<?php
require 'auth.php';
require 'koneksi.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anggota_id    = intval($_POST['anggota_id']);
    $jumlah        = floatval($_POST['jumlah']);
    $tanggal_setor = trim($_POST['tanggal_setor']);
    $keterangan    = !empty($_POST['keterangan']) ? trim($_POST['keterangan']) : '';

    if(!$anggota_id || !$jumlah || empty($tanggal_setor)) {
        header("Location: tabungan.php?error=1&message=" . urlencode("Harap lengkapi semua field yang wajib diisi!"));
        exit();
    }

    $query = "INSERT INTO tabungan (anggota_id, jenis, jumlah, tanggal_setor, keterangan, created_at)
              VALUES (?, 'setor', ?, ?, ?, NOW())";
    $stmt  = mysqli_prepare($koneksi, $query);

    if(!$stmt) {
        header("Location: tabungan.php?error=1&message=" . urlencode("Prepare failed: " . mysqli_error($koneksi)));
        exit();
    }

    mysqli_stmt_bind_param($stmt, 'idss', $anggota_id, $jumlah, $tanggal_setor, $keterangan);

    if(mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($koneksi);
        header("Location: tabungan.php?success=1&message=" . urlencode("Setoran tabungan berhasil disimpan!"));
    } else {
        $error = mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
        mysqli_close($koneksi);
        header("Location: tabungan.php?error=1&message=" . urlencode("Gagal menyimpan: " . $error));
    }
    exit();
} else {
    header("Location: tabungan.php");
    exit();
}
?>