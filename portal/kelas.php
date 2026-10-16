<?php
/** Kelas: daftar, gabung (murid), buat (tutor/admin), detail + kelola murid & pengumuman. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

$u = require_login();
$classId = (int) ($_GET['id'] ?? 0);

/* ---------------- Aksi POST ---------------- */
if (is_post()) {
    verify_csrf();
    $action = post('action');
    try {
        if ($action === 'join' && $u['role'] === 'student') {
            $class = row('SELECT * FROM classes WHERE join_code = ?', [strtoupper(post('code'))]);
            if (!$class) throw new RuntimeException('Kode kelas tidak ditemukan. Periksa kembali kode dari tutor.');
            if (val('SELECT COUNT(*) FROM enrollments WHERE class_id = ? AND student_id = ?', [$class['id'], $u['id']])) {
                throw new RuntimeException('Kamu sudah terdaftar di kelas ini.');
            }
            insert('enrollments', ['class_id' => $class['id'], 'student_id' => $u['id']]);
            flash('Berhasil bergabung ke kelas ' . $class['name'] . '! 🎉');
            redirect('portal/kelas.php?id=' . $class['id']);
        }

        if ($action === 'create' && is_staff($u)) {
            $name = post('name');
            if (mb_strlen($name) < 3) throw new RuntimeException('Nama kelas minimal 3 karakter.');
            $level = in_array(post('level'), ['SD', 'SMP', 'SMA', 'UTBK'], true) ? post('level') : 'SMP';
            $tutorId = $u['role'] === 'admin' ? (int) post('tutor_id') : (int) $u['id'];
            if (!val("SELECT COUNT(*) FROM users WHERE id = ? AND role IN ('tutor','admin')", [$tutorId])) throw new RuntimeException('Pilih tutor pengampu.');
            do { $code = random_code(6); } while (val('SELECT COUNT(*) FROM classes WHERE join_code = ?', [$code]));
            $id = insert('classes', [
                'name' => mb_substr($name, 0, 150), 'subject' => mb_substr(post('subject') ?: $name, 0, 100), 'level' => $level,
                'tutor_id' => $tutorId, 'schedule' => mb_substr(post('schedule'), 0, 150) ?: null,
                'description' => mb_substr(post('description'), 0, 500) ?: null, 'join_code' => $code,
            ]);
            flash("Kelas dibuat. Bagikan kode gabung: $code");
            redirect('portal/kelas.php?id=' . $id);
        }

        if (in_array($action, ['add_student', 'remove_student', 'announce', 'delete_announce', 'update_class'], true)) {
            $cid = (int) post('class_id');
            if (!can_manage_class($u, $cid)) throw new RuntimeException('Kamu tidak memiliki izin untuk kelas ini.');

            if ($action === 'add_student') {
                $stu = row("SELECT id FROM users WHERE email = ? AND role = 'student'", [mb_strtolower(post('email'))]);
                if (!$stu) throw new RuntimeException('Murid dengan email tersebut belum terdaftar.');
                if (val('SELECT COUNT(*) FROM enrollments WHERE class_id = ? AND student_id = ?', [$cid, $stu['id']])) throw new RuntimeException('Murid sudah ada di kelas ini.');
                insert('enrollments', ['class_id' => $cid, 'student_id' => $stu['id']]);
                flash('Murid ditambahkan ke kelas.');
            } elseif ($action === 'remove_student') {
                run('DELETE FROM enrollments WHERE class_id = ? AND student_id = ?', [$cid, (int) post('student_id')]);
                flash('Murid dikeluarkan dari kelas.', 'info');
            } elseif ($action === 'announce') {
                if (post('title') === '' || post('body') === '') throw new RuntimeException('Judul dan isi pengumuman wajib diisi.');
                insert('announcements', ['class_id' => $cid, 'title' => mb_substr(post('title'), 0, 190), 'body' => mb_substr(post('body'), 0, 1000), 'created_by' => $u['id']]);
                flash('Pengumuman dikirim ke murid.');
            } elseif ($action === 'delete_announce') {
                run('DELETE FROM announcements WHERE id = ? AND class_id = ?', [(int) post('announce_id'), $cid]);
                flash('Pengumuman dihapus.', 'info');
            } elseif ($action === 'update_class') {
                run('UPDATE classes SET name = ?, schedule = ?, description = ? WHERE id = ?', [mb_substr(post('name'), 0, 150), mb_substr(post('schedule'), 0, 150) ?: null, mb_substr(post('description'), 0, 500) ?: null, $cid]);
                flash('Info kelas diperbarui.');
            }
            redirect('portal/kelas.php?id=' . $cid);
        }
        throw new RuntimeException('Aksi tidak tersedia untuk akunmu.');
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
        redirect('portal/kelas.php' . ($classId ? '?id=' . $classId : ''));
    }
}

