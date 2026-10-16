<?php
/**
 * Bootstrap: dimuat oleh semua halaman.
 * Berisi koneksi database, helper tampilan, autentikasi, CSRF, dan upload.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

define('ROOT_PATH', dirname(__DIR__));
define('STORAGE_PATH', ROOT_PATH . '/storage');

/* ---------------------------------------------------------------------
 *  URL & tampilan
 * ------------------------------------------------------------------- */

/** Folder URL proyek, mis. "/HakunaMatataCourse" (kosong jika di root). */
function base_url(): string
{
    static $base = null;
    if ($base === null) {
        $docRoot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $root    = str_replace('\\', '/', (string) realpath(ROOT_PATH));
        $base    = ($docRoot !== '' && str_starts_with($root, $docRoot)) ? substr($root, strlen($docRoot)) : '';
    }
    return $base;
}

/** Bangun URL absolut-path ke file proyek. url('portal/index.php') */
function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function rupiah(int $amount): string
{
    return 'Rp' . number_format($amount, 0, ',', '.');
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

function whatsapp_url(string $message): string
{
    return 'https://wa.me/' . WHATSAPP_NUMBER . '?text=' . rawurlencode($message);
}

function tanggal(?string $date, bool $withTime = false): string
{
    if (!$date) return '-';
    $ts = strtotime($date);
    if ($ts === false) return '-';
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $out = date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    return $withTime ? $out . ', ' . date('H:i', $ts) : $out;
}

/* ---------------------------------------------------------------------
 *  Database (PDO) + helper ringkas
 * ------------------------------------------------------------------- */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn  = getenv('HAKUNA_DSN') ?: 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo  = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

/** Ambil banyak baris. */
function rows(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Ambil satu baris (atau null). */
function row(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $r = $st->fetch();
    return $r === false ? null : $r;
}

/** Ambil satu nilai (kolom pertama baris pertama). */
function val(string $sql, array $params = []): mixed
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $r = $st->fetchColumn();
    return $r === false ? null : $r;
}

/** Jalankan INSERT/UPDATE/DELETE. Mengembalikan jumlah baris terdampak. */
function run(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/** INSERT lalu kembalikan ID baru. */
function insert(string $table, array $data): int
{
    $cols = implode(',', array_keys($data));
    $marks = implode(',', array_fill(0, count($data), '?'));
    run("INSERT INTO $table ($cols) VALUES ($marks)", array_values($data));
    return (int) db()->lastInsertId();
}

/* ---------------------------------------------------------------------
 *  Pesan kilat (flash), CSRF
 * ------------------------------------------------------------------- */

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function take_flash(): array
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Silakan kembali, muat ulang halaman, lalu coba lagi.');
    }
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/** Ambil input POST berupa teks rapi. */
function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/* ---------------------------------------------------------------------
 *  Autentikasi & peran
 * ------------------------------------------------------------------- */

const ROLE_LABELS = ['student' => 'Murid', 'tutor' => 'Tutor', 'admin' => 'Admin'];

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = isset($_SESSION['uid']) ? row('SELECT * FROM users WHERE id = ?', [$_SESSION['uid']]) : null;
        if (!$user) unset($_SESSION['uid']);
    }
    return $user;
}

function logged_in(): bool
{
    return current_user() !== null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $user['id'];
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        flash('Silakan masuk terlebih dahulu.', 'info');
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
    return $u;
}

/** Batasi halaman untuk peran tertentu. */
function require_role(string ...$roles): array
{
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        exit('Akses ditolak. Akun Anda tidak memiliki izin untuk halaman ini.');
    }
    return $u;
}

function is_staff(array $u): bool
{
    return in_array($u['role'], ['tutor', 'admin'], true);
}

/** Alamat tujuan setelah login (hanya path internal yang diizinkan). */
function safe_next(?string $next): string
{
    if ($next && str_starts_with($next, '/') && !str_starts_with($next, '//') && !str_contains($next, '\\')) {
        return $next;
    }
    return url('portal/index.php');
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) $out .= mb_strtoupper(mb_substr($p, 0, 1));
    return $out ?: '?';
}

function random_code(int $len = 6): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) $out .= $chars[random_int(0, strlen($chars) - 1)];
    return $out;
}

/* ---------------------------------------------------------------------
 *  Akses kelas & paket
 * ------------------------------------------------------------------- */

/** Kelas yang boleh dilihat user: murid = terdaftar, tutor = miliknya, admin = semua. */
function visible_classes(array $u): array
{
    return match ($u['role']) {
        'admin' => rows('SELECT c.*, u.name AS tutor_name FROM classes c JOIN users u ON u.id = c.tutor_id ORDER BY c.name'),
        'tutor' => rows('SELECT c.*, u.name AS tutor_name FROM classes c JOIN users u ON u.id = c.tutor_id WHERE c.tutor_id = ? ORDER BY c.name', [$u['id']]),
        default => rows('SELECT c.*, u.name AS tutor_name FROM classes c JOIN users u ON u.id = c.tutor_id
                         JOIN enrollments en ON en.class_id = c.id WHERE en.student_id = ? ORDER BY c.name', [$u['id']]),
    };
}

function can_access_class(array $u, int $classId): bool
{
    foreach (visible_classes($u) as $c) {
        if ((int) $c['id'] === $classId) return true;
    }
    return false;
}

/** Hanya admin atau tutor pemilik kelas yang boleh mengelola kelas. */
function can_manage_class(array $u, int $classId): bool
{
    if ($u['role'] === 'admin') return true;
    return $u['role'] === 'tutor' && (int) val('SELECT COUNT(*) FROM classes WHERE id = ? AND tutor_id = ?', [$classId, $u['id']]) > 0;
}

function has_entitlement(array $u, ?int $packageId): bool
{
    if ($packageId === null || is_staff($u)) return true;
    $today = date('Y-m-d');
    return (int) val('SELECT COUNT(*) FROM entitlements WHERE user_id = ? AND package_id = ?
                      AND (expires_at IS NULL OR expires_at >= ?)', [$u['id'], $packageId, $today]) > 0;
}

/* ---------------------------------------------------------------------
 *  Upload file (disimpan di storage/, di luar akses publik)
 * ------------------------------------------------------------------- */

const UPLOAD_ALLOWED = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'jpg', 'jpeg', 'png'];
const UPLOAD_MAX_MB  = 10;

/**
 * Simpan file upload. Mengembalikan [nama_asli, path_relatif] atau null jika tidak ada file.
 * Melempar RuntimeException jika file tidak valid.
 */
function save_upload(string $field, string $folder): ?array
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gagal. Coba lagi atau gunakan file yang lebih kecil.');
    if ($f['size'] > UPLOAD_MAX_MB * 1024 * 1024) throw new RuntimeException('Ukuran file maksimal ' . UPLOAD_MAX_MB . ' MB.');

    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED, true)) {
        throw new RuntimeException('Tipe file tidak diizinkan. Gunakan: ' . implode(', ', UPLOAD_ALLOWED) . '.');
    }
    $dir = STORAGE_PATH . '/' . $folder;
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    $stored = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $stored)) throw new RuntimeException('File tidak dapat disimpan di server.');

    $original = preg_replace('/[^\w.\- ]+/u', '_', basename($f['name'])) ?: ('file.' . $ext);
    return [mb_substr($original, 0, 200), $folder . '/' . $stored];
}

/**
 * Simpan foto (jpg/png/webp, maks. 3 MB) ke assets/uploads/<folder>/ agar bisa tampil di website.
 * Mengembalikan path relatif (mis. "assets/uploads/tutors/abc.jpg") atau null jika tidak ada file.
 */
function save_image(string $field, string $folder): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload foto gagal. Coba file yang lebih kecil.');
    if ($f['size'] > 3 * 1024 * 1024) throw new RuntimeException('Ukuran foto maksimal 3 MB.');
    $info = @getimagesize($f['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) throw new RuntimeException('File harus berupa foto JPG, PNG, atau WEBP.');
    $dir = ROOT_PATH . '/assets/uploads/' . $folder;
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = bin2hex(random_bytes(10)) . '.' . $types[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Foto tidak dapat disimpan di server.');
    return 'assets/uploads/' . $folder . '/' . $name;
}

/** Avatar tutor: foto jika ada, jika tidak inisial berwarna. $class tambahan untuk ukuran. */
function tutor_avatar(array $t, string $class = ''): string
{
    if (!empty($t['photo']) && is_file(ROOT_PATH . '/' . $t['photo'])) {
        return '<img class="tutor-avatar tutor-photo ' . e($class) . '" src="' . e(url($t['photo'])) . '" alt="Foto ' . e($t['name']) . '" loading="lazy">';
    }
    return '<span class="tutor-avatar av-' . e($t['color']) . ' ' . e($class) . '">' . e(initials($t['name'])) . '</span>';
}

/** Daftar foto galeri: semua jpg/png/webp di assets/img/galeri/ (urut nama file). */
function gallery_photos(): array
{
    $out = [];
    foreach (glob(ROOT_PATH . '/assets/img/galeri/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [] as $file) {
        $out[] = 'assets/img/galeri/' . basename($file);
    }
    sort($out);
    return $out;
}

/* ---------------------------------------------------------------------
 *  Page View Counter (file data/counter.txt)
 *  Bertambah 1 setiap halaman publik dibuka / di-refresh.
 * ------------------------------------------------------------------- */

function page_counter(): int
{
    $file = ROOT_PATH . '/data/counter.txt';
    if (!is_dir(dirname($file))) mkdir(dirname($file), 0775, true);
    $fh = @fopen($file, 'c+');
    if (!$fh) return 0;
    flock($fh, LOCK_EX);
    $count = (int) trim((string) stream_get_contents($fh)) + 1;
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, (string) $count);
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $count;
}

/** Ambil daftar fitur (satu per baris) menjadi array. */
function lines(string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: [])));
}

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
