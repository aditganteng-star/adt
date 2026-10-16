<?php
/** Beranda — halaman sales utama. */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Les SD, SMP, SMA & Persiapan UTBK';
$active    = 'home';
$views     = page_counter();

$packages = rows('SELECT * FROM packages WHERE is_active = 1 ORDER BY sort_order');
$tutors   = rows('SELECT * FROM tutor_profiles WHERE is_active = 1 ORDER BY sort_order LIMIT 8');
$stats = [
    'students' => (int) val("SELECT COUNT(*) FROM users WHERE role = 'student'"),
    'tutors'   => (int) val('SELECT COUNT(*) FROM tutor_profiles WHERE is_active = 1'),
    'tryouts'  => (int) val('SELECT COUNT(*) FROM tryout_attempts'),
    'classes'  => (int) val('SELECT COUNT(*) FROM classes'),
];

// Ringkasan per jenjang untuk tab "Pilih jenjangmu".
$levels = [
    'SD'   => ['🌱', 'Fondasi kuat sejak dini', 'Belajar konsep dasar lewat cara yang menyenangkan: cerita, permainan, dan latihan bertahap.', ['Calistung & Matematika dasar', 'IPA & Bahasa Indonesia', 'Kebiasaan belajar mandiri']],
    'SMP'  => ['🚀', 'Makin percaya diri', 'Perkuat pemahaman konsep, latihan soal, dan persiapan menghadapi ujian sekolah.', ['Matematika, IPA, Bahasa Inggris', 'Bank soal & pembahasan', 'Tryout bulanan']],
    'SMA'  => ['📘', 'Target jelas, hasil terukur', 'Penguatan mata pelajaran dengan pemetaan materi dan strategi mengerjakan soal.', ['Mapel IPA & IPS', 'Pemetaan target nilai', 'Konsultasi jurusan']],
    'UTBK' => ['🎯', 'Siap tembus PTN impian', 'Latihan terstruktur TPS, literasi, dan tryout rutin dengan evaluasi pribadi.', ['TPS & Literasi', 'Tryout mingguan', 'Strategi & mentoring']],
];

$faqs = [
    ['Bagaimana cara mendaftar les di Hakuna?', 'Pilih paket di halaman Paket & Tryout, isi nama dan nomor WhatsApp, lalu kamu langsung diarahkan ke WhatsApp admin untuk jadwal dan pembayaran. Tanpa antre, tanpa ribet.'],
    ['Apakah les dilakukan online atau tatap muka?', 'Keduanya tersedia. Kelas reguler tatap muka di ' . SITE_CITY . ', sedangkan materi, tugas, absensi, dan tryout bisa diakses online lewat portal murid.'],
    ['Apa itu portal murid?', 'Dashboard khusus murid untuk absen, mengunduh materi, mengumpulkan tugas, mengerjakan tryout, dan melihat nilai. Cukup masuk dengan akun Google.'],
    ['Apakah ada tryout gratis?', 'Ada! Coba mini-tryout UTBK gratis di halaman Paket & Tryout — hasilnya langsung keluar beserta pembahasan.'],
    ['Bagaimana cara membayar paket?', 'Pembayaran dikonfirmasi manual oleh admin melalui WhatsApp. Setelah pembayaran terverifikasi, akses portal dan tryout kamu langsung diaktifkan.'],
    ['Bisa ganti tutor atau jadwal?', 'Bisa. Hubungi admin lewat WhatsApp dan kami bantu sesuaikan dengan kebutuhan serta jadwal sekolahmu.'],
];

require __DIR__ . '/includes/site_header.php';
?>

