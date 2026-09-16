<?php
require 'koneksi.php';

// Get POST data
$nama = $_POST['nama'] ?? '';
$usaha = $_POST['usaha'] ?? '';
$no_telp = $_POST['no_telp'] ?? '';
$email = $_POST['email'] ?? '';
$alamat = $_POST['alamat'] ?? '';
$nik = $_POST['nik'] ?? '';
$ttl = $_POST['ttl'] ?? '';

// Validate required fields
if (empty($nama) || empty($no_telp)) {
    header("Location: anggota.php?error=1&message=Nama+dan+No+Telepon+wajib+diisi");
    exit();
}

// Use prepared statement to prevent SQL injection
$query = "INSERT INTO anggota (nama, usaha, no_telp, email, alamat, NIK, ttl, joindate, status) 
          VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'aktif')";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "sssssss", $nama, $usaha, $no_telp, $email, $alamat, $nik, $ttl);

if(mysqli_stmt_execute($stmt)) {
    header("Location: anggota.php?success=1&message=Anggota+berhasil+ditambahkan");
} else {
    header("Location: anggota.php?error=1&message=" . urlencode("Gagal menambahkan anggota: " . mysqli_error($koneksi)));
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);
exit();
?>