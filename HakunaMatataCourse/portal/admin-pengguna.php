<?php
/** Admin: kelola pengguna & peran. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

$me = require_role('admin');

if (is_post()) {
    verify_csrf();
    try {
        $action = post('action');
        if ($action === 'create') {
            $email = mb_strtolower(post('email'));
            $role  = in_array(post('role'), ['student', 'tutor', 'admin'], true) ? post('role') : 'student';
            if (mb_strlen(post('name')) < 2) throw new RuntimeException('Nama wajib diisi.');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email tidak valid.');
            if (row('SELECT id FROM users WHERE email = ?', [$email])) throw new RuntimeException('Email sudah terdaftar.');
            $pw = post('password');
            // Password boleh kosong: akun hanya bisa masuk lewat Google dengan email tersebut.
            if ($pw !== '' && strlen($pw) < 8) throw new RuntimeException('Password minimal 8 karakter (atau kosongkan untuk login Google saja).');
            insert('users', ['name' => mb_substr(post('name'), 0, 120), 'email' => $email, 'role' => $role, 'password_hash' => $pw !== '' ? password_hash($pw, PASSWORD_DEFAULT) : null]);
            flash('Akun dibuat' . ($pw === '' ? ' — pengguna bisa masuk dengan Google memakai email tersebut.' : '.'));
        } elseif ($action === 'role') {
            $id = (int) post('id');
            if ($id === (int) $me['id']) throw new RuntimeException('Kamu tidak bisa mengubah peranmu sendiri.');
            $role = in_array(post('role'), ['student', 'tutor', 'admin'], true) ? post('role') : 'student';
            run('UPDATE users SET role = ? WHERE id = ?', [$role, $id]);
            flash('Peran pengguna diubah.');
        } elseif ($action === 'reset') {
            $pw = post('password');
            if (strlen($pw) < 8) throw new RuntimeException('Password baru minimal 8 karakter.');
            run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), (int) post('id')]);
            flash('Password pengguna direset.');
        } elseif ($action === 'delete') {
            $id = (int) post('id');
            if ($id === (int) $me['id']) throw new RuntimeException('Kamu tidak bisa menghapus akunmu sendiri.');
            if (val('SELECT COUNT(*) FROM classes WHERE tutor_id = ?', [$id])) throw new RuntimeException('Pengguna ini masih mengampu kelas. Pindahkan kelasnya dulu.');
            foreach (['enrollments' => 'student_id', 'attendance' => 'student_id', 'submissions' => 'student_id', 'tryout_attempts' => 'student_id', 'entitlements' => 'user_id'] as $tbl => $col) run("DELETE FROM $tbl WHERE $col = ?", [$id]);
            run('UPDATE tutor_profiles SET user_id = NULL WHERE user_id = ?', [$id]);
            run('DELETE FROM users WHERE id = ?', [$id]);
            flash('Pengguna dihapus.', 'info');
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    } catch (PDOException $ex) {
        flash('Pengguna tidak dapat dihapus karena masih memiliki data terkait (materi/tugas/pengumuman).', 'error');
    }
    redirect('portal/admin-pengguna.php' . (isset($_GET['q']) ? '?q=' . urlencode((string) $_GET['q']) : ''));
}

$q = trim((string) ($_GET['q'] ?? ''));
$roleFilter = (string) ($_GET['role'] ?? '');
$sql = 'SELECT * FROM users WHERE 1=1';
$params = [];
if ($q !== '') { $sql .= ' AND (name LIKE ? OR email LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if (in_array($roleFilter, ['student', 'tutor', 'admin'], true)) { $sql .= ' AND role = ?'; $params[] = $roleFilter; }
$users = rows($sql . ' ORDER BY role, name', $params);

portal_open('Pengguna', 'pengguna');
?>
<section class="panel">
  <div class="panel-head"><h3>+ Tambah pengguna</h3></div>
  <form method="post" class="stack-form row-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="create">
    <div class="field grow"><label>Nama</label><input name="name" required></div>
    <div class="field grow"><label>Email (sama dengan email Google-nya)</label><input name="email" type="email" required></div>
    <div class="field"><label>Peran</label><select name="role"><option value="tutor">Tutor</option><option value="student">Murid</option><option value="admin">Admin</option></select></div>
    <div class="field"><label>Password (opsional)</label><input name="password" type="password" placeholder="kosong = Google saja" autocomplete="new-password"></div>
    <button class="btn btn-primary">Tambah</button>
  </form>
  <p class="fine-print">Tips: untuk membuat akun tutor, tambahkan emailnya di sini dengan peran Tutor. Saat tutor masuk dengan Google memakai email itu, peran tutor otomatis berlaku.</p>
</section>

<section class="panel">
  <div class="panel-head"><h3>Semua pengguna (<?= count($users) ?>)</h3>
    <form method="get" class="filter-inline">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama / email">
      <select name="role" onchange="this.form.submit()"><option value="">Semua peran</option><?php foreach (ROLE_LABELS as $k => $l): ?><option value="<?= $k ?>" <?= $roleFilter === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
      <button class="btn btn-soft btn-small">Cari</button>
    </form>
  </div>
  <div class="table-wrap"><table>
    <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Login</th><th>Bergabung</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $x): $self = (int) $x['id'] === (int) $me['id']; ?>
      <tr>
        <td><b><?= e($x['name']) ?></b><?= $self ? ' <small class="muted">(kamu)</small>' : '' ?></td>
        <td><?= e($x['email']) ?></td>
        <td><?php if ($self): ?><span class="role-badge role-admin">Admin</span><?php else: ?>
          <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= (int) $x['id'] ?>">
            <select name="role" onchange="this.form.submit()"><?php foreach (ROLE_LABELS as $k => $l): ?><option value="<?= $k ?>" <?= $x['role'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></form><?php endif; ?></td>
        <td><?= $x['google_id'] ? 'Google' : '' ?><?= $x['google_id'] && $x['password_hash'] ? ' + ' : '' ?><?= $x['password_hash'] ? 'Password' : '' ?></td>
        <td><?= e(tanggal($x['created_at'])) ?></td>
        <td class="actions">
          <?php if (!$self): ?>
            <form method="post" class="inline-form" data-prompt-password="Password baru untuk <?= e($x['name']) ?> (min. 8 karakter):"><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= (int) $x['id'] ?>"><input type="hidden" name="password"><button class="btn btn-soft btn-small">Reset password</button></form>
            <form method="post" class="inline-form" data-confirm="Hapus akun <?= e($x['name']) ?> beserta datanya?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $x['id'] ?>"><button class="link-danger">Hapus</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php portal_close(); ?>
