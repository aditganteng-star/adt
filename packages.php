<?php
/** Paket les & tryout — interaktif, checkout langsung ke WhatsApp. */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Paket Les & Tryout';
$active    = 'packages';
$views     = page_counter();

$packages = rows('SELECT * FROM packages WHERE is_active = 1 ORDER BY sort_order');

// Pencarian paket (method GET) — diproses di server; JavaScript hanya mempercepat tampilan.
$keyword = trim((string) ($_GET['q'] ?? ''));
$resultNote = '';
if ($keyword !== '') {
    $needle = mb_strtolower($keyword);
    $found = array_values(array_filter($packages, fn($p) => str_contains(mb_strtolower($p['name'] . ' ' . $p['description'] . ' ' . $p['level'] . ' ' . $p['category']), $needle)));
    $resultNote = count($found) . ' paket ditemukan untuk kata kunci “' . $keyword . '”.';
    $packages = $found;
}
$allPackages = rows('SELECT * FROM packages WHERE is_active = 1 ORDER BY sort_order');

// Mini-tryout publik (dikerjakan di browser, tanpa login).
$demo = row('SELECT * FROM tryouts WHERE is_public = 1 AND is_published = 1 ORDER BY id LIMIT 1');
$demoQuestions = [];
if ($demo) {
    foreach (rows('SELECT * FROM tryout_questions WHERE tryout_id = ? ORDER BY sort_order', [$demo['id']]) as $q) {
        $demoQuestions[] = [
            'subtest' => $q['subtest'], 'q' => $q['question'],
            'o' => [$q['option_a'], $q['option_b'], $q['option_c'], $q['option_d']],
            'c' => ord($q['correct']) - 65, 'why' => $q['explanation'],
        ];
    }
}

$jsPackages = array_map(fn($p) => [
    'id' => (int) $p['id'], 'slug' => $p['slug'], 'name' => $p['name'], 'level' => $p['level'], 'category' => $p['category'],
    'price' => (int) $p['price'], 'old' => $p['old_price'] ? (int) $p['old_price'] : null, 'unit' => $p['unit'],
], $packages);

$catLabels = ['les' => 'Les Reguler & Privat', 'tryout' => 'Tryout Online', 'utbk' => 'Program UTBK'];

require __DIR__ . '/includes/site_header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Paket belajar</span>
    <h1>Pilih paket, <span class="hl">daftar langsung</span> lewat WhatsApp.</h1>
    <p>Atur durasi, lihat total harga, lalu klik pilih — data kamu terkirim otomatis ke admin tanpa ribet.</p>
  </div>
</section>

