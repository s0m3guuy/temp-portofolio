<?php
require 'auth.php';
require 'koneksi.php';

// Get all POST data
$id = $_POST['id'];
$pinjaman = $_POST['pinjaman'];
$jangka_waktu = $_POST['jangka_waktu'];
$bunga = $_POST['bunga'];
$status_pinjaman = $_POST['status_pinjaman'];

// Recalculate total pinjaman + bunga
$total_pinjaman_bunga = $pinjaman + ($pinjaman * ($bunga/100) * $jangka_waktu);
$bayar_angsuran = $total_pinjaman_bunga / $jangka_waktu;

$query = "UPDATE pinjaman SET 
    pinjaman = ?,
    jangka_waktu = ?,
    bunga = ?,
    total_pinjaman_bunga = ?,
    bayar_angsuran = ?,
    status_pinjaman = ?
    WHERE id = ?";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "dddddss", 
    $pinjaman, $jangka_waktu, $bunga, 
    $total_pinjaman_bunga, $bayar_angsuran, 
    $status_pinjaman, $id
);

if(mysqli_stmt_execute($stmt)) {
    header("Location: pengajuan.php?success=1&message=Data+pinjaman+berhasil+diupdate");
} else {
    header("Location: pengajuan.php?error=1&message=" . urlencode("Gagal mengupdate data: " . mysqli_error($koneksi)));
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);
exit();
?>