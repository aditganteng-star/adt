<?php
/** Kontak & konsultasi (form diproses PHP, disimpan, lalu lanjut ke WhatsApp). */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Kontak & Konsultasi';
$active    = 'contact';
$views     = page_counter();

$values = ['name' => '', 'phone' => '', 'email' => '', 'interest' => '', 'message' => ''];
$errors = [];
$waLink = null;
$phone = '';

if (is_post()) {
    verify_csrf();
    foreach ($values as $k => $_) $values[$k] = mb_substr(post($k), 0, $k === 'message' ? 500 : 190);
    $phone = preg_replace('/[^\d+]/', '', $values['phone']) ?? '';

    if (mb_strlen($values['name']) < 2) $errors[] = 'Nama wajib diisi.';
    if (strlen($phone) < 9 || strlen($phone) > 16) $errors[] = 'Nomor WhatsApp belum valid.';
    if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email belum valid.';
    if ($values['interest'] === '') $errors[] = 'Pilih program yang diminati.';
    if ($values['message'] === '') $errors[] = 'Ceritakan kebutuhan belajarmu singkat saja.';

    if (!$errors) {
        $id = insert('orders', [
            'package_name' => 'Konsultasi: ' . $values['interest'], 'name' => $values['name'], 'phone' => $phone,
            'email' => $values['email'] ?: null, 'note' => $values['message'], 'source' => 'kontak',
        ]);
        $waLink = whatsapp_url("Halo Hakuna Matata Course!\nNama: {$values['name']}\nWhatsApp: $phone\nProgram: {$values['interest']}\nKebutuhan: {$values['message']}\n(Ref #$id)");
    }
}

require __DIR__ . '/includes/site_header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Kami siap membantu</span>
    <h1>Masih punya pertanyaan? <span class="hl">Yuk, ngobrol dulu.</span></h1>
    <p>Isi formulir, lalu lanjutkan percakapan dengan tim Hakuna lewat WhatsApp.</p>
  </div>
</section>

<section class="section">
  <div class="container contact-layout">
    <div class="contact-info reveal">
      <h2>Mulai dari kebutuhanmu.</h2>
      <p>Ceritakan jenjang, mata pelajaran, atau target yang ingin dicapai. Kami bantu menjelaskan pilihan program dan jadwal.</p>
      <div class="contact-item"><span>📍</span><div><b>Lokasi</b><p><?= e(SITE_CITY) ?></p></div></div>
      <div class="contact-item"><span>💬</span><div><b>WhatsApp</b><p><a class="text-link" target="_blank" rel="noopener" href="<?= e(whatsapp_url('Halo Hakuna Matata Course!')) ?>">Chat langsung admin →</a></p></div></div>
      <div class="contact-item"><span>✉️</span><div><b>Email</b><p><?= e(SITE_EMAIL) ?></p></div></div>
      <div class="contact-item"><span>🕒</span><div><b>Jam layanan</b><p>Senin – Sabtu, 08.00 – 20.00 WIB</p></div></div>
      <img class="contact-img" src="<?= e(url('assets/img/hero-study.svg')) ?>" alt="Ilustrasi belajar online bersama Hakuna" width="480" height="360" loading="lazy">
    </div>

    <div class="form-card reveal">
      <h2>Form konsultasi</h2>
      <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div><?php endif; ?>

      <?php if ($waLink): ?>
        <div class="success-panel">
          <span class="check-big">✓</span>
          <h3>Konsultasi Diterima</h3>
          <p>Terima kasih, <?= e($values['name']) ?>! Berikut ringkasan permintaanmu:</p>
          <table class="summary-table">
            <tr><th>Nama</th><td><?= e($values['name']) ?></td></tr>
            <tr><th>No. WhatsApp</th><td><?= e($phone) ?></td></tr>
            <?php if ($values['email'] !== ''): ?><tr><th>Email</th><td><?= e($values['email']) ?></td></tr><?php endif; ?>
            <tr><th>Program</th><td><?= e($values['interest']) ?></td></tr>
            <tr><th>Kebutuhan belajar</th><td><?= e($values['message']) ?></td></tr>
            <tr><th>Waktu</th><td><?= e(tanggal(date('Y-m-d H:i:s'), true)) ?> WIB</td></tr>
          </table>
          <p>Klik tombol di bawah untuk melanjutkan chat dengan admin di WhatsApp.</p>
          <a class="btn btn-primary btn-full btn-large" target="_blank" rel="noopener" href="<?= e($waLink) ?>">Lanjut ke WhatsApp →</a>
        </div>
      <?php else: ?>
        <form method="post" class="stack-form" novalidate>
          <?= csrf_field() ?>
          <div class="field"><label for="name">Nama lengkap *</label><input id="name" name="name" required value="<?= e($values['name']) ?>" placeholder="Nama kamu"></div>
          <div class="two-col">
            <div class="field"><label for="phone">No. WhatsApp *</label><input id="phone" name="phone" type="tel" required value="<?= e($values['phone']) ?>" placeholder="0812xxxxxxx"></div>
            <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="<?= e($values['email']) ?>" placeholder="opsional"></div>
          </div>
          <div class="field"><label for="interest">Program yang diminati *</label>
            <select id="interest" name="interest" required>
              <option value="">Pilih program</option>
              <?php foreach (['Les SD', 'Les SMP', 'Les SMA', 'Tryout UTBK', 'Persiapan UTBK', 'Les Privat', 'Lainnya'] as $opt): ?>
                <option <?= $values['interest'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label for="message">Kebutuhan belajar *</label><textarea id="message" name="message" rows="4" required placeholder="Contoh: ingin memperkuat Matematika kelas 9"><?= e($values['message']) ?></textarea></div>
          <button class="btn btn-primary btn-full btn-large" type="submit">Kirim konsultasi →</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
