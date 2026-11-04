<p id="firebase-status" class="hint" role="status" aria-live="polite"></p>
<noscript><p class="alert err">Vui lòng bật JavaScript để đăng nhập Google.</p></noscript>
<script type="application/json" id="firebase-config"><?= json_encode(require __DIR__ . '/../config/firebase.php', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= url('assets/js/firebase-login.js') ?>?v=<?= filemtime(__DIR__ . '/../assets/js/firebase-login.js') ?>" defer></script>