<!-- ================= HERO ================= -->
<section class="hero">
  <div class="hero-bg" aria-hidden="true"><span class="blob b1"></span><span class="blob b2"></span><span class="dots"></span></div>
  <div class="container hero-grid">
    <div class="hero-copy reveal">
      <span class="eyebrow"><span class="eyebrow-dot"></span> Les terpercaya dari <?= e(explode(',', SITE_CITY)[0]) ?>, Sidoarjo</span>
      <h1>Les yang bikin kamu <span class="hl">paham</span>, bukan sekadar hafal.</h1>
      <p class="hero-lead">Tutor lulusan kampus terbaik, tryout online, dan portal belajar pribadi — semua dalam satu tempat. Mulai dari SD sampai persiapan UTBK.</p>

      <div class="hero-actions">
        <a class="btn btn-primary btn-large" href="<?= e(url('program.php')) ?>">Kenali program kami <span aria-hidden="true">→</span></a>
        <a class="btn btn-soft btn-large" href="<?= e(url('packages.php')) ?>">Lihat paket &amp; tryout</a>
      </div>

      <form class="lead-form" id="heroLead" novalidate>
        <div class="lead-title">💬 Konsultasi gratis — kami hubungi lewat WhatsApp</div>
        <div class="lead-row">
          <input name="name" placeholder="Nama kamu" autocomplete="name" required aria-label="Nama">
          <input name="phone" type="tel" inputmode="tel" placeholder="No. WhatsApp" autocomplete="tel" required aria-label="Nomor WhatsApp">
          <select name="level" aria-label="Jenjang">
            <option>SD</option><option selected>SMP</option><option>SMA</option><option>UTBK</option>
          </select>
          <button class="btn btn-primary" type="submit">Hubungi saya</button>
        </div>
        <input type="hidden" name="source" value="hero">
        <small class="form-msg" role="alert"></small>
      </form>
    </div>

    <div class="hero-art reveal" aria-hidden="true">
      <div class="mock">
        <div class="mock-bar"><i></i><i></i><i></i><span>portal.hakuna</span></div>
        <div class="mock-body">
          <div class="mock-hello">Halo, Adit! 👋<small>Kamu sudah belajar 12 hari berturut-turut 🔥</small></div>
          <div class="mock-progress">
            <div><span>Matematika</span><b>82%</b><i style="--w:82%"></i></div>
            <div><span>Bahasa Inggris</span><b>68%</b><i style="--w:68%"></i></div>
            <div><span>Tryout UTBK</span><b>74%</b><i style="--w:74%"></i></div>
          </div>
          <div class="mock-task"><span>✎</span><div><b>Latihan Persamaan Linear</b><small>Tenggat 3 hari lagi</small></div><em>Kumpulkan</em></div>
        </div>
      </div>
      <div class="chip-float c1"><span>✅</span> Absen hari ini berhasil</div>
      <div class="chip-float c2"><span>🏆</span> Skor tryout <b>86</b></div>
      <div class="chip-float c3"><span>🎓</span> Tutor lulusan PTN</div>
      <span class="spark s1">✦</span><span class="spark s2">✳</span>
    </div>
  </div>
</section>

<!-- ================= MARQUEE ================= -->
<section class="marquee" aria-label="Keunggulan">
  <div class="marquee-track">
    <?php for ($i = 0; $i < 2; $i++): ?>
      <span>✦ Tutor lulusan PTN</span><span>✦ Tryout online + pembahasan</span><span>✦ Portal murid & tutor</span>
      <span>✦ Absensi digital</span><span>✦ Materi online 24 jam</span><span>✦ Daftar langsung via WhatsApp</span>
    <?php endfor; ?>
  </div>
</section>

