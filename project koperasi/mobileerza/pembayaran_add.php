<?php
require 'auth.php';
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: laporan3.php');
    exit;
}

$pinjaman_id  = (int)($_POST['pinjaman_id'] ?? 0);
$anggota_id   = (int)($_POST['anggota_id'] ?? 0);
$tanggal_bayar = str_replace('T', ' ', $_POST['tanggal_bayar'] ?? '');
if ($tanggal_bayar && strlen($tanggal_bayar) === 16) {
    $tanggal_bayar .= ':00'; // add seconds if missing (datetime-local gives YYYY-MM-DD HH:mm)
}
$keterangan   = trim($_POST['keterangan'] ?? '');

// Basic validation (jumlah_bayar and angsuran_ke are no longer taken from the client at all)
if (!$pinjaman_id || !$anggota_id || !$tanggal_bayar) {
    header('Location: laporan3.php?error=1&message=' . urlencode('Semua field wajib diisi!'));
    exit;
}

// Check pinjaman exists and is aktif, and look up the fixed minimum installment
// amount from the loan record itself -- never trust an amount from the browser.
$stmt = mysqli_prepare($koneksi, "SELECT bayar_angsuran, jangka_waktu FROM pinjaman WHERE id = ? AND status_pinjaman = 'aktif'");
mysqli_stmt_bind_param($stmt, "i", $pinjaman_id);
mysqli_stmt_execute($stmt);
$pinjamanRow = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$pinjamanRow) {
    header('Location: laporan3.php?error=1&message=' . urlencode('Pinjaman tidak ditemukan atau tidak aktif.'));
    exit;
}

$jumlah_bayar = (float)$pinjamanRow['bayar_angsuran'];
$jangka_waktu = (int)$pinjamanRow['jangka_waktu'];
if ($jumlah_bayar <= 0) {
    header('Location: laporan3.php?error=1&message=' . urlencode('Jumlah angsuran minimum untuk pinjaman ini tidak valid.'));
    exit;
}

// Determine the next angsuran_ke ourselves from how many payments already
// exist for this loan -- the client only ever sees this as a readonly display.
$stmtCount = mysqli_prepare($koneksi, "SELECT COUNT(*) as total FROM pembayaran WHERE pinjaman_id = ?");
mysqli_stmt_bind_param($stmtCount, "i", $pinjaman_id);
mysqli_stmt_execute($stmtCount);
$sudahBayar = (int)(mysqli_stmt_get_result($stmtCount)->fetch_assoc()['total']);
mysqli_stmt_close($stmtCount);

$angsuran_ke = $sudahBayar + 1;

if ($jangka_waktu && $angsuran_ke > $jangka_waktu) {
    header('Location: laporan3.php?error=1&message=' . urlencode('Pinjaman ini sudah lunas — semua angsuran telah tercatat.'));
    exit;
}

// Safety net against two near-simultaneous submissions for the same loan
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
    header('Location: anggota.php?success=1&message=' . urlencode('Pembayaran berhasil disimpan!'));
} else {
    header('Location: anggota.php?error=1&message=' . urlencode('Gagal menyimpan: ' . mysqli_error($koneksi)));
}
exit;