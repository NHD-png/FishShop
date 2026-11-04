(function () {
  'use strict';
  var shipping = document.getElementById('checkout-shipping-form');
  if (!shipping) return;
  document.querySelectorAll('[data-voucher-form]').forEach(function (form) {
    form.addEventListener('submit', function () {
      ['phone', 'shipping_address', 'shipping_latitude', 'shipping_longitude', 'payment_method'].forEach(function (name) {
        var source = shipping.querySelector('[name="' + name + '"]' + (name === 'payment_method' ? ':checked' : ''));
        var target = form.querySelector('[data-draft="' + name + '"]');
        if (source && target) target.value = source.value;
      });
    });
  });
})();