<!-- ================= SHORTCUT ================= -->
<section class="section">
  <div class="container">
    <div class="section-heading center reveal">
      <span class="eyebrow">Semua kebutuhan belajar</span>
      <h2>Satu tempat untuk semua langkah belajarmu.</h2>
      <p class="section-subtitle">Pilih yang paling kamu butuhkan hari ini — kami siap bantu dari konsultasi sampai hasil belajar.</p>
    </div>
    <div class="shortcut-grid">
      <a class="shortcut reveal" href="<?= e(url('packages.php?kategori=les')) ?>"><span class="sc-ico bg-mint">📚</span><b>Les Reguler</b><small>SD · SMP · SMA</small></a>
      <a class="shortcut reveal" href="<?= e(url('packages.php?kategori=utbk')) ?>"><span class="sc-ico bg-sun">🎯</span><b>Persiapan UTBK</b><small>Intensif &amp; premium</small></a>
      <a class="shortcut reveal" href="<?= e(url('packages.php?kategori=tryout')) ?>"><span class="sc-ico bg-lilac">📝</span><b>Tryout Online</b><small>Skor &amp; pembahasan</small></a>
      <a class="shortcut reveal" href="<?= e(url('packages.php?q=privat')) ?>"><span class="sc-ico bg-peach">👩‍🏫</span><b>Les Privat</b><small>1 lawan 1</small></a>
      <a class="shortcut reveal" href="<?= e(url('tutors.php')) ?>"><span class="sc-ico bg-sky">🎓</span><b>Kenali Tutor</b><small>Lulusan PTN pilihan</small></a>
      <a class="shortcut reveal" href="<?= e(url('login.php')) ?>"><span class="sc-ico bg-rose">🧑‍💻</span><b>Portal Murid</b><small>Absen, materi, tugas</small></a>
    </div>
  </div>
</section>

<!-- ================= PILIH JENJANG ================= -->
<section class="section section-tint" id="jenjang">
  <div class="container">
    <div class="section-heading reveal">
      <div>
        <span class="eyebrow">Paket populer</span>
        <h2>Pilih jenjangmu, lihat paket yang cocok.</h2>
      </div>
      <a class="text-link" href="<?= e(url('packages.php')) ?>">Semua paket →</a>
    </div>

    <div class="tabs" role="tablist" data-tabs>
      <?php $first = true; foreach ($levels as $key => $_): ?>
        <button class="tab <?= $first ? 'active' : '' ?>" role="tab" data-tab="lv-<?= e($key) ?>" aria-selected="<?= $first ? 'true' : 'false' ?>"><?= e($key) ?></button>
      <?php $first = false; endforeach; ?>
    </div>

    <?php $first = true; foreach ($levels as $key => [$emoji, $title, $desc, $points]):
      $lvPackages = array_values(array_filter($packages, fn($p) => $p['level'] === $key));
      if ($key === 'SMA') $lvPackages = array_values(array_filter($packages, fn($p) => $p['level'] === 'SMA'));
    ?>
      <div class="tab-panel <?= $first ? 'active' : '' ?>" id="lv-<?= e($key) ?>" role="tabpanel">
        <div class="level-panel">
          <div class="level-info">
            <span class="level-emoji"><?= $emoji ?></span>
            <h3><?= e($title) ?></h3>
            <p><?= e($desc) ?></p>
            <ul class="check-list"><?php foreach ($points as $pt): ?><li><?= e($pt) ?></li><?php endforeach; ?></ul>
            <a class="btn btn-outline" href="<?= e(url('program.php#' . strtolower($key))) ?>">Detail program <?= e($key) ?> →</a>
          </div>
          <div class="level-packages">
            <?php foreach (array_slice($lvPackages, 0, 2) as $p): ?>
              <article class="mini-package">
                <?php if ($p['badge']): ?><span class="badge"><?= e($p['badge']) ?></span><?php endif; ?>
                <h4><?= e($p['name']) ?></h4>
                <p><?= e($p['description']) ?></p>
                <div class="price-line">
                  <?php if ($p['old_price']): ?><s><?= rupiah((int) $p['old_price']) ?></s><?php endif; ?>
                  <strong><?= rupiah((int) $p['price']) ?></strong><span>/<?= e($p['unit']) ?></span>
                </div>
                <a class="btn btn-primary btn-full btn-small" href="<?= e(url('packages.php?pilih=' . $p['slug'])) ?>">Pilih paket</a>
              </article>
            <?php endforeach; ?>
            <?php if (!$lvPackages): ?><div class="empty-state">Paket jenjang ini segera hadir. Tanyakan via WhatsApp!</div><?php endif; ?>
          </div>
        </div>
      </div>
    <?php $first = false; endforeach; ?>
  </div>
</section>

