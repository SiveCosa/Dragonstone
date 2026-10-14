<?php
// public/product_upload.php
require_once __DIR__ . '/../src/config.php';
require_roles(['admin', 'staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
if (!isset($_FILES['image'])) { echo json_encode(['error' => 'No file']); exit; }

$file    = $_FILES['image'];
$allowed = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($file['type'], $allowed)) { echo json_encode(['error' => 'Invalid file type']); exit; }
if ($file['size'] > 5 * 1024 * 1024) { echo json_encode(['error' => 'File too large']); exit; }

$uploadDir = __DIR__ . '/uploads/products/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file['name']);
$target   = $uploadDir . $filename;
if (move_uploaded_file($file['tmp_name'], $target)) {
    echo json_encode(['status' => 'ok', 'filename' => $filename]);
} else {
    echo json_encode(['error' => 'Upload failed']);
}
