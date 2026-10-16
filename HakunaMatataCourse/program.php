<?php
/** Pengenalan program les per jenjang. */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Program Belajar';
$active    = 'program';
$views     = page_counter();

$programs = [
    'sd' => [
        'label' => 'SD', 'emoji' => '🌱', 'color' => 'peach', 'title' => 'Fondasi kuat sejak awal',
        'desc' => 'Adik-adik belajar konsep dasar lewat cerita, permainan, dan latihan bertahap sehingga belajar terasa seru, bukan beban.',
        'subjects' => ['Calistung', 'Matematika', 'IPA', 'Bahasa Indonesia', 'Bahasa Inggris Dasar'],
        'flow' => ['Asesmen kemampuan awal', 'Belajar konsep dengan alat peraga', 'Latihan soal bertahap', 'Laporan perkembangan ke orang tua'],
        'schedule' => '2x seminggu · 90 menit · kelas kecil maks. 6 murid',
    ],
    'smp' => [
        'label' => 'SMP', 'emoji' => '🚀', 'color' => 'lilac', 'title' => 'Konsep kuat, nilai naik',
        'desc' => 'Menghadapi mapel yang makin beragam dengan pembahasan konsep, bank soal, dan persiapan ujian sekolah.',
        'subjects' => ['Matematika', 'IPA', 'Bahasa Inggris', 'Bahasa Indonesia', 'IPS'],
        'flow' => ['Pemetaan materi sekolah', 'Pendalaman konsep & rumus', 'Bank soal + pembahasan', 'Tryout bulanan & evaluasi'],
        'schedule' => '2x seminggu · 90 menit · kelas kecil maks. 8 murid',
    ],
    'sma' => [
        'label' => 'SMA', 'emoji' => '📘', 'color' => 'mint', 'title' => 'Target jelas, hasil terukur',
        'desc' => 'Penguatan mata pelajaran IPA/IPS dengan strategi mengerjakan soal dan konsultasi arah jurusan kuliah.',
        'subjects' => ['Matematika', 'Fisika', 'Kimia', 'Biologi', 'Ekonomi', 'Bahasa Inggris'],
        'flow' => ['Tentukan target nilai & jurusan', 'Pemetaan topik lemah', 'Pembahasan intensif + latihan', 'Evaluasi dan perbaikan strategi'],
        'schedule' => '2x seminggu · 90 menit · kelas kecil maks. 8 murid',
    ],
    'utbk' => [
        'label' => 'UTBK', 'emoji' => '🎯', 'color' => 'sun', 'title' => 'Siap tembus PTN impian',
        'desc' => 'Latihan terstruktur TPS, literasi, dan penalaran dengan tryout rutin serta mentoring pribadi.',
        'subjects' => ['Penalaran Umum', 'Penalaran Kuantitatif', 'Pemahaman Bacaan', 'Literasi B. Inggris', 'Saintek / Soshum'],
        'flow' => ['Diagnostik lewat tryout awal', 'Kelas materi & trik soal', 'Tryout mingguan bertimer', 'Evaluasi + strategi pilihan jurusan'],
        'schedule' => '2x seminggu · 90 menit · tryout tiap akhir pekan',
    ],
];

