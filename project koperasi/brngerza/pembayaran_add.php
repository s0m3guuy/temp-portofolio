<?php
require 'auth.php';
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: laporan3.php');
    exit;
}

$pinjaman_id  = (int)($_POST['pinjaman_id'] ?? 0);
$anggota_id   = (int)($_POST['anggota_id'] ?? 0);
$angsuran_ke  = (int)($_POST['angsuran_ke'] ?? 0);
$jumlah_bayar = (float)($_POST['jumlah_bayar'] ?? 0);
$tanggal_bayar = str_replace('T', ' ', $_POST['tanggal_bayar'] ?? '');
if ($tanggal_bayar && strlen($tanggal_bayar) === 16) {
    $tanggal_bayar .= ':00'; // add seconds if missing (datetime-local gives YYYY-MM-DD HH:mm)
}
$keterangan   = trim($_POST['keterangan'] ?? '');

// Basic validation
if (!$pinjaman_id || !$anggota_id || !$angsuran_ke || !$jumlah_bayar || !$tanggal_bayar) {
    header('Location: laporan3.php?error=1&message=' . urlencode('Semua field wajib diisi!'));
    exit;
}

// Check pinjaman exists and is aktif
$cek = mysqli_query($koneksi, "SELECT id FROM pinjaman WHERE id = $pinjaman_id AND status_pinjaman = 'aktif'");
if (!$cek || mysqli_num_rows($cek) === 0) {
    header('Location: laporan3.php?error=1&message=' . urlencode('Pinjaman tidak ditemukan atau tidak aktif.'));
    exit;
}

// Check angsuran_ke not already paid
$cekDuplikat = mysqli_query($koneksi,
    "SELECT id FROM pembayaran WHERE pinjaman_id = $pinjaman_id AND angsuran_ke = $angsuran_ke"
);
if ($cekDuplikat && mysqli_num_rows($cekDuplikat) > 0) {
    header('Location: laporan3.php?error=1&message=' . urlencode("Angsuran ke-$angsuran_ke untuk pinjaman ini sudah diinput!"));
    exit;
}

$keteranganEsc = mysqli_real_escape_string($koneksi, $keterangan);
$tanggalEsc    = mysqli_real_escape_string($koneksi, $tanggal_bayar);

$sql = "INSERT INTO pembayaran (anggota_id, pinjaman_id, angsuran_ke, jumlah_bayar, tanggal_bayar, keterangan)
        VALUES ($anggota_id, $pinjaman_id, $angsuran_ke, $jumlah_bayar, '$tanggalEsc', '$keteranganEsc')";

if (mysqli_query($koneksi, $sql)) {
    header('Location: laporan3.php?success=1&message=' . urlencode('Pembayaran berhasil disimpan!'));
} else {
    header('Location: laporan3.php?error=1&message=' . urlencode('Gagal menyimpan: ' . mysqli_error($koneksi)));
}
exit;