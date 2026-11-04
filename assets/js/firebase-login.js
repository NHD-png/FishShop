(() => {
  const form = document.querySelector('[data-firebase-login]');
  if (!form) return;
  const button = form.querySelector('button');
  const status = document.getElementById('firebase-status');
  const config = JSON.parse(document.getElementById('firebase-config').textContent);
  const sdk = Promise.all([
    import('https://www.gstatic.com/firebasejs/13.1.0/firebase-app.js'),
    import('https://www.gstatic.com/firebasejs/13.1.0/firebase-auth.js')
  ]).then(async ([appSdk, authSdk]) => {
    const auth = authSdk.getAuth(appSdk.initializeApp(config));
    await authSdk.setPersistence(auth, authSdk.inMemoryPersistence);
    const provider = new authSdk.GoogleAuthProvider();
    provider.setCustomParameters({ prompt: 'select_account' });
    return { auth, authSdk, provider };
  });
  let ready;
  sdk.then(value => { ready = value; }).catch(() => {
    status.textContent = 'Không tải được Firebase. Kiểm tra Internet rồi tải lại trang.';
  });
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!ready) {
      status.textContent = 'Firebase đang tải. Vui lòng chờ vài giây rồi bấm lại.';
      return;
    }
    button.disabled = true;
    form.setAttribute('aria-busy', 'true');
    status.textContent = 'Đang mở cửa sổ chọn tài khoản Google…';
    try {
      // Called directly during the click to avoid popup blocking.
      const result = await ready.authSdk.signInWithPopup(ready.auth, ready.provider);
      status.textContent = 'Đang xác minh tài khoản…';
      const body = new FormData(form);
      // signInWithPopup already returns a fresh user. Avoid forcing a second
      // token refresh request before sending it to the PHP verifier.
      body.set('id_token', await result.user.getIdToken());
      const response = await fetch(form.action, { method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json' } });
      let data;
      try { data = await response.json(); } catch { throw new Error('Máy chủ chưa sẵn sàng. Vui lòng kiểm tra MySQL và thử lại.'); }
      if (!response.ok || !data.ok) throw new Error(data.message || 'Không thể đăng nhập.');
      await ready.authSdk.signOut(ready.auth).catch(() => {});
      window.location.assign(data.redirect);
    } catch (error) {
      const messages = {
        'auth/popup-closed-by-user': 'Bạn đã đóng cửa sổ Google. Bấm nút để thử lại.',
        'auth/cancelled-popup-request': 'Yêu cầu trước đã bị hủy. Vui lòng thử lại.',
        'auth/popup-blocked': 'Trình duyệt chặn cửa sổ Google. Hãy cho phép cửa sổ bật lên rồi thử lại.',
        'auth/unauthorized-domain': 'Tên miền này chưa được cho phép trong Firebase Authentication.',
        'auth/operation-not-allowed': 'Chưa bật Google trong Firebase Authentication.',
        'auth/network-request-failed': 'Không kết nối được Firebase. Hãy kiểm tra Internet.',
        'auth/account-exists-with-different-credential': 'Email này đã dùng phương thức đăng nhập khác.',
        'auth/invalid-api-key': 'Cấu hình Firebase API key không hợp lệ.'
      };
      status.textContent = messages[error.code] || (error.code ? 'Không thể đăng nhập Google. Vui lòng thử lại.' : error.message);
      await ready.authSdk.signOut(ready.auth).catch(() => {});
    } finally {
      button.disabled = false;
      form.removeAttribute('aria-busy');
    }
  });
})();