require __DIR__ . '/includes/site_header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Program Hakuna</span>
    <h1>Temukan cara belajar <span class="hl">yang cocok</span> untukmu.</h1>
    <p>Setiap jenjang punya tantangan berbeda. Pilih program sesuai kebutuhan, lalu lanjutkan ke paket dan daftar via WhatsApp.</p>
    <div class="jump-chips">
      <?php foreach ($programs as $id => $p): ?><a class="chip" href="#<?= e($id) ?>"><?= $p['emoji'] ?> <?= e($p['label']) ?></a><?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container program-list">
    <?php foreach ($programs as $id => $p):
        $levelPackages = rows('SELECT slug, name, price, unit FROM packages WHERE level = ? AND is_active = 1 ORDER BY sort_order', [$p['label']]);
    ?>
      <article class="program-detail reveal" id="<?= e($id) ?>">
        <img class="pd-img" src="<?= e(url('assets/img/program-' . $id . '.svg')) ?>" alt="Ilustrasi program <?= e($p['label']) ?>" width="120" height="120" loading="lazy">
        <div class="pd-main">
          <span class="pill"><?= e($p['label']) ?></span>
          <h2><?= e($p['title']) ?></h2>
          <p><?= e($p['desc']) ?></p>

          <h4>Mata pelajaran <small class="muted">(klik untuk menandai minatmu)</small></h4>
          <div class="chip-row" data-chip-select>
            <?php foreach ($p['subjects'] as $s): ?><button type="button" class="chip" data-value="<?= e($s) ?>"><?= e($s) ?></button><?php endforeach; ?>
          </div>

          <h4>Alur belajar</h4>
          <ol class="flow">
            <?php foreach ($p['flow'] as $step): ?><li><?= e($step) ?></li><?php endforeach; ?>
          </ol>
          <p class="schedule-line">🗓️ <?= e($p['schedule']) ?></p>
        </div>
        <aside class="pd-side">
          <h4>Paket tersedia</h4>
          <?php foreach ($levelPackages as $lp): ?>
            <a class="pd-package" href="<?= e(url('packages.php?pilih=' . $lp['slug'])) ?>">
              <span><?= e($lp['name']) ?></span><b><?= rupiah((int) $lp['price']) ?><small>/<?= e($lp['unit']) ?></small></b>
            </a>
          <?php endforeach; ?>
          <a class="btn btn-primary btn-full" href="<?= e(url('packages.php?level=' . $p['label'])) ?>">Lihat paket <?= e($p['label']) ?> →</a>
          <a class="btn btn-soft btn-full" data-wa-subjects target="_blank" rel="noopener" data-wa-base="Halo Hakuna, saya tertarik program <?= e($p['label']) ?>" href="<?= e(whatsapp_url('Halo Hakuna, saya tertarik program ' . $p['label'] . '.')) ?>">💬 Tanya program ini</a>
        </aside>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="section section-tint" id="ringkasan">
  <div class="container">
    <div class="section-heading center reveal">
      <span class="eyebrow">Ringkasan</span>
      <h2>Perbandingan program sekilas.</h2>
    </div>
    <div class="table-wrap reveal">
      <table class="data-table">
        <caption class="sr-only">Perbandingan program les Hakuna per jenjang</caption>
        <thead><tr><th>Jenjang</th><th>Mata pelajaran utama</th><th>Jadwal</th><th>Biaya mulai dari</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($programs as $id => $p):
            $min = val('SELECT MIN(price) FROM packages WHERE level = ? AND is_active = 1', [$p['label']]); ?>
          <tr>
            <td><b><?= $p['emoji'] ?> <?= e($p['label']) ?></b></td>
            <td><?= e(implode(', ', array_slice($p['subjects'], 0, 3))) ?>, dll.</td>
            <td><?= e($p['schedule']) ?></td>
            <td><?= $min ? '<b>' . rupiah((int) $min) . '</b>' : '—' ?></td>
            <td><a class="text-link" href="<?= e(url('packages.php?level=' . $p['label'])) ?>">Lihat paket →</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-heading center reveal">
      <span class="eyebrow">Kenapa Hakuna?</span>
      <h2>Belajar teratur dengan bantuan teknologi.</h2>
    </div>
    <div class="why-grid">
      <div class="why reveal"><span>🧑‍🏫</span><h3>Tutor pilihan</h3><p>Lulusan PTN yang sabar dan paham cara menjelaskan.</p></div>
      <div class="why reveal"><span>📱</span><h3>Portal belajar</h3><p>Absen, materi, tugas, dan nilai ada di satu dashboard.</p></div>
      <div class="why reveal"><span>📝</span><h3>Tryout bertimer</h3><p>Latihan seperti ujian asli, skor dan pembahasan instan.</p></div>
      <div class="why reveal"><span>👨‍👩‍👧</span><h3>Kelas kecil</h3><p>Maksimal 8 murid agar tiap anak diperhatikan.</p></div>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-inner reveal">
    <div><h2>Belum yakin memilih?</h2><p>Ceritakan jenjang dan targetmu, kami bantu pilihkan program yang pas.</p></div>
    <div class="cta-actions"><a class="btn btn-white btn-large" href="<?= e(url('contact.php')) ?>">Konsultasi gratis</a></div>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