/* ---------------- Halaman detail ---------------- */
if ($classId) {
    if (!can_access_class($u, $classId)) { http_response_code(403); exit('Kamu tidak memiliki akses ke kelas ini.'); }
    $class = row('SELECT c.*, u.name AS tutor_name FROM classes c JOIN users u ON u.id = c.tutor_id WHERE c.id = ?', [$classId]);
    $manage   = can_manage_class($u, $classId);
    $students = rows('SELECT u.id, u.name, u.email, u.grade_level, u.school FROM enrollments e JOIN users u ON u.id = e.student_id WHERE e.class_id = ? ORDER BY u.name', [$classId]);
    $sessions = rows('SELECT * FROM class_sessions WHERE class_id = ? ORDER BY session_date DESC, id DESC', [$classId]);
    $news     = rows('SELECT * FROM announcements WHERE class_id = ? ORDER BY id DESC', [$classId]);

    portal_open($class['name'], 'kelas');
    ?>
    <a class="back-link" href="<?= e(url('portal/kelas.php')) ?>">← Semua kelas</a>
    <div class="class-hero">
      <div>
        <span class="pill"><?= e($class['level']) ?></span>
        <h2><?= e($class['name']) ?></h2>
        <p><?= e((string) $class['description']) ?></p>
        <div class="meta-row"><span>👩‍🏫 <?= e($class['tutor_name']) ?></span><span>🗓 <?= e((string) ($class['schedule'] ?: 'Jadwal belum diatur')) ?></span><span>🧑‍🎓 <?= count($students) ?> murid</span></div>
      </div>
      <?php if ($manage): ?>
        <div class="code-box"><small>Kode gabung kelas</small><b id="joinCode"><?= e($class['join_code']) ?></b><button type="button" class="btn btn-soft btn-small" data-copy="#joinCode">Salin</button></div>
      <?php endif; ?>
    </div>

    <div class="quick-links">
      <a class="btn btn-outline btn-small" href="<?= e(url('portal/materi.php?kelas=' . $classId)) ?>">📚 Materi</a>
      <a class="btn btn-outline btn-small" href="<?= e(url('portal/tugas.php?kelas=' . $classId)) ?>">📝 Tugas</a>
      <a class="btn btn-outline btn-small" href="<?= e(url('portal/absensi.php?kelas=' . $classId)) ?>">✅ Absensi</a>
    </div>

    <div class="grid-2">
      <section class="panel">
        <div class="panel-head"><h3>📢 Pengumuman</h3></div>
        <?php if ($manage): ?>
          <form method="post" class="stack-form compact">
            <?= csrf_field() ?><input type="hidden" name="action" value="announce"><input type="hidden" name="class_id" value="<?= $classId ?>">
            <input name="title" placeholder="Judul pengumuman" required>
            <textarea name="body" rows="2" placeholder="Isi pengumuman…" required></textarea>
            <button class="btn btn-primary btn-small">Kirim pengumuman</button>
          </form>
        <?php endif; ?>
        <?php if (!$news): ?><div class="empty-state small">Belum ada pengumuman.</div><?php endif; ?>
        <?php foreach ($news as $n): ?>
          <div class="announce">
            <b><?= e($n['title']) ?></b><p><?= e($n['body']) ?></p><small><?= e(tanggal($n['created_at'], true)) ?></small>
            <?php if ($manage): ?>
              <form method="post" class="inline-form" data-confirm="Hapus pengumuman ini?"><?= csrf_field() ?><input type="hidden" name="action" value="delete_announce"><input type="hidden" name="class_id" value="<?= $classId ?>"><input type="hidden" name="announce_id" value="<?= (int) $n['id'] ?>"><button class="link-danger">Hapus</button></form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </section>

      <section class="panel">
        <div class="panel-head"><h3>🗓 Pertemuan</h3><?php if ($manage): ?><a class="text-link" href="<?= e(url('portal/absensi.php?kelas=' . $classId)) ?>">Kelola →</a><?php endif; ?></div>
        <?php if (!$sessions): ?><div class="empty-state small">Belum ada pertemuan.</div><?php endif; ?>
        <?php foreach (array_slice($sessions, 0, 6) as $s): ?>
          <div class="list-row">
            <span class="row-ico">🗓</span><div><b><?= e($s['title']) ?></b><small><?= e(tanggal($s['session_date'])) ?></small></div>
            <?php if ($manage): ?><a class="btn btn-soft btn-small" href="<?= e(url('portal/sesi.php?id=' . $s['id'])) ?>">Buka</a><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </section>
    </div>

    <section class="panel">
      <div class="panel-head"><h3>🧑‍🎓 Daftar murid (<?= count($students) ?>)</h3></div>
      <?php if ($manage): ?>
        <form method="post" class="inline-add">
          <?= csrf_field() ?><input type="hidden" name="action" value="add_student"><input type="hidden" name="class_id" value="<?= $classId ?>">
          <input type="email" name="email" placeholder="Email murid terdaftar" required>
          <button class="btn btn-primary btn-small">+ Tambah murid</button>
        </form>
      <?php endif; ?>
      <div class="table-wrap"><table>
        <thead><tr><th>Nama</th><th>Email</th><th>Jenjang</th><th>Sekolah</th><?php if ($manage): ?><th></th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($students as $s): ?>
          <tr><td><b><?= e($s['name']) ?></b></td><td><?= e($s['email']) ?></td><td><?= e((string) $s['grade_level']) ?></td><td><?= e((string) $s['school']) ?></td>
          <?php if ($manage): ?><td><form method="post" class="inline-form" data-confirm="Keluarkan <?= e($s['name']) ?> dari kelas?"><?= csrf_field() ?><input type="hidden" name="action" value="remove_student"><input type="hidden" name="class_id" value="<?= $classId ?>"><input type="hidden" name="student_id" value="<?= (int) $s['id'] ?>"><button class="link-danger">Keluarkan</button></form></td><?php endif; ?></tr>
        <?php endforeach; ?>
        <?php if (!$students): ?><tr><td colspan="5" class="empty-cell">Belum ada murid. Bagikan kode gabung atau tambahkan lewat email.</td></tr><?php endif; ?>
        </tbody>
      </table></div>
    </section>

    <?php if ($manage): ?>
      <section class="panel">
        <div class="panel-head"><h3>⚙️ Pengaturan kelas</h3></div>
        <form method="post" class="stack-form">
          <?= csrf_field() ?><input type="hidden" name="action" value="update_class"><input type="hidden" name="class_id" value="<?= $classId ?>">
          <div class="two-col">
            <div class="field"><label>Nama kelas</label><input name="name" value="<?= e($class['name']) ?>" required></div>
            <div class="field"><label>Jadwal</label><input name="schedule" value="<?= e((string) $class['schedule']) ?>" placeholder="Senin & Kamis, 16.00"></div>
          </div>
          <div class="field"><label>Deskripsi</label><textarea name="description" rows="2"><?= e((string) $class['description']) ?></textarea></div>
          <button class="btn btn-primary btn-small">Simpan perubahan</button>
        </form>
      </section>
    <?php endif; ?>
    <?php
    portal_close();
    exit;
}

