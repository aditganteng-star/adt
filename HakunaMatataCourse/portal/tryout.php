<?php
/** Tryout: murid memilih & melihat riwayat; tutor/admin mengelola. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

$u = require_login();

/* ---------------- Aksi staf ---------------- */
if (is_post() && is_staff($u)) {
    verify_csrf();
    try {
        $action = post('action');
        if ($action === 'create') {
            if (mb_strlen(post('title')) < 3) throw new RuntimeException('Judul tryout minimal 3 karakter.');
            $pkg = (int) post('package_id') ?: null;
            if ($pkg && !val('SELECT COUNT(*) FROM packages WHERE id = ?', [$pkg])) $pkg = null;
            $id = insert('tryouts', [
                'title' => mb_substr(post('title'), 0, 190), 'category' => mb_substr(post('category') ?: 'UTBK', 0, 30),
                'description' => mb_substr(post('description'), 0, 500) ?: null, 'duration_min' => max(5, min(180, (int) post('duration_min', '30'))),
                'package_id' => $pkg, 'is_public' => 0, 'is_published' => 0, 'created_by' => $u['id'],
            ]);
            flash('Tryout dibuat. Tambahkan soal lalu publikasikan.');
            redirect('portal/tryout-edit.php?id=' . $id);
        }
        if ($action === 'toggle' || $action === 'delete') {
            $t = row('SELECT * FROM tryouts WHERE id = ?', [(int) post('id')]);
            if (!$t || ($u['role'] !== 'admin' && (int) $t['created_by'] !== (int) $u['id'])) throw new RuntimeException('Kamu hanya bisa mengelola tryout buatanmu sendiri.');
            if ($action === 'toggle') {
                run('UPDATE tryouts SET is_published = ? WHERE id = ?', [$t['is_published'] ? 0 : 1, $t['id']]);
                flash($t['is_published'] ? 'Tryout disembunyikan dari murid.' : 'Tryout dipublikasikan.', 'info');
            } else {
                run('DELETE FROM tryout_questions WHERE tryout_id = ?', [$t['id']]);
                run('DELETE FROM tryout_attempts WHERE tryout_id = ?', [$t['id']]);
                run('DELETE FROM tryouts WHERE id = ?', [$t['id']]);
                flash('Tryout dihapus.', 'info');
            }
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('portal/tryout.php');
}

portal_open($u['role'] === 'student' ? 'Tryout' : 'Kelola Tryout', 'tryout');

if ($u['role'] === 'student'):
    $tryouts = rows('SELECT t.*, p.name AS package_name, p.slug AS package_slug,
        (SELECT COUNT(*) FROM tryout_questions q WHERE q.tryout_id = t.id) AS q_count
        FROM tryouts t LEFT JOIN packages p ON p.id = t.package_id WHERE t.is_published = 1 ORDER BY t.package_id IS NOT NULL, t.id');
    $attempts = rows('SELECT a.*, t.title FROM tryout_attempts a JOIN tryouts t ON t.id = a.tryout_id WHERE a.student_id = ? AND a.finished_at IS NOT NULL ORDER BY a.id DESC', [$u['id']]);
    $best = [];
    foreach ($attempts as $a) $best[(int) $a['tryout_id']] = max($best[(int) $a['tryout_id']] ?? 0, (int) $a['score']);
?>
  <div class="grid-cards">
    <?php foreach ($tryouts as $t):
        $ok = has_entitlement($u, $t['package_id'] ? (int) $t['package_id'] : null); ?>
      <article class="tryout-card <?= $ok ? '' : 'locked' ?>">
        <div class="tc-top"><span class="pill"><?= e($t['category']) ?></span><?= $t['package_id'] ? ($ok ? '<span class="status status-ok">Aktif</span>' : '<span class="status status-warn">🔒 Berbayar</span>') : '<span class="status status-info">Gratis</span>' ?></div>
        <h4><?= e($t['title']) ?></h4>
        <p><?= e((string) $t['description']) ?></p>
        <div class="meta-row"><span>❓ <?= (int) $t['q_count'] ?> soal</span><span>⏱ <?= (int) $t['duration_min'] ?> menit</span><?php if (isset($best[(int) $t['id']])): ?><span>🏆 Terbaik <?= $best[(int) $t['id']] ?></span><?php endif; ?></div>
        <?php if (!$t['q_count']): ?>
          <button class="btn btn-soft btn-full" disabled>Soal belum tersedia</button>
        <?php elseif ($ok): ?>
          <a class="btn btn-primary btn-full" href="<?= e(url('portal/tryout-kerjakan.php?id=' . $t['id'])) ?>"><?= isset($best[(int) $t['id']]) ? 'Kerjakan lagi' : 'Mulai tryout' ?> →</a>
        <?php else: ?>
          <a class="btn btn-outline btn-full" href="<?= e(url('packages.php?pilih=' . $t['package_slug'])) ?>">Beli paket <?= e((string) $t['package_name']) ?></a>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
    <?php if (!$tryouts): ?><div class="empty-state">Belum ada tryout tersedia.</div><?php endif; ?>
  </div>

  <section class="panel">
    <div class="panel-head"><h3>Riwayat &amp; perkembangan skor</h3></div>
    <?php if (!$attempts): ?><div class="empty-state small">Kamu belum mengerjakan tryout. Coba satu sekarang!</div><?php else: ?>
      <div class="bar-chart" aria-label="Grafik skor tryout">
        <?php foreach (array_reverse(array_slice($attempts, 0, 8)) as $a): ?>
          <div class="bar" title="<?= e($a['title']) ?>: <?= (int) $a['score'] ?>"><i style="height:<?= max(4, (int) $a['score']) ?>%"></i><b><?= (int) $a['score'] ?></b><small><?= e(date('d/m', strtotime((string) $a['finished_at']))) ?></small></div>
        <?php endforeach; ?>
      </div>
      <div class="table-wrap"><table>
        <thead><tr><th>Tanggal</th><th>Tryout</th><th>Benar</th><th>Skor</th><th></th></tr></thead>
        <tbody><?php foreach ($attempts as $a): ?>
          <tr><td><?= e(tanggal($a['finished_at'], true)) ?></td><td><?= e($a['title']) ?></td><td><?= (int) $a['correct_cnt'] ?>/<?= (int) $a['total_cnt'] ?></td><td><b><?= (int) $a['score'] ?></b></td>
          <td><a class="btn btn-soft btn-small" href="<?= e(url('portal/tryout-hasil.php?id=' . $a['id'])) ?>">Pembahasan</a></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    <?php endif; ?>
  </section>

<?php else:
    $tryouts = rows('SELECT t.*, p.name AS package_name, u.name AS author,
        (SELECT COUNT(*) FROM tryout_questions q WHERE q.tryout_id = t.id) AS q_count,
        (SELECT COUNT(*) FROM tryout_attempts a WHERE a.tryout_id = t.id AND a.finished_at IS NOT NULL) AS attempts,
        (SELECT AVG(score) FROM tryout_attempts a WHERE a.tryout_id = t.id AND a.finished_at IS NOT NULL) AS avg_score
        FROM tryouts t LEFT JOIN packages p ON p.id = t.package_id LEFT JOIN users u ON u.id = t.created_by ORDER BY t.id DESC');
    $packages = rows("SELECT id, name FROM packages WHERE category IN ('tryout','utbk') ORDER BY name");
?>
  <section class="panel">
    <div class="panel-head"><h3>+ Buat tryout baru</h3></div>
    <form method="post" class="stack-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <div class="two-col">
        <div class="field"><label>Judul *</label><input name="title" required placeholder="Tryout UTBK Series 2"></div>
        <div class="field"><label>Kategori</label><select name="category"><option>UTBK</option><option>SMP</option><option>SMA</option><option>SD</option></select></div>
      </div>
      <div class="field"><label>Deskripsi</label><input name="description" placeholder="Ringkasan singkat"></div>
      <div class="two-col">
        <div class="field"><label>Durasi (menit)</label><input type="number" name="duration_min" min="5" max="180" value="30"></div>
        <div class="field"><label>Akses</label><select name="package_id"><option value="0">Gratis untuk semua murid</option><?php foreach ($packages as $p): ?><option value="<?= (int) $p['id'] ?>">Khusus pembeli: <?= e($p['name']) ?></option><?php endforeach; ?></select></div>
      </div>
      <button class="btn btn-primary">Buat &amp; tambah soal</button>
    </form>
  </section>

  <section class="panel">
    <div class="panel-head"><h3>Semua tryout (<?= count($tryouts) ?>)</h3></div>
    <div class="table-wrap"><table>
      <thead><tr><th>Tryout</th><th>Akses</th><th>Soal</th><th>Dikerjakan</th><th>Rata-rata</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($tryouts as $t): $mine = $u['role'] === 'admin' || (int) $t['created_by'] === (int) $u['id']; ?>
        <tr>
          <td><b><?= e($t['title']) ?></b><br><small class="muted"><?= e($t['category']) ?> · <?= (int) $t['duration_min'] ?> mnt<?= $t['is_public'] ? ' · tampil di website' : '' ?></small></td>
          <td><?= $t['package_name'] ? e($t['package_name']) : 'Gratis' ?></td>
          <td><?= (int) $t['q_count'] ?></td><td><?= (int) $t['attempts'] ?></td><td><?= $t['avg_score'] === null ? '—' : round((float) $t['avg_score']) ?></td>
          <td><?= $t['is_published'] ? '<span class="status status-ok">Terbit</span>' : '<span class="status status-warn">Draft</span>' ?></td>
          <td class="actions">
            <?php if ($mine): ?>
              <a class="btn btn-soft btn-small" href="<?= e(url('portal/tryout-edit.php?id=' . $t['id'])) ?>">Soal</a>
              <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><button class="btn btn-outline btn-small"><?= $t['is_published'] ? 'Sembunyikan' : 'Terbitkan' ?></button></form>
              <form method="post" class="inline-form" data-confirm="Hapus tryout beserta soal dan hasilnya?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><button class="link-danger">Hapus</button></form>
            <?php else: ?><small class="muted">oleh <?= e((string) $t['author']) ?></small><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </section>
<?php endif; ?>

<?php portal_close(); ?>
