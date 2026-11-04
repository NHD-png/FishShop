(function () {
  'use strict';
  var widgets = document.querySelectorAll('[data-map-widget]');
  if (!widgets.length) return;
  var base = document.body.dataset.base || '/FishShop/';
  var configElement = document.getElementById('fishshop-map-config');
  var config = configElement ? JSON.parse(configElement.textContent) : {};
  var leafletPromise;

  function loadLeaflet() {
    if (window.L) return Promise.resolve(window.L);
    if (leafletPromise) return leafletPromise;
    leafletPromise = new Promise(function (resolve, reject) {
      var css = document.createElement('link');
      css.rel = 'stylesheet';
      css.href = base + 'assets/vendor/leaflet/leaflet.css';
      document.head.appendChild(css);
      var script = document.createElement('script');
      script.src = base + 'assets/vendor/leaflet/leaflet.js';
      script.onload = function () { window.L ? resolve(window.L) : reject(new Error('map-library')); };
      script.onerror = function () { reject(new Error('map-library')); };
      document.head.appendChild(script);
    });
    return leafletPromise;
  }

  function initialize(widget) {
    if (widget.dataset.mapInitialized) return;
    widget.dataset.mapInitialized = 'true';
    var canvas = widget.querySelector('[data-map-canvas]');
    var error = widget.querySelector('[data-map-error]');
    function showError(message) { error.textContent = message; error.hidden = false; }
    loadLeaflet().then(function (L) {
      var delivery = widget.dataset.mapWidget === 'delivery';
      var center = [Number(widget.dataset.latitude), Number(widget.dataset.longitude)];
      var map = L.map(canvas, {scrollWheelZoom: false, minZoom: 3, maxZoom: 19, worldCopyJump: true});
      map.setView(center, Number(widget.dataset.zoom) || 14);
      var tiles = L.tileLayer(config.tileUrl || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors'
      }).addTo(map);
      var tileErrors = 0;
      tiles.on('tileerror', function () {
        tileErrors += 1;
        if (tileErrors >= 3) showError('Chưa tải được nền bản đồ. Hãy kiểm tra kết nối mạng.' + (delivery ? ' Bạn vẫn có thể nhập địa chỉ và đặt hàng.' : ' Bạn có thể dùng nút chỉ đường.'));
      });
      tiles.on('tileload', function () { tileErrors = 0; error.hidden = true; });
      var icon = L.divIcon({
        className: 'fishshop-map-pin' + (delivery ? ' delivery-pin' : ''),
        html: '<span><b>' + (delivery ? '' : 'F') + '</b></span>',
        iconSize: [30, 38], iconAnchor: [15, 36], popupAnchor: [0, -32]
      });
      if (window.ResizeObserver) new ResizeObserver(function () { map.invalidateSize(); }).observe(canvas);
      if (!delivery) {
        var label = document.createElement('strong');
        label.textContent = widget.dataset.label || 'Fish Shop';
        L.marker(center, {icon: icon, title: widget.dataset.label || 'Fish Shop'}).addTo(map).bindPopup(label);
        return;
      }

      var latitude = widget.querySelector('[name="shipping_latitude"]');
      var longitude = widget.querySelector('[name="shipping_longitude"]');
      var status = widget.querySelector('[data-map-status]');
      var clear = widget.querySelector('[data-map-clear]');
      var locate = widget.querySelector('[data-map-locate]');
      var centerButton = widget.querySelector('[data-map-center]');
      var selectedLink = widget.querySelector('[data-map-selected-link]');
      var marker = null;
      var locationRequest = 0;
      function selectPoint(point) {
        point = L.latLng(point).wrap();
        latitude.value = point.lat.toFixed(7);
        longitude.value = point.lng.toFixed(7);
        if (!marker) {
          marker = L.marker(point, {icon: icon, draggable: true, title: 'Điểm giao hàng đã chọn'}).addTo(map);
          marker.on('dragend', function () { selectManually(marker.getLatLng()); });
        } else marker.setLatLng(point);
        status.textContent = 'Đã chọn điểm giao hàng. Kéo ghim để chỉnh vị trí nếu cần.';
        status.classList.add('has-location');
        clear.disabled = false;
        selectedLink.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(latitude.value + ',' + longitude.value);
        selectedLink.hidden = false;
      }
      function cancelLocationRequest() {
        locationRequest += 1;
        locate.disabled = false;
        locate.textContent = 'Vị trí của tôi';
      }
      function selectManually(point) {
        cancelLocationRequest();
        selectPoint(point);
      }
      map.on('click', function (event) { selectManually(event.latlng); });
      centerButton.addEventListener('click', function () { selectManually(map.getCenter()); });
      clear.addEventListener('click', function () {
        cancelLocationRequest();
        if (marker) map.removeLayer(marker);
        marker = null;
        latitude.value = '';
        longitude.value = '';
        clear.disabled = true;
        selectedLink.hidden = true;
        selectedLink.removeAttribute('href');
        status.textContent = 'Đã bỏ ghim. Bạn có thể chọn lại hoặc dùng địa chỉ đã nhập.';
        status.classList.remove('has-location');
      });
      locate.addEventListener('click', function () {
        if (!window.isSecureContext) {
          showError('Định vị cần kết nối HTTPS hoặc localhost. Bạn có thể chọn trực tiếp trên bản đồ.');
          return;
        }
        if (!navigator.geolocation) { showError('Trình duyệt chưa hỗ trợ định vị. Hãy chọn trực tiếp trên bản đồ.'); return; }
        var request = ++locationRequest;
        locate.disabled = true;
        locate.textContent = 'Đang tìm vị trí…';
        navigator.geolocation.getCurrentPosition(function (position) {
          if (request !== locationRequest) return;
          locate.disabled = false;
          locate.textContent = 'Vị trí của tôi';
          error.hidden = true;
          var point = [position.coords.latitude, position.coords.longitude];
          selectPoint(point);
          map.setView(point, 17);
        }, function (failure) {
          if (request !== locationRequest) return;
          locate.disabled = false;
          locate.textContent = 'Vị trí của tôi';
          showError(failure.code === 1 ? 'Bạn chưa cho phép truy cập vị trí. Hãy chọn điểm trực tiếp trên bản đồ.' : 'Chưa xác định được vị trí. Hãy chọn điểm trực tiếp trên bản đồ.');
        }, {enableHighAccuracy: true, timeout: 10000, maximumAge: 60000});
      });
      if (latitude.value !== '' && longitude.value !== '') {
        selectPoint([Number(latitude.value), Number(longitude.value)]);
      }
      widget.closest('form').addEventListener('reset', function () { if (!clear.disabled) clear.click(); });
    }).catch(function () {
      canvas.classList.add('map-unavailable');
      showError('Chưa mở được bản đồ. Bạn vẫn có thể dùng địa chỉ đã nhập hoặc nút chỉ đường.');
      widget.querySelectorAll('button').forEach(function (button) { button.disabled = true; });
    });
  }

  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) { if (entry.isIntersecting) { observer.unobserve(entry.target); initialize(entry.target); } });
    }, {rootMargin: '160px'});
    widgets.forEach(function (widget) { observer.observe(widget); });
  } else widgets.forEach(initialize);
})();
