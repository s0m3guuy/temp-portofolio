<?php
require 'auth.php';
require 'koneksi.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Debug: check what we received
    $anggota_id    = intval($_POST['anggota_id']);
    $pinjaman_id   = intval($_POST['pinjaman_id']);
    $angsuran_ke   = intval($_POST['angsuran_ke']);
    $tanggal_libur = trim($_POST['tanggal_libur']);
    $alasan        = trim($_POST['alasan']);
    $keterangan    = !empty($_POST['keterangan']) ? trim($_POST['keterangan']) : '';

    // Validate required fields
    if(!$anggota_id || !$angsuran_ke || empty($tanggal_libur) || empty($alasan)) {
        $missing = [];
        if(!$anggota_id)        $missing[] = 'anggota_id';
        if(!$angsuran_ke)       $missing[] = 'angsuran_ke';
        if(empty($tanggal_libur)) $missing[] = 'tanggal_libur';
        if(empty($alasan))      $missing[] = 'alasan';
        
        header("Location: libur.php?error=1&message=" . urlencode("Field kosong: " . implode(', ', $missing)));
        exit();
    }

    // pinjaman_id is optional (can be 0 if not found), use NULL if 0
    $pinjaman_id_val = $pinjaman_id > 0 ? $pinjaman_id : null;

    $query = "INSERT INTO libur (anggota_id, pinjaman_id, angsuran_ke, tanggal_libur, alasan, keterangan, status, created_at)
              VALUES (?, ?, ?, ?, ?, ?, 'libur', NOW())";
    
    $stmt = mysqli_prepare($koneksi, $query);
    
    if(!$stmt) {
        header("Location: libur.php?error=1&message=" . urlencode("Prepare failed: " . mysqli_error($koneksi)));
        exit();
    }

    // Use NULL for pinjaman_id if not provided
    if($pinjaman_id_val === null) {
        mysqli_stmt_bind_param($stmt, 'iissss', 
            $anggota_id, 
            $pinjaman_id_val,  // will bind as NULL
            $angsuran_ke, 
            $tanggal_libur, 
            $alasan, 
            $keterangan
        );
    } else {
        mysqli_stmt_bind_param($stmt, 'iiisss', 
            $anggota_id, 
            $pinjaman_id_val, 
            $angsuran_ke, 
            $tanggal_libur, 
            $alasan, 
            $keterangan
        );
    }

    if(mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($koneksi);
        header("Location: libur.php?success=1&message=" . urlencode("Data angsuran libur berhasil disimpan!"));
        exit();
    } else {
        $error = mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
        mysqli_close($koneksi);
        header("Location: libur.php?error=1&message=" . urlencode("Gagal menyimpan: " . $error));
        exit();
    }

} else {
    header("Location: libur.php");
    exit();
}
?>