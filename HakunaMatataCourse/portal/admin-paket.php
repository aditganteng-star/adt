<?php
/** Admin: kelola paket les & tryout (harga, fitur, badge). */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

require_role('admin');
$cats = ['les' => 'Les', 'tryout' => 'Tryout', 'utbk' => 'UTBK'];
$edit = null;

if (is_post()) {
    verify_csrf();
    try {
        $action = post('action');
        if ($action === 'save') {
            $id = (int) post('id');
            if (mb_strlen(post('name')) < 3) throw new RuntimeException('Nama paket minimal 3 karakter.');
            if ((int) post('price') < 0) throw new RuntimeException('Harga tidak valid.');
            $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', post('slug') ?: post('name')), '-'));
            if (val('SELECT COUNT(*) FROM packages WHERE slug = ? AND id <> ?', [$slug, $id])) throw new RuntimeException('Slug sudah dipakai paket lain.');
            if (mb_strlen(post('features')) < 3) throw new RuntimeException('Isi minimal satu fitur (satu per baris).');
            $data = [
                'slug' => $slug, 'name' => mb_substr(post('name'), 0, 150), 'category' => array_key_exists(post('category'), $cats) ? post('category') : 'les',
                'level' => in_array(post('level'), ['SD', 'SMP', 'SMA', 'UTBK'], true) ? post('level') : 'SMP', 'description' => mb_substr(post('description'), 0, 500),
                'features' => post('features'), 'price' => max(0, (int) post('price')), 'old_price' => (int) post('old_price') > 0 ? (int) post('old_price') : null,
                'unit' => in_array(post('unit'), ['bulan', 'paket'], true) ? post('unit') : 'bulan', 'badge' => mb_substr(post('badge'), 0, 40) ?: null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0, 'sort_order' => (int) post('sort_order'),
            ];
            if ($id) {
                $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
                run("UPDATE packages SET $set WHERE id = ?", [...array_values($data), $id]);
                flash('Paket diperbarui.');
            } else {
                insert('packages', $data);
                flash('Paket ditambahkan dan tampil di halaman Paket.');
            }
        } elseif ($action === 'delete') {
            $id = (int) post('id');
            run('UPDATE tryouts SET package_id = NULL WHERE package_id = ?', [$id]);
            run('DELETE FROM entitlements WHERE package_id = ?', [$id]);
            run('UPDATE orders SET package_id = NULL WHERE package_id = ?', [$id]);
            run('DELETE FROM packages WHERE id = ?', [$id]);
            flash('Paket dihapus.', 'info');
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('portal/admin-paket.php');
}

if (isset($_GET['edit'])) $edit = row('SELECT * FROM packages WHERE id = ?', [(int) $_GET['edit']]);
$packages = rows('SELECT * FROM packages ORDER BY sort_order, id');
$f = $edit ?: ['id' => 0, 'slug' => '', 'name' => '', 'category' => 'les', 'level' => 'SMP', 'description' => '', 'features' => '', 'price' => 0, 'old_price' => '', 'unit' => 'bulan', 'badge' => '', 'is_active' => 1, 'sort_order' => count($packages)];

portal_open('Paket & Harga', 'paket');
?>
<section class="panel" id="form">
  <div class="panel-head"><h3><?= $edit ? 'Edit paket' : '+ Tambah paket' ?></h3><?php if ($edit): ?><a class="text-link" href="<?= e(url('portal/admin-paket.php')) ?>">Batal edit</a><?php endif; ?></div>
  <form method="post" class="stack-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
    <div class="two-col">
      <div class="field"><label>Nama paket *</label><input name="name" value="<?= e($f['name']) ?>" required></div>
      <div class="field"><label>Slug (alamat unik)</label><input name="slug" value="<?= e($f['slug']) ?>" placeholder="otomatis dari nama"></div>
    </div>
    <div class="field"><label>Deskripsi singkat</label><input name="description" value="<?= e($f['description']) ?>"></div>
    <div class="two-col">
      <div class="field"><label>Kategori</label><select name="category"><?php foreach ($cats as $k => $l): ?><option value="<?= $k ?>" <?= $f['category'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Jenjang</label><select name="level"><?php foreach (['SD', 'SMP', 'SMA', 'UTBK'] as $l): ?><option <?= $f['level'] === $l ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="two-col">
      <div class="field"><label>Harga (Rp) *</label><input type="number" min="0" step="1000" name="price" value="<?= (int) $f['price'] ?>" required></div>
      <div class="field"><label>Harga coret (Rp, opsional)</label><input type="number" min="0" step="1000" name="old_price" value="<?= e((string) $f['old_price']) ?>"></div>
    </div>
    <div class="two-col">
      <div class="field"><label>Satuan harga</label><select name="unit"><option value="bulan" <?= $f['unit'] === 'bulan' ? 'selected' : '' ?>>per bulan (bisa pilih durasi)</option><option value="paket" <?= $f['unit'] === 'paket' ? 'selected' : '' ?>>per paket (sekali bayar)</option></select></div>
      <div class="field"><label>Label (opsional)</label><input name="badge" value="<?= e((string) $f['badge']) ?>" placeholder="Terlaris / Hemat 33%"></div>
    </div>
    <div class="field"><label>Fitur (satu per baris) *</label><textarea name="features" rows="5"><?= e($f['features']) ?></textarea></div>
    <div class="two-col">
      <div class="field"><label>Urutan tampil</label><input type="number" name="sort_order" value="<?= (int) $f['sort_order'] ?>"></div>
      <label class="check-line"><input type="checkbox" name="is_active" <?= $f['is_active'] ? 'checked' : '' ?>> Tampilkan di website</label>
    </div>
    <button class="btn btn-primary"><?= $edit ? 'Simpan perubahan' : 'Tambah paket' ?></button>
  </form>
</section>

<section class="panel">
  <div class="panel-head"><h3>Daftar paket (<?= count($packages) ?>)</h3></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Paket</th><th>Kategori</th><th>Harga</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($packages as $p): ?>
      <tr>
        <td><b><?= e($p['name']) ?></b><br><small class="muted"><?= e($p['level']) ?> · <?= e($p['slug']) ?><?= $p['badge'] ? ' · ' . e($p['badge']) : '' ?></small></td>
        <td><?= e($cats[$p['category']] ?? $p['category']) ?></td>
        <td><?= rupiah((int) $p['price']) ?> <small>/<?= e($p['unit']) ?></small></td>
        <td><?= $p['is_active'] ? '<span class="status status-ok">Tampil</span>' : '<span class="status status-info">Disembunyikan</span>' ?></td>
        <td class="actions"><a class="btn btn-soft btn-small" href="<?= e(url('portal/admin-paket.php?edit=' . $p['id'] . '#form')) ?>">Edit</a>
          <form method="post" class="inline-form" data-confirm="Hapus paket <?= e($p['name']) ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="link-danger">Hapus</button></form></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php portal_close(); ?>
