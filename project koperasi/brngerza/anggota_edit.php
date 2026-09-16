<?php
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $usaha = $_POST['usaha'] ?? '';
    $no_telp = $_POST['no_telp'];
    $email = $_POST['email'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $nik = $_POST['nik'] ?? '';
    $ttl = $_POST['ttl'] ?? '';

    $query = "UPDATE anggota 
              SET nama = ?, usaha = ?, no_telp = ?, email = ?, 
                  alamat = ?, NIK = ?, ttl = ?
              WHERE id = ?";

    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "sssssssi", $nama, $usaha, $no_telp, $email, $alamat, $nik, $ttl, $id);

    if(mysqli_stmt_execute($stmt)) {
        header("Location: anggota.php?success=1&message=Data+anggota+berhasil+diupdate");
    } else {
        header("Location: anggota.php?error=1&message=" . urlencode("Gagal mengupdate data: " . mysqli_error($koneksi)));
    }

    mysqli_stmt_close($stmt);
    mysqli_close($koneksi);
    exit();
}
?>