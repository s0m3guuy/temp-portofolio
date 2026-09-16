<?php
require 'auth.php';
require 'koneksi.php';

// Get all POST data
$anggota_id = (int)$_POST['anggota_id'];
$kode_pinjaman = $_POST['kode_pinjaman'];
$tipe_pinjaman = $_POST['tipe_pinjaman'];
$tanggal_pengambilan = $_POST['tanggal_pengambilan'];
$pinjaman = (float)$_POST['pinjaman'];
$jangka_waktu = (int)$_POST['jangka_waktu'];
$bunga = (float)$_POST['bunga'];
$total_pinjaman_bunga = (float)$_POST['total_pinjaman_bunga'];
$bayar_angsuran = (float)$_POST['bayar_angsuran'];
$jatuh_tempo = $_POST['jatuh_tempo'];
$kekurangan_pinjaman = !empty($_POST['kekurangan_pinjaman']) ? (float)$_POST['kekurangan_pinjaman'] : 0;
$denda_pinjaman_lama = !empty($_POST['denda_pinjaman_lama']) ? (float)$_POST['denda_pinjaman_lama'] : 0;
$biaya_admin = !empty($_POST['biaya_admin']) ? (float)$_POST['biaya_admin'] : 0;
$terima_pinjaman = (float)$_POST['terima_pinjaman'];
$status_pinjaman = 'pending';

$query = "INSERT INTO pinjaman (
    anggota_id, kode_pinjaman, tipe_pinjaman, tanggal_pengambilan,
    pinjaman, jangka_waktu, bunga, total_pinjaman_bunga,
    bayar_angsuran, jatuh_tempo, kekurangan_pinjaman,
    denda_pinjaman_lama, biaya_admin, terima_pinjaman, status_pinjaman
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($koneksi, $query);
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($koneksi));
}

// CORRECT TYPE STRING: isssdidddsdddds (15 characters)
// i=1, s=3, d=1, i=1, d=3, s=1, d=4, s=1
mysqli_stmt_bind_param($stmt, "isssdidddsdddds", 
    $anggota_id,
    $kode_pinjaman,
    $tipe_pinjaman,
    $tanggal_pengambilan,
    $pinjaman,
    $jangka_waktu,
    $bunga,
    $total_pinjaman_bunga,
    $bayar_angsuran,
    $jatuh_tempo,
    $kekurangan_pinjaman,
    $denda_pinjaman_lama,
    $biaya_admin,
    $terima_pinjaman,
    $status_pinjaman
);

if(mysqli_stmt_execute($stmt)) {
    header("Location: pengajuan.php?success=1&message=Pengajuan+pinjaman+berhasil+dibuat");
} else {
    error_log("Database error: " . mysqli_error($koneksi));
    header("Location: pengajuan.php?error=1&message=" . urlencode("Gagal menyimpan data: " . mysqli_error($koneksi)));
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);
exit();
?>