/* ---------------- Daftar kelas ---------------- */
$classes = visible_classes($u);
$tutors  = $u['role'] === 'admin' ? rows("SELECT id, name FROM users WHERE role IN ('tutor','admin') ORDER BY name") : [];
portal_open($u['role'] === 'student' ? 'Kelas Saya' : 'Kelas', 'kelas');
?>

<?php if ($u['role'] === 'student'): ?>
  <section class="panel join-panel">
    <div><h3>Punya kode kelas dari tutor?</h3><p class="muted">Masukkan kode untuk bergabung ke kelas.</p></div>
    <form method="post" class="inline-add">
      <?= csrf_field() ?><input type="hidden" name="action" value="join">
      <input name="code" placeholder="Contoh: MTK9SMP" maxlength="12" style="text-transform:uppercase" required>
      <button class="btn btn-primary">Gabung kelas</button>
    </form>
  </section>
<?php endif; ?>

<div class="class-grid">
  <?php foreach ($classes as $c):
    $n = (int) val('SELECT COUNT(*) FROM enrollments WHERE class_id = ?', [$c['id']]); ?>
    <a class="class-card" href="<?= e(url('portal/kelas.php?id=' . $c['id'])) ?>">
      <span class="pill"><?= e($c['level']) ?></span>
      <h4><?= e($c['name']) ?></h4>
      <small>👩‍🏫 <?= e($c['tutor_name']) ?></small>
      <small>🗓 <?= e((string) ($c['schedule'] ?: '—')) ?></small>
      <small>🧑‍🎓 <?= $n ?> murid</small>
    </a>
  <?php endforeach; ?>
  <?php if (!$classes): ?><div class="empty-state"><?= $u['role'] === 'student' ? 'Kamu belum bergabung ke kelas mana pun. Masukkan kode kelas di atas.' : 'Belum ada kelas.' ?></div><?php endif; ?>
</div>

<?php if (is_staff($u)): ?>
  <section class="panel">
    <div class="panel-head"><h3>+ Buat kelas baru</h3></div>
    <form method="post" class="stack-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <div class="two-col">
        <div class="field"><label>Nama kelas *</label><input name="name" required placeholder="Contoh: Fisika SMA Kelas 11"></div>
        <div class="field"><label>Mata pelajaran</label><input name="subject" placeholder="Fisika"></div>
      </div>
      <div class="two-col">
        <div class="field"><label>Jenjang</label><select name="level"><option>SD</option><option selected>SMP</option><option>SMA</option><option>UTBK</option></select></div>
        <div class="field"><label>Jadwal</label><input name="schedule" placeholder="Senin & Kamis, 16.00–17.30"></div>
      </div>
      <?php if ($tutors): ?><div class="field"><label>Tutor pengampu</label><select name="tutor_id"><?php foreach ($tutors as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
      <div class="field"><label>Deskripsi</label><textarea name="description" rows="2"></textarea></div>
      <button class="btn btn-primary">Buat kelas</button>
    </form>
  </section>
<?php endif; ?>

<?php portal_close(); ?>
