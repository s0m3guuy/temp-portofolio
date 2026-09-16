<?php
require 'auth.php';
require 'koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

$cek = mysqli_query($koneksi, "SELECT id FROM pembayaran WHERE id = $id");
if (!$cek || mysqli_num_rows($cek) === 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
    exit;
}

if (mysqli_query($koneksi, "DELETE FROM pembayaran WHERE id = $id")) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($koneksi)]);
}
exit;