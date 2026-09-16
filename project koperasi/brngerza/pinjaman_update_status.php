<?php
require 'auth.php';
require 'koneksi.php';

header('Content-Type: application/json');

// Get POST data
$id = $_POST['id'];
$status = $_POST['status'];

// Map status values
$statusMap = [
    'aktif' => 'aktif',
    'rejected' => 'rejected',
    'pending' => 'pending'
];

$status_pinjaman = $statusMap[$status] ?? 'pending';

$query = "UPDATE pinjaman SET 
    status_pinjaman = ?
    WHERE id = ?";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "si", $status_pinjaman, $id);

if(mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Status berhasil diubah']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengubah status: ' . mysqli_error($koneksi)]);
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);
exit();
?>