<section class="section">
  <div class="container">

    <div class="filter-bar" id="pkgFilter">
      <div class="seg" role="group" aria-label="Kategori paket">
        <button type="button" class="seg-btn active" data-cat="">Semua</button>
        <?php foreach ($catLabels as $k => $label): ?><button type="button" class="seg-btn" data-cat="<?= e($k) ?>"><?= e($label) ?></button><?php endforeach; ?>
      </div>
      <div class="field"><label for="pkgLevel">Jenjang</label>
        <select id="pkgLevel"><option value="">Semua</option><option>SD</option><option>SMP</option><option>SMA</option><option>UTBK</option></select>
      </div>
      <form class="field grow search-form" method="get" action="packages.php"><label for="pkgSearch">Cari paket</label><div class="search-row"><input id="pkgSearch" name="q" type="search" value="<?= e($keyword) ?>" placeholder="Contoh: tryout, privat…"><button class="btn btn-primary btn-small" type="submit">Cari</button></div></form>
    </div>

    <?php if ($resultNote): ?><p class="result-note" role="status">🔎 <?= e($resultNote) ?> <a class="text-link" href="packages.php">Tampilkan semua</a></p><?php endif; ?>

    <div class="duration-bar">
      <span>Durasi langganan (paket bulanan):</span>
      <div class="seg" role="group" aria-label="Durasi" id="durationSeg">
        <button type="button" class="seg-btn active" data-months="1">1 bulan</button>
        <button type="button" class="seg-btn" data-months="3">3 bulan <em>hemat 5%</em></button>
        <button type="button" class="seg-btn" data-months="6">6 bulan <em>hemat 10%</em></button>
      </div>
    </div>

    <div class="package-grid" id="pkgGrid">
      <?php foreach ($packages as $p): $features = lines($p['features']); $featured = $p['badge'] === 'Terlaris'; ?>
        <article class="package-card <?= $featured ? 'featured' : '' ?> reveal"
                 data-id="<?= (int) $p['id'] ?>" data-slug="<?= e($p['slug']) ?>" data-cat="<?= e($p['category']) ?>" data-level="<?= e($p['level']) ?>"
                 data-search="<?= e(mb_strtolower($p['name'] . ' ' . $p['description'] . ' ' . $p['level'] . ' ' . $p['category'])) ?>">
          <?php if ($p['badge']): ?><span class="badge"><?= e($p['badge']) ?></span><?php endif; ?>
          <span class="pill"><?= e($p['level']) ?> · <?= e($catLabels[$p['category']] ?? $p['category']) ?></span>
          <h3><?= e($p['name']) ?></h3>
          <p class="pkg-desc"><?= e($p['description']) ?></p>
          <div class="price">
            <?php if ($p['old_price']): ?><s class="price-old"></s><?php endif; ?>
            <strong class="price-now"></strong><span class="price-unit"></span>
          </div>
          <small class="price-note"></small>
          <ul class="check-list"><?php foreach ($features as $f): ?><li><?= e($f) ?></li><?php endforeach; ?></ul>
          <button class="btn <?= $featured ? 'btn-primary' : 'btn-outline' ?> btn-full" type="button" data-choose="<?= (int) $p['id'] ?>">Pilih paket →</button>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="empty-state" id="pkgEmpty" hidden>Paket tidak ditemukan. Coba ubah filter atau kata kunci.</div>

    <h2 class="table-title">Tabel perbandingan paket</h2>
    <div class="table-wrap">
      <table class="data-table">
        <caption class="sr-only">Perbandingan seluruh paket Hakuna</caption>
        <thead><tr><th>Paket</th><th>Jenjang</th><th>Kategori</th><th>Harga</th><th>Isi paket</th></tr></thead>
        <tbody>
        <?php foreach ($allPackages as $ap): ?>
          <tr>
            <td><b><?= e($ap['name']) ?></b><?= $ap['badge'] ? ' <span class="badge-inline">' . e($ap['badge']) . '</span>' : '' ?></td>
            <td><?= e($ap['level']) ?></td>
            <td><?= e($catLabels[$ap['category']] ?? $ap['category']) ?></td>
            <td><b><?= rupiah((int) $ap['price']) ?></b> <small>/<?= e($ap['unit']) ?></small></td>
            <td><?= e(implode(' · ', array_slice(lines($ap['features']), 0, 3))) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if (SHOW_SAMPLE_NOTICE): ?>
      <div class="notice"><b>Catatan pemilik website:</b> harga dan isi paket ini adalah <u>contoh</u>. Ubah lewat <b>Dashboard Admin → Paket</b> sesuai daftar harga resmi Hakuna.</div>
    <?php endif; ?>
  </div>
</section>

<!-- ============ MINI TRYOUT GRATIS ============ -->
<?php if ($demoQuestions): ?>
<section class="section section-tint" id="coba-tryout">
  <div class="container">
    <div class="section-heading center reveal">
      <span class="eyebrow">Coba dulu, gratis!</span>
      <h2><?= e($demo['title']) ?></h2>
      <p class="section-subtitle"><?= e((string) $demo['description']) ?></p>
    </div>
    <div class="quiz-card reveal" id="miniQuiz" data-questions="<?= e(json_encode($demoQuestions, JSON_UNESCAPED_UNICODE)) ?>" data-minutes="<?= (int) $demo['duration_min'] ?>">
      <div class="quiz-start">
        <div class="quiz-emoji">📝</div>
        <h3><?= count($demoQuestions) ?> soal · <?= (int) $demo['duration_min'] ?> menit</h3>
        <p>Pilih jawaban, lihat skor, dan baca pembahasan di akhir. Tidak perlu login.</p>
        <button class="btn btn-primary btn-large" type="button" id="quizStart">Mulai tryout →</button>
      </div>
      <div class="quiz-run" hidden>
        <div class="quiz-top"><span id="quizNo"></span><span class="quiz-timer" id="quizTimer"></span></div>
        <div class="quiz-progress"><i id="quizBar"></i></div>
        <small class="quiz-subtest" id="quizSub"></small>
        <h3 id="quizQ"></h3>
        <div class="quiz-options" id="quizOpts"></div>
        <div class="quiz-nav"><button class="btn btn-soft" type="button" id="quizPrev">← Sebelumnya</button><button class="btn btn-primary" type="button" id="quizNext">Lanjut →</button></div>
      </div>
      <div class="quiz-result" hidden>
        <div class="score-ring" id="quizRing"><b id="quizScore">0</b><small>/ 100</small></div>
        <h3 id="quizMsg"></h3>
        <p id="quizDetail"></p>
        <div class="quiz-review" id="quizReview"></div>
        <div class="hero-actions">
          <a class="btn btn-primary" href="#pkgGrid" data-scroll-cat="tryout">Beli paket tryout lengkap</a>
          <button class="btn btn-soft" type="button" id="quizRetry">Ulangi</button>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============ CARA BAYAR ============ -->
