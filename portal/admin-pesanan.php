<?php
/** Admin: pesanan & konsultasi dari website; aktivasi akses setelah pembayaran. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

require_role('admin');

if (is_post()) {
    verify_csrf();
    try {
        $action = post('action');
        $order = row('SELECT * FROM orders WHERE id = ?', [(int) post('id')]);
        if (!$order) throw new RuntimeException('Pesanan tidak ditemukan.');

        if ($action === 'status') {
            $st = in_array(post('status'), ['baru', 'diproses', 'aktif', 'batal'], true) ? post('status') : 'baru';
            run('UPDATE orders SET status = ? WHERE id = ?', [$st, $order['id']]);
            flash('Status pesanan diperbarui.');
        } elseif ($action === 'activate') {
            $student = row("SELECT * FROM users WHERE email = ? AND role = 'student'", [mb_strtolower(post('student_email'))]);
            if (!$student) throw new RuntimeException('Akun murid dengan email itu belum ada. Minta murid mendaftar / login Google dulu.');
            $msg = [];
            if ($order['package_id']) {
                $pkg = row('SELECT * FROM packages WHERE id = ?', [$order['package_id']]);
                $exp = ($pkg && $pkg['unit'] === 'bulan') ? date('Y-m-d', strtotime('+' . (int) $order['duration'] . ' months')) : null;
                $ex = row('SELECT * FROM entitlements WHERE user_id = ? AND package_id = ?', [$student['id'], $order['package_id']]);
                if ($ex) run('UPDATE entitlements SET expires_at = ? WHERE id = ?', [$exp, $ex['id']]);
                else insert('entitlements', ['user_id' => $student['id'], 'package_id' => $order['package_id'], 'expires_at' => $exp]);
                $msg[] = 'akses paket aktif' . ($exp ? ' sampai ' . tanggal($exp) : '');
            }
            $cid = (int) post('class_id');
            if ($cid && !val('SELECT COUNT(*) FROM enrollments WHERE class_id = ? AND student_id = ?', [$cid, $student['id']])) {
                insert('enrollments', ['class_id' => $cid, 'student_id' => $student['id']]);
                $msg[] = 'didaftarkan ke kelas';
            }
            run("UPDATE orders SET status = 'aktif' WHERE id = ?", [$order['id']]);
            flash($student['name'] . ': ' . ($msg ? implode(', ', $msg) : 'status diaktifkan') . '.');
        } elseif ($action === 'delete') {
            run('DELETE FROM orders WHERE id = ?', [$order['id']]);
            flash('Pesanan dihapus.', 'info');
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('portal/admin-pesanan.php' . (isset($_GET['status']) ? '?status=' . urlencode((string) $_GET['status']) : ''));
}

$status = (string) ($_GET['status'] ?? '');
$orders = in_array($status, ['baru', 'diproses', 'aktif', 'batal'], true)
    ? rows('SELECT * FROM orders WHERE status = ? ORDER BY id DESC', [$status])
    : rows('SELECT * FROM orders ORDER BY id DESC LIMIT 200');
$classes = visible_classes(current_user());
$counts = [];
foreach (rows('SELECT status, COUNT(*) AS n FROM orders GROUP BY status') as $r) $counts[$r['status']] = (int) $r['n'];

portal_open('Pesanan & Konsultasi', 'pesanan');
?>
<div class="tabs link-tabs">
  <a class="tab <?= $status === '' ? 'active' : '' ?>" href="?">Semua</a>
  <?php foreach (['baru', 'diproses', 'aktif', 'batal'] as $s): ?><a class="tab <?= $status === $s ? 'active' : '' ?>" href="?status=<?= $s ?>"><?= ucfirst($s) ?> <em><?= $counts[$s] ?? 0 ?></em></a><?php endforeach; ?>
</div>

<section class="panel">
  <?php if (!$orders): ?><div class="empty-state small">Belum ada pesanan pada kategori ini.</div><?php endif; ?>
  <?php foreach ($orders as $o): ?>
    <article class="order-card">
      <div class="order-main">
        <div class="order-top"><b>#<?= (int) $o['id'] ?> · <?= e($o['name']) ?></b><?= status_pill($o['status']) ?></div>
        <p><?= e($o['package_name']) ?><?= $o['total'] ? ' · ' . ($o['duration'] > 1 ? (int) $o['duration'] . ' bln · ' : '') . '<b>' . rupiah((int) $o['total']) . '</b>' : '' ?></p>
        <small>📞 <?= e($o['phone']) ?><?= $o['email'] ? ' · ✉️ ' . e($o['email']) : '' ?> · <?= e(tanggal($o['created_at'], true)) ?> · sumber: <?= e($o['source']) ?></small>
        <?php if ($o['note']): ?><small class="order-note">“<?= e($o['note']) ?>”</small><?php endif; ?>
      </div>
      <div class="order-actions">
        <a class="btn btn-soft btn-small" target="_blank" rel="noopener" href="https://wa.me/<?= e(preg_replace('/^0/', '62', preg_replace('/\D/', '', $o['phone']) ?? '') ?? '') ?>?text=<?= rawurlencode('Halo ' . $o['name'] . ', terima kasih sudah mendaftar di Hakuna Matata Course. Berikut info pembayaran & jadwal untuk ' . $o['package_name'] . ' …') ?>">💬 Balas WA</a>
        <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
          <select name="status" onchange="this.form.submit()"><?php foreach (['baru', 'diproses', 'aktif', 'batal'] as $s): ?><option <?= $o['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></form>
        <details class="activate"><summary class="btn btn-primary btn-small">Aktifkan akses</summary>
          <form method="post" class="stack-form compact"><?= csrf_field() ?><input type="hidden" name="action" value="activate"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
            <input type="email" name="student_email" value="<?= e((string) $o['email']) ?>" placeholder="Email akun murid" required>
            <select name="class_id"><option value="0">— tanpa kelas —</option><?= class_options($classes) ?></select>
            <button class="btn btn-primary btn-small">Aktifkan</button></form></details>
        <form method="post" class="inline-form" data-confirm="Hapus pesanan ini?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>"><button class="link-danger">Hapus</button></form>
      </div>
    </article>
  <?php endforeach; ?>
</section>
<?php portal_close(); ?>
