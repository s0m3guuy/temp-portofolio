<?php
require 'auth.php';
require 'koneksi.php';

header('Content-Type: application/json');

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Check if member is blacklisted
    $blacklistCheck = "SELECT b.alasan FROM blacklist b WHERE b.anggota_id = ?";
    $blStmt = mysqli_prepare($koneksi, $blacklistCheck);
    mysqli_stmt_bind_param($blStmt, 'i', $id);
    mysqli_stmt_execute($blStmt);
    $blResult = mysqli_stmt_get_result($blStmt);

    if(mysqli_num_rows($blResult) > 0) {
        $blRow = mysqli_fetch_assoc($blResult);
        // Return blacklisted flag so frontend can block the form
        echo json_encode([
            'blacklisted' => true,
            'alasan'      => $blRow['alasan']
        ]);
        exit();
    }

    // Not blacklisted — return normal member details
    $query = "SELECT * FROM anggota WHERE id = ?";
    $stmt  = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if($row = mysqli_fetch_assoc($result)) {
        echo json_encode([
            'blacklisted'    => false,
            'nama'           => $row['nama'],
            'usaha'          => $row['usaha'],
            'no_telp'        => $row['no_telp'],
            'email'          => $row['email'],
            'alamat'         => $row['alamat'],
            'nik'            => $row['NIK'],
            'ttl'            => $row['ttl'],
            'joindate'       => $row['joindate'],
        ]);
    } else {
        echo json_encode(['error' => 'Member not found']);
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($koneksi);
?>