<!-- ================= CARA KERJA ================= -->
<section class="section">
  <div class="container">
    <div class="section-heading center reveal">
      <span class="eyebrow">Mulai dalam 4 langkah</span>
      <h2>Daftar semudah ngobrol di WhatsApp.</h2>
    </div>
    <ol class="steps">
      <li class="reveal"><span class="step-no">1</span><h3>Pilih paket</h3><p>Bandingkan paket les &amp; tryout, atur durasi, dan lihat total harganya.</p></li>
      <li class="reveal"><span class="step-no">2</span><h3>Chat via WhatsApp</h3><p>Data kamu otomatis terkirim ke admin — tinggal kirim pesan.</p></li>
      <li class="reveal"><span class="step-no">3</span><h3>Aktivasi akun</h3><p>Setelah pembayaran terkonfirmasi, akun portal dan tryout langsung aktif.</p></li>
      <li class="reveal"><span class="step-no">4</span><h3>Belajar &amp; berkembang</h3><p>Absen, materi, tugas, tryout, dan nilai — semua ada di dashboard.</p></li>
    </ol>
  </div>
</section>

<!-- ================= PORTAL ================= -->
<section class="section section-dark">
  <div class="container portal-showcase">
    <div class="reveal">
      <span class="eyebrow eyebrow-light">Portal belajar Hakuna</span>
      <h2>Bukan cuma les — ada dashboard yang bikin belajar teratur.</h2>
      <p class="light-text">Murid dan tutor punya dashboard sendiri. Masuk cepat dengan akun Google, semuanya real-time.</p>
      <div class="tabs tabs-dark" data-tabs>
        <button class="tab active" data-tab="pt-murid">Untuk Murid</button>
        <button class="tab" data-tab="pt-tutor">Untuk Tutor</button>
      </div>
      <div class="tab-panel active" id="pt-murid">
        <ul class="feature-list">
          <li><b>Absen online</b> dengan kode dari tutor</li>
          <li><b>Materi</b> PDF, link, dan video kapan saja</li>
          <li><b>Tugas</b> — kumpulkan jawaban &amp; file, lihat nilai</li>
          <li><b>Tryout</b> bertimer dengan skor &amp; pembahasan</li>
        </ul>
      </div>
      <div class="tab-panel" id="pt-tutor">
        <ul class="feature-list">
          <li><b>Buka sesi</b> &amp; absensi murid sekali klik</li>
          <li><b>Unggah materi</b> dan buat tugas</li>
          <li><b>Beri nilai</b> &amp; feedback pribadi</li>
          <li><b>Pantau</b> kehadiran dan progres kelas</li>
        </ul>
      </div>
      <div class="hero-actions">
        <a class="btn btn-accent btn-large" href="<?= e(url('login.php')) ?>">Masuk dengan Google</a>
        <a class="btn btn-ghost-light btn-large" href="<?= e(url('register.php')) ?>">Buat akun murid</a>
      </div>
    </div>
    <div class="portal-visual reveal" aria-hidden="true">
      <div class="pv-card pv-1"><b>Absensi</b><div class="pv-bars"><i style="--h:60%"></i><i style="--h:85%"></i><i style="--h:70%"></i><i style="--h:95%"></i><i style="--h:80%"></i></div><small>Kehadiran 92%</small></div>
      <div class="pv-card pv-2"><b>Tugas minggu ini</b><ul><li>✅ Persamaan Linear</li><li>⏳ Proyek Luas Ruangan</li><li>⏳ Latihan PK</li></ul></div>
      <div class="pv-card pv-3"><b>Skor Tryout</b><div class="pv-score">86</div><small>naik 8 poin 📈</small></div>
    </div>
  </div>
</section>

<!-- ================= STATS ================= -->
<section class="section section-small">
  <div class="container stats-row">
    <div class="reveal"><strong data-count="<?= max($stats['students'], 1) ?>">0</strong><span>Murid terdaftar di portal</span></div>
    <div class="reveal"><strong data-count="<?= max($stats['tutors'], 1) ?>">0</strong><span>Tutor aktif</span></div>
    <div class="reveal"><strong data-count="<?= $stats['classes'] ?>">0</strong><span>Kelas berjalan</span></div>
    <div class="reveal"><strong data-count="<?= $stats['tryouts'] ?>">0</strong><span>Tryout dikerjakan</span></div>
  </div>
</section>

<!-- ================= TUTOR ================= -->
<section class="section section-tint">
  <div class="container">
    <div class="section-heading reveal">
      <div>
        <span class="eyebrow">Belajar bersama mereka</span>
        <h2>Tutor lulusan kampus terbaik, ramah dan sabar.</h2>
        <p class="section-subtitle">Mereka tahu rasanya jadi pelajar — dan tahu cara membuat materi sulit jadi mudah.</p>
      </div>
      <div class="carousel-nav"><button class="icon-btn" data-carousel-prev="tutorCarousel" aria-label="Sebelumnya">←</button><button class="icon-btn" data-carousel-next="tutorCarousel" aria-label="Berikutnya">→</button></div>
    </div>
    <div class="carousel" id="tutorCarousel">
      <?php foreach ($tutors as $t): ?>
        <article class="tutor-slide">
          <div class="tutor-top"><?= tutor_avatar($t) ?><span class="tag"><?= e($t['subject']) ?></span></div>
          <h3><?= e($t['name']) ?></h3>
          <p class="univ">🎓 <?= e($t['university']) ?></p>
          <p class="muted"><?= e($t['major']) ?></p>
          <p class="achv">⭐ <?= e((string) $t['achievement']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="center-text"><a class="btn btn-outline" href="<?= e(url('tutors.php')) ?>">Lihat semua tutor →</a></p>
    <?php if (SHOW_SAMPLE_NOTICE): ?><p class="fine-print center-text">Profil tutor pada tampilan ini adalah contoh. Admin dapat menggantinya di panel admin → Tutor.</p><?php endif; ?>
  </div>
</section>

<?php $photos = gallery_photos(); if ($photos): ?>
<!-- ================= GALERI ================= -->
<section class="section">
  <div class="container">
    <div class="section-heading center reveal">
      <span class="eyebrow">Galeri</span>
      <h2>Suasana belajar di Hakuna.</h2>
      <p class="section-subtitle">Klik foto untuk memperbesar.</p>
    </div>
    <div class="gallery">
      <?php foreach ($photos as $i => $ph): ?>
        <button type="button" class="gallery-item reveal" data-lightbox="<?= e(url($ph)) ?>" aria-label="Perbesar foto <?= $i + 1 ?>">
          <img src="<?= e(url($ph)) ?>" alt="Foto kegiatan belajar <?= $i + 1 ?>" loading="lazy">
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ================= FAQ ================= -->
<section class="section">
  <div class="container faq-layout">
    <div class="reveal">
      <span class="eyebrow">Pertanyaan umum</span>
      <h2>Masih ragu? Ini jawabannya.</h2>
      <p class="section-subtitle">Tidak menemukan jawabanmu? Tanya langsung ke admin, biasanya dibalas dalam hitungan menit.</p>
      <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e(whatsapp_url('Halo Hakuna Matata Course, saya punya pertanyaan.')) ?>">💬 Tanya via WhatsApp</a>
    </div>
    <div class="accordion reveal">
      <?php foreach ($faqs as $i => [$q, $a]): ?>
        <div class="acc-item">
          <button class="acc-head" aria-expanded="false"><?= e($q) ?><span class="acc-ico">+</span></button>
          <div class="acc-body"><p><?= e($a) ?></p></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ================= CTA ================= -->
<section class="cta-band">
  <div class="container cta-inner reveal">
    <div>
      <h2>Yuk, mulai langkah kecil hari ini.</h2>
      <p>Konsultasikan jenjang, target, dan jadwal belajarmu dengan tim Hakuna. Gratis!</p>
    </div>
    <div class="cta-actions">
      <a class="btn btn-white btn-large" href="<?= e(url('packages.php')) ?>">Pilih paket</a>
      <a class="btn btn-ghost-light btn-large" target="_blank" rel="noopener" href="<?= e(whatsapp_url('Halo Hakuna Matata Course, saya ingin konsultasi program belajar.')) ?>">Chat WhatsApp</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
