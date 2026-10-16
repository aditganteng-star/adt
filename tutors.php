<?php
/** Pengenalan tutor + filter. */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Kenali Tutor';
$active    = 'tutors';
$views     = page_counter();
$tutors    = rows('SELECT * FROM tutor_profiles WHERE is_active = 1 ORDER BY sort_order, name');

require __DIR__ . '/includes/site_header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Orang di balik proses belajarmu</span>
    <h1>Kenali tutor yang akan <span class="hl">belajar bareng</span> kamu.</h1>
    <p>Semua tutor Hakuna adalah mahasiswa dan lulusan perguruan tinggi pilihan. Cari berdasarkan jenjang atau mata pelajaran.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="filter-bar" id="tutorFilter">
      <div class="field grow"><label for="tSearch">Cari tutor</label><input id="tSearch" type="search" placeholder="Nama, kampus, atau mapel…"></div>
      <div class="field"><label for="tLevel">Jenjang</label>
        <select id="tLevel"><option value="">Semua jenjang</option><option>SD</option><option>SMP</option><option>SMA</option><option>UTBK</option></select>
      </div>
      <button class="btn btn-soft" type="button" id="tReset">Reset</button>
    </div>
    <p class="result-count" id="tCount" aria-live="polite"></p>

    <div class="tutor-grid" id="tutorGrid">
      <?php foreach ($tutors as $t): ?>
        <article class="tutor-card reveal" data-levels="<?= e((string) $t['levels']) ?>"
                 data-search="<?= e(mb_strtolower($t['name'] . ' ' . $t['subject'] . ' ' . $t['university'] . ' ' . $t['major'])) ?>">
          <div class="tutor-top">
            <?= tutor_avatar($t, 'lg') ?>
            <span class="tag"><?= e($t['subject']) ?></span>
          </div>
          <h3><?= e($t['name']) ?></h3>
          <div class="edu-box">
            <span>🎓</span>
            <div><b><?= e($t['university']) ?></b><small><?= e($t['major']) ?></small></div>
          </div>
          <p class="bio"><?= e((string) $t['bio']) ?></p>
          <div class="tutor-meta">
            <span>⭐ <?= e((string) $t['achievement']) ?></span>
            <span>⏱ <?= e((string) $t['experience']) ?></span>
          </div>
          <div class="level-tags"><?php foreach (explode(',', (string) $t['levels']) as $lv): ?><i><?= e(trim($lv)) ?></i><?php endforeach; ?></div>
          <a class="btn btn-outline btn-full" target="_blank" rel="noopener"
             href="<?= e(whatsapp_url('Halo Hakuna, saya ingin belajar bersama ' . $t['name'] . ' (' . $t['subject'] . '). Mohon info jadwal yang tersedia.')) ?>">Belajar dengan <?= e(explode(' ', $t['name'])[1] ?? 'tutor') ?> →</a>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="empty-state" id="tEmpty" hidden>Tidak ada tutor yang cocok. Coba kata kunci atau jenjang lain.</div>

    <?php if (SHOW_SAMPLE_NOTICE): ?>
      <div class="notice"><b>Catatan pemilik website:</b> profil tutor di halaman ini adalah <u>data contoh</u>. Ganti dengan data tutor sebenarnya lewat <b>Dashboard Admin → Tutor</b>, lalu set <code>SHOW_SAMPLE_NOTICE = false</code> di config.php untuk menyembunyikan catatan ini.</div>
    <?php endif; ?>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-inner reveal">
    <div><h2>Ingin jadi bagian tim tutor Hakuna?</h2><p>Kami selalu mencari mahasiswa dan lulusan berprestasi yang suka berbagi ilmu.</p></div>
    <div class="cta-actions"><a class="btn btn-white btn-large" target="_blank" rel="noopener" href="<?= e(whatsapp_url('Halo Hakuna, saya tertarik menjadi tutor.')) ?>">Daftar jadi tutor</a></div>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
