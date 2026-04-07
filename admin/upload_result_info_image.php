<?php
require_once __DIR__ . '/includes/admin_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesi admin tidak valid.']);
    exit;
}

if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'File gambar tidak ditemukan.']);
    exit;
}

$file = $_FILES['image'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Upload gambar gagal diproses.']);
    exit;
}

$tmpPath = $file['tmp_name'] ?? '';
if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
    http_response_code(400);
    echo json_encode(['error' => 'File upload tidak valid.']);
    exit;
}

$maxSize = 5 * 1024 * 1024;
if (($file['size'] ?? 0) > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'Ukuran gambar maksimal 5MB.']);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($tmpPath);
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];

if (!isset($allowed[$mime])) {
    http_response_code(400);
    echo json_encode(['error' => 'Format gambar harus JPG, PNG, GIF, atau WEBP.']);
    exit;
}

$targetDir = dirname(__DIR__) . '/assets/result_info';
if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
    http_response_code(500);
    echo json_encode(['error' => 'Folder upload gambar tidak bisa dibuat.']);
    exit;
}

$filename = 'result-info-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
$targetPath = $targetDir . '/' . $filename;

if (!move_uploaded_file($tmpPath, $targetPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menyimpan gambar ke server.']);
    exit;
}

$scriptDir = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')));
$basePath = rtrim($scriptDir, '/');
$publicUrl = ($basePath !== '' ? $basePath : '') . '/assets/result_info/' . $filename;

echo json_encode([
    'url' => $publicUrl,
    'filename' => $filename,
]);