<section class="section">
  <div class="container">
    <div class="section-heading center reveal"><span class="eyebrow">Cara pembayaran</span><h2>Praktis &amp; transparan.</h2></div>
    <ol class="steps">
      <li class="reveal"><span class="step-no">1</span><h3>Pilih paket</h3><p>Tentukan paket dan durasi, total harga langsung terlihat.</p></li>
      <li class="reveal"><span class="step-no">2</span><h3>Kirim via WhatsApp</h3><p>Isi data singkat — pesan pendaftaran otomatis siap kamu kirim.</p></li>
      <li class="reveal"><span class="step-no">3</span><h3>Konfirmasi pembayaran</h3><p>Admin memberi instruksi pembayaran dan jadwal kelas.</p></li>
      <li class="reveal"><span class="step-no">4</span><h3>Akses aktif</h3><p>Login ke portal — kelas, materi, dan tryout siap dipakai.</p></li>
    </ol>
  </div>
</section>

<!-- ============ MODAL CHECKOUT ============ -->
<div class="modal-backdrop" id="checkoutModal" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="coTitle">
    <button class="modal-close" type="button" data-modal-close aria-label="Tutup">×</button>
    <div id="coForm">
      <span class="eyebrow">Daftar paket</span>
      <h2 id="coTitle">—</h2>
      <div class="co-summary">
        <div><span>Paket</span><b id="coPkg">—</b></div>
        <div id="coDurRow"><span>Durasi</span><b id="coDur">—</b></div>
        <div class="co-total"><span>Total</span><b id="coTotal">—</b></div>
      </div>
      <form class="stack-form" id="checkoutForm" novalidate>
        <input type="hidden" name="package_id"><input type="hidden" name="duration"><input type="hidden" name="source" value="paket">
        <div class="field"><label for="coName">Nama lengkap *</label><input id="coName" name="name" required autocomplete="name" value="<?= e(current_user()['name'] ?? '') ?>"></div>
        <div class="two-col">
          <div class="field"><label for="coPhone">No. WhatsApp *</label><input id="coPhone" name="phone" type="tel" required autocomplete="tel" placeholder="0812xxxxxxx"></div>
          <div class="field"><label for="coEmail">Email akun portal</label><input id="coEmail" name="email" type="email" value="<?= e(current_user()['email'] ?? '') ?>" placeholder="opsional"></div>
        </div>
        <div class="field"><label for="coNote">Catatan (kelas / jadwal yang diinginkan)</label><textarea id="coNote" name="note" rows="2" placeholder="Contoh: kelas 9, jadwal sore"></textarea></div>
        <small class="form-msg" role="alert"></small>
        <button class="btn btn-primary btn-full btn-large" type="submit">Lanjut ke WhatsApp →</button>
        <small class="fine-print">Dengan melanjutkan, data di atas disimpan dan dikirim ke admin Hakuna untuk proses pendaftaran. Belum ada pembayaran pada langkah ini.</small>
      </form>
    </div>
    <div id="coDone" hidden>
      <div class="success-panel">
        <span class="check-big">✓</span>
        <h3>Pendaftaranmu tercatat!</h3>
        <p>Tinggal satu langkah lagi: kirim pesan ke admin lewat WhatsApp supaya kami bisa memproses.</p>
        <a class="btn btn-primary btn-full btn-large" id="coWa" target="_blank" rel="noopener" href="#">Buka WhatsApp →</a>
      </div>
    </div>
  </div>
</div>

<script>window.HAKUNA_PACKAGES = <?= json_encode($jsPackages, JSON_UNESCAPED_UNICODE) ?>;</script>
<script defer src="<?= e(url('assets/js/packages.js')) ?>"></script>
<?php require __DIR__ . '/includes/site_footer.php'; ?>
