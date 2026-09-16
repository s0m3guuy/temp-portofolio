<?php
require 'auth.php';
require 'koneksi.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anggota_id  = intval($_POST['anggota_id']);
    $alasan      = $_POST['alasan'];
    $keterangan  = !empty($_POST['keterangan']) ? $_POST['keterangan'] : '';
    $dibuat_oleh = $_SESSION['username'] ?? 'admin'; // adjust to your session variable

    // Check if already blacklisted
    $checkQuery = "SELECT id FROM blacklist WHERE anggota_id = ?";
    $checkStmt  = mysqli_prepare($koneksi, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, 'i', $anggota_id);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);

    if(mysqli_num_rows($checkResult) > 0) {
        header("Location: blacklist.php?error=1&message=" . urlencode("Anggota sudah ada di blacklist!"));
        exit();
    }

    $query = "INSERT INTO blacklist (anggota_id, alasan, keterangan, dibuat_oleh, created_at) 
              VALUES (?, ?, ?, ?, NOW())";
    $stmt  = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, 'isss', $anggota_id, $alasan, $keterangan, $dibuat_oleh);

    if(mysqli_stmt_execute($stmt)) {
        header("Location: blacklist.php?success=1&message=" . urlencode("Anggota berhasil di-blacklist!"));
    } else {
        header("Location: blacklist.php?error=1&message=" . urlencode("Gagal: " . mysqli_error($koneksi)));
    }

    mysqli_stmt_close($stmt);
    mysqli_close($koneksi);
    exit();
}
?>