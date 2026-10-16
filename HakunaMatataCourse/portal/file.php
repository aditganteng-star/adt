<?php
/** Mengunduh file upload dengan pengecekan izin akses. */
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$u = require_login();
$type = $_GET['t'] ?? '';
$id   = (int) ($_GET['id'] ?? 0);
$path = $name = null;

if ($type === 'material') {
    $m = row('SELECT * FROM materials WHERE id = ?', [$id]);
    if ($m && can_access_class($u, (int) $m['class_id'])) { $path = $m['file_path']; $name = $m['file_name']; }
} elseif ($type === 'submission') {
    $s = row('SELECT s.*, a.class_id FROM submissions s JOIN assignments a ON a.id = s.assignment_id WHERE s.id = ?', [$id]);
    if ($s && (((int) $s['student_id'] === (int) $u['id']) || (is_staff($u) && can_manage_class($u, (int) $s['class_id'])))) {
        $path = $s['file_path']; $name = $s['file_name'];
    }
}

$full = $path ? realpath(STORAGE_PATH . '/' . $path) : false;
if (!$full || !str_starts_with($full, (string) realpath(STORAGE_PATH)) || !is_file($full)) {
    http_response_code(404);
    exit('File tidak ditemukan atau kamu tidak memiliki akses.');
}

$ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
$inline = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'txt'], true);
$mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'txt' => 'text/plain; charset=utf-8'][$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($full));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurlencode((string) $name) . '"');
readfile($full);
exit;
