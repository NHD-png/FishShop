// WebBanCa - JavaScript giao diện. Mọi giá tiền/tồn kho đều do PHP quyết định lại ở máy chủ.
(function () {
  'use strict';
  var base = document.body.dataset.base || '/';
  var csrf = document.body.dataset.csrf || '';

  // ----- Thông báo nổi -----
  function toast(msg, ok) {
    var box = document.getElementById('toasts');
    if (!box) return;
    var t = document.createElement('div');
    t.className = 'toast ' + (ok ? 'ok' : 'err');
    t.setAttribute('role', 'status');
    t.textContent = msg;
    box.appendChild(t);
    setTimeout(function () { t.classList.add('leave'); setTimeout(function () { t.remove(); }, 350); }, 3800);
  }
  document.querySelectorAll('#toasts .toast').forEach(function (t) {
    setTimeout(function () { t.classList.add('leave'); setTimeout(function () { t.remove(); }, 350); }, 4500);
  });

  // ----- Menu điện thoại -----
  var menuBtn = document.querySelector('.menu-btn');
  if (menuBtn) menuBtn.addEventListener('click', function () {
    var open = document.getElementById('nav-main').classList.toggle('open');
    menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  // ----- Thêm vào giỏ bằng fetch (không tải lại trang) -----
  document.querySelectorAll('form.js-add-cart').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var btn = form.querySelector('button[type=submit]');
      if (btn) btn.disabled = true;
      fetch(base + 'actions/add_to_cart.php', {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' },
        credentials: 'same-origin'
      })
        .then(function (r) {
          if (r.status === 401) { window.location.href = base + 'login.php'; throw new Error('login'); }
          return r.json();
        })
        .then(function (d) {
          toast(d.message, d.ok);
          if (d.ok && typeof d.cartCount !== 'undefined') {
            var b = document.getElementById('cart-badge');
            if (b) b.textContent = d.cartCount;
          }
        })
        .catch(function (e) { if (e.message !== 'login') toast('Không thể kết nối máy chủ. Vui lòng thử lại.', false); })
        .then(function () { if (btn) btn.disabled = false; });
    });
  });

  // ----- Ô chọn số lượng (+/-) -----
  document.querySelectorAll('.qty').forEach(function (box) {
    var input = box.querySelector('input');
    var max = parseInt(input.getAttribute('max'), 10) || 9999;
    function clamp() {
      var v = parseInt(input.value, 10);
      if (isNaN(v) || v < 1) v = 1;
      if (v > max) { v = max; toast('Chỉ còn ' + max + ' sản phẩm trong kho.', false); }
      input.value = v;
    }
    box.querySelectorAll('button').forEach(function (b) {
      b.addEventListener('click', function () {
        input.value = (parseInt(input.value, 10) || 1) + parseInt(b.dataset.step, 10);
        clamp();
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });
    input.addEventListener('change', clamp);
  });

  // Trang giỏ hàng: đổi số lượng thì tự gửi biểu mẫu
  document.querySelectorAll('form.js-autosubmit').forEach(function (f) {
    var timer;
    f.querySelector('.qty input').addEventListener('change', function () {
      clearTimeout(timer);
      timer = setTimeout(function () { f.submit(); }, 450);
    });
    var sb = f.querySelector('.noscript-btn');
    if (sb) sb.classList.add('hidden');
  });

  // ----- Xác nhận trước khi xóa/hủy -----
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (ev) { if (!confirm(el.dataset.confirm)) ev.preventDefault(); });
  });

  // ----- Hiện/ẩn mật khẩu -----
  document.querySelectorAll('.pw-toggle').forEach(function (b) {
    b.addEventListener('click', function () {
      var input = b.parentElement.querySelector('input');
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      b.textContent = show ? 'Ẩn' : 'Hiện';
    });
  });

  // ----- Kiểm tra biểu mẫu phía trình duyệt (PHP vẫn kiểm tra lại) -----
  var rules = {
    username: function (v) { return /^[A-Za-z0-9_]{3,30}$/.test(v) ? '' : 'Tên đăng nhập 3-30 ký tự: chữ, số hoặc dấu gạch dưới.'; },
    password: function (v) {
      if (v.length < 8) return 'Mật khẩu tối thiểu 8 ký tự.';
      if (!/[A-Z]/.test(v) || !/[a-z]/.test(v) || !/[0-9]/.test(v) || !/[^A-Za-z0-9]/.test(v)) {
        return 'Mật khẩu phải có chữ hoa, chữ thường, số và ký tự đặc biệt.';
      }
      return '';
    },
    fullname: function (v) { return v.trim().length >= 2 ? '' : 'Vui lòng nhập họ tên.'; },
    phone: function (v) { return /^[0-9]{9,11}$/.test(v.trim()) ? '' : 'Số điện thoại gồm 9-11 chữ số.'; },
    address: function (v) { return v.trim().length >= 5 ? '' : 'Vui lòng nhập địa chỉ đầy đủ.'; },
    email: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()) ? '' : 'Email không hợp lệ.'; },
    security_question: function (v) { return v !== '' ? '' : 'Vui lòng chọn câu hỏi bảo mật.'; },
    security_answer: function (v) { return v.trim().length >= 2 ? '' : 'Vui lòng nhập câu trả lời bảo mật.'; }
  };
  document.querySelectorAll('form.js-validate').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      var bad = false;
      form.querySelectorAll('.field').forEach(function (f) {
        var input = f.querySelector('input, textarea, select');
        if (!input) return;
        var msg = '';
        var rule = rules[input.dataset.rule];
        if (rule && (input.required || input.value !== '')) msg = rule(input.value);
        if (!msg && input.dataset.match) {
          var other = form.querySelector('[name=' + input.dataset.match + ']');
          if (other && other.value !== input.value) msg = 'Mật khẩu nhập lại không khớp.';
        }
        var slot = f.querySelector('.err-msg');
        if (!slot) { slot = document.createElement('div'); slot.className = 'err-msg'; f.appendChild(slot); }
        slot.textContent = msg;
        f.classList.toggle('invalid', !!msg);
        if (msg) bad = true;
      });
      if (bad) { ev.preventDefault(); var first = form.querySelector('.invalid input, .invalid textarea, .invalid select'); if (first) first.focus(); }
    });
  });

  // ----- Xem trước ảnh khi chọn file -----
  document.querySelectorAll('input[type=file][data-preview]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var img = document.getElementById(inp.dataset.preview);
      var f = inp.files && inp.files[0];
      if (!f) return;
      if (f.size > 2 * 1024 * 1024) { toast('Ảnh vượt quá 2MB.', false); inp.value = ''; return; }
      if (img) img.src = URL.createObjectURL(f);
    });
  });
})();
