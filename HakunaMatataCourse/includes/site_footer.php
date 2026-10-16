</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-about">
      <a class="brand brand-light" href="<?= e(url('index.php')) ?>">
        <span class="brand-mark">h<span>!</span></span>
        <span class="brand-name">hakuna<span>matata</span><small>COURSE</small></span>
      </a>
      <p>Teman belajar yang bikin proses memahami pelajaran terasa lebih ringan — dari SD sampai persiapan UTBK.</p>
      <a class="btn btn-white btn-small" target="_blank" rel="noopener" href="<?= e(whatsapp_url('Halo Hakuna Matata Course, saya ingin bertanya tentang program les.')) ?>">💬 Chat WhatsApp</a>
    </div>
    <div>
      <h4>Program</h4>
      <a href="<?= e(url('program.php#sd')) ?>">Les SD</a>
      <a href="<?= e(url('program.php#smp')) ?>">Les SMP</a>
      <a href="<?= e(url('program.php#sma')) ?>">Les SMA</a>
      <a href="<?= e(url('program.php#utbk')) ?>">Persiapan UTBK</a>
    </div>
    <div>
      <h4>Jelajahi</h4>
      <a href="<?= e(url('tutors.php')) ?>">Kenali tutor</a>
      <a href="<?= e(url('packages.php')) ?>">Paket &amp; tryout</a>
      <a href="<?= e(url('packages.php#coba-tryout')) ?>">Tryout gratis</a>
      <a href="<?= e(url('contact.php')) ?>">Hubungi kami</a>
    </div>
    <div>
      <h4>Portal</h4>
      <a href="<?= e(url('login.php')) ?>">Masuk murid / tutor</a>
      <a href="<?= e(url('register.php')) ?>">Daftar akun murid</a>
      <p class="footer-loc">📍 <?= e(SITE_CITY) ?><br>✉️ <?= e(SITE_EMAIL) ?></p>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>© <?= date('Y') ?> <?= e(SITE_NAME) ?> · <?= e(SITE_TAGLINE) ?></span>
    <span class="page-views">👀 Total Page Views: <b><?= number_format($views ?? page_counter(), 0, ',', '.') ?></b></span>
  </div>
</footer>

<a class="wa-float" id="waFloat" target="_blank" rel="noopener" aria-label="Chat WhatsApp"
   href="<?= e(whatsapp_url('Halo Hakuna Matata Course, saya ingin konsultasi program belajar.')) ?>">
  <svg viewBox="0 0 32 32" width="28" height="28" aria-hidden="true"><path fill="#fff" d="M16 3C9 3 3.3 8.6 3.3 15.5c0 2.4.7 4.7 1.9 6.600L3 29l7.100-2.100a13 13 0 0 0 5.900 1.400c7 0 12.700-5.600 12.700-12.500S23 3 16 3zm0 22.700c-1.900 0-3.700-.5-5.300-1.500l-.4-.2-4.200 1.200 1.200-4-.3-.4a10 10 0 0 1-1.600-5.400C5.400 10 10.200 5.400 16 5.400s10.600 4.600 10.600 10.200S21.800 25.700 16 25.700zm5.800-7.500c-.3-.2-1.900-.9-2.200-1s-.5-.2-.7.200-.8 1-.9 1.200-.4.200-.7.100a8.600 8.600 0 0 1-4.200-3.700c-.3-.6.300-.5.900-1.700.1-.2 0-.4 0-.5l-1-2.300c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.5.100-.8.400s-1 1-1 2.400 1.100 2.800 1.200 3c.2.200 2.100 3.200 5 4.400 1.900.8 2.600.8 3.600.7.600-.1 1.900-.8 2.100-1.500s.3-1.400.2-1.500-.3-.3-.6-.4z"/></svg>
  <span>Tanya Admin Yuk!</span>
</a>

<div class="lightbox" id="lightbox" hidden><button type="button" class="lightbox-close" aria-label="Tutup">×</button><img alt=""></div>
<div class="toast-region" id="toastRegion" aria-live="polite"></div>
</body>
</html>
