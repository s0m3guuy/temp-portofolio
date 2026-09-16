<?php
require 'auth.php';
require 'koneksi.php';

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = intval($_POST['id']);
    $status = $_POST['status'];

    // Only allow valid statuses
    $allowedStatuses = ['lunas', 'aktif', 'pending_lunas'];
    if(!in_array($status, $allowedStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
        exit;
    }

    $query = "UPDATE pinjaman SET status_pinjaman = ? WHERE id = ?";
    $stmt  = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, 'si', $status, $id);

    if(mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($koneksi)]);
    }

    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>