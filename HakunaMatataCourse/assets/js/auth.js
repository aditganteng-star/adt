/* =====================================================================
   Login & daftar: Google Identity Services (atau mode demo di localhost)
   ===================================================================== */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const block = $('.google-block');

  async function sendToServer(payload) {
    const msg = $('.form-msg', block);
    msg.textContent = 'Memproses…'; msg.classList.remove('ok');
    const res = await window.postForm(window.HAKUNA.base + '/api/google.php', Object.assign({ next: block.dataset.next || '' }, payload));
    if (res.ok) { msg.textContent = 'Berhasil! Mengalihkan…'; msg.classList.add('ok'); location.href = res.redirect; }
    else { msg.textContent = res.error || 'Gagal masuk dengan Google.'; }
  }

  if (block) {
    const clientId = block.dataset.clientId;
    if (clientId) {
      // Mode normal: muat skrip Google lalu render tombol resmi.
      const s = document.createElement('script');
      s.src = 'https://accounts.google.com/gsi/client'; s.async = true; s.defer = true;
      s.onload = () => {
        google.accounts.id.initialize({ client_id: clientId, callback: (r) => sendToServer({ credential: r.credential }) });
        const holder = $('#googleBtn');
        google.accounts.id.renderButton(holder, { theme: 'outline', size: 'large', shape: 'pill', width: Math.min(holder.parentElement.offsetWidth, 400), text: holder.dataset.text && holder.dataset.text.includes('Daftar') ? 'signup_with' : 'signin_with', locale: 'id' });
      };
      s.onerror = () => { $('.form-msg', block).textContent = 'Tidak bisa memuat Google. Periksa koneksi internet.'; };
      document.head.appendChild(s);
    } else {
      // Mode demo (hanya tersedia di localhost).
      const btn = $('#googleDemoBtn'), box = $('#demoGoogleBox');
      if (btn) {
        btn.addEventListener('click', () => { box.hidden = !box.hidden; });
        $('#demoGoogleGo').addEventListener('click', () => sendToServer({ name: $('#demoName').value, email: $('#demoEmail').value }));
      }
    }
  }

  // Isi otomatis akun demo.
  document.querySelectorAll('[data-demo]').forEach((b) => b.addEventListener('click', () => {
    const [email, pw] = b.dataset.demo.split('|');
    $('#email').value = email; $('#password').value = pw;
    window.toast('Akun demo terisi — klik Masuk');
  }));
})();
