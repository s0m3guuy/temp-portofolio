<?php
require 'auth.php';
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tabungan.php");
    exit();
}

$anggota_id     = intval($_POST['anggota_id']);
$jumlah         = floatval($_POST['jumlah']);
$tanggal_tarik  = trim($_POST['tanggal_setor']); // same column as deposits, just a transaction date
$keterangan     = !empty($_POST['keterangan']) ? trim($_POST['keterangan']) : '';

if (!$anggota_id || !$jumlah || $jumlah <= 0 || empty($tanggal_tarik)) {
    header("Location: tabungan.php?error=1&message=" . urlencode("Harap lengkapi semua field yang wajib diisi!"));
    exit();
}

// Compute this member's current balance (deposits minus withdrawals so far)
// server-side -- never trust a balance value from the browser.
$stmtSaldo = mysqli_prepare($koneksi,
    "SELECT COALESCE(SUM(CASE WHEN jenis = 'tarik' THEN -jumlah ELSE jumlah END), 0) as saldo
     FROM tabungan WHERE anggota_id = ?");
mysqli_stmt_bind_param($stmtSaldo, 'i', $anggota_id);
mysqli_stmt_execute($stmtSaldo);
$saldo = (float) mysqli_stmt_get_result($stmtSaldo)->fetch_assoc()['saldo'];
mysqli_stmt_close($stmtSaldo);

if ($jumlah > $saldo) {
    header("Location: tabungan.php?error=1&message=" . urlencode(
        "Saldo tidak mencukupi. Saldo saat ini: Rp " . number_format($saldo, 0, ',', '.')
    ));
    exit();
}

$query = "INSERT INTO tabungan (anggota_id, jenis, jumlah, tanggal_setor, keterangan, created_at)
          VALUES (?, 'tarik', ?, ?, ?, NOW())";
$stmt  = mysqli_prepare($koneksi, $query);

if (!$stmt) {
    header("Location: tabungan.php?error=1&message=" . urlencode("Prepare failed: " . mysqli_error($koneksi)));
    exit();
}

mysqli_stmt_bind_param($stmt, 'idss', $anggota_id, $jumlah, $tanggal_tarik, $keterangan);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($koneksi);
    header("Location: tabungan.php?success=1&message=" . urlencode("Penarikan tabungan berhasil disimpan!"));
} else {
    $error = mysqli_error($koneksi);
    mysqli_stmt_close($stmt);
    mysqli_close($koneksi);
    header("Location: tabungan.php?error=1&message=" . urlencode("Gagal menyimpan: " . $error));
}
exit();
?>