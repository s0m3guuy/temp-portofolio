<?php
require 'auth.php';
require 'koneksi.php';

// Initialize variables
$success = false;
$message = '';

// Get loan ID from POST
$loanId = isset($_POST['id']) ? intval($_POST['id']) : 0;
$newBunga = isset($_POST['bunga_baru']) ? floatval($_POST['bunga_baru']) : 0;
$denda = isset($_POST['denda_pinjaman_lama']) ? floatval($_POST['denda_pinjaman_lama']) : 0;
$alasan = isset($_POST['alasan_update']) ? mysqli_real_escape_string($koneksi, $_POST['alasan_update']) : '';

if ($loanId > 0 && $newBunga > 0) {
    // Start transaction
    mysqli_begin_transaction($koneksi);
    
    try {
        // 1. Get current loan details for logging
        $queryGetLoan = "SELECT p.*, a.nama 
                        FROM pinjaman p 
                        JOIN anggota a ON p.anggota_id = a.id 
                        WHERE p.id = ?";
        $stmt = mysqli_prepare($koneksi, $queryGetLoan);
        mysqli_stmt_bind_param($stmt, "i", $loanId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt); // Get the result, not the statement
        $loanDetails = mysqli_fetch_assoc($result); // Use the result here
        
        if (!$loanDetails) {
            throw new Exception("Pinjaman tidak ditemukan!");
        }
        
        $oldBunga = $loanDetails['bunga'];
        $oldTotal = $loanDetails['total_pinjaman_bunga'];
        
        // 2. Calculate new total based on new interest rate
        $pinjamanPokok = $loanDetails['pinjaman'];
        $jangkaWaktu = $loanDetails['jangka_waktu'];
        
        // Calculate new total: pokok + (pokok * bunga/100 * jangka)
        $bungaPeriode = ($pinjamanPokok * $newBunga) / 100;
        $newTotal = $pinjamanPokok + ($bungaPeriode * $jangkaWaktu);
        
        // Calculate new monthly payment
        $newBayarAngsuran = ($jangkaWaktu > 0) ? $newTotal / $jangkaWaktu : 0;
        
        // 3. Update the loan with new interest rate
        $queryUpdate = "UPDATE pinjaman 
                       SET bunga = ?, 
                           total_pinjaman_bunga = ?, 
                           bayar_angsuran = ?, 
                           denda_pinjaman_lama = ?,
                           updated_at = NOW()
                       WHERE id = ?";
        $stmt = mysqli_prepare($koneksi, $queryUpdate);
        mysqli_stmt_bind_param($stmt, "dddii", $newBunga, $newTotal, $newBayarAngsuran, $denda, $loanId);
        $updateResult = mysqli_stmt_execute($stmt);
        
        if (!$updateResult) {
            throw new Exception("Gagal mengupdate pinjaman: " . mysqli_error($koneksi));
        }
        
        // 4. Create bunga_history table if it doesn't exist
        $createHistoryTable = "CREATE TABLE IF NOT EXISTS bunga_history (
            id INT PRIMARY KEY AUTO_INCREMENT,
            pinjaman_id INT NOT NULL,
            bunga_lama DECIMAL(5,2) NOT NULL,
            bunga_baru DECIMAL(5,2) NOT NULL,
            total_lama DECIMAL(15,2) NOT NULL,
            total_baru DECIMAL(15,2) NOT NULL,
            alasan TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        mysqli_query($koneksi, $createHistoryTable);
        
        // 5. Log the interest rate change
        $queryLog = "INSERT INTO bunga_history 
                    (pinjaman_id, bunga_lama, bunga_baru, total_lama, total_baru, alasan) 
                    VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($koneksi, $queryLog);
        mysqli_stmt_bind_param($stmt, "idddds", $loanId, $oldBunga, $newBunga, $oldTotal, $newTotal, $alasan);
        $logResult = mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($koneksi);
        
        $success = true;
        $message = "Bunga pinjaman berhasil diupdate dari {$oldBunga}% menjadi {$newBunga}%";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($koneksi);
        $message = "Gagal mengupdate bunga: " . $e->getMessage();
    }
} else {
    $message = "Data tidak valid. Pastikan ID pinjaman dan bunga baru diisi dengan benar.";
}

// Redirect back with status message
$status = $success ? 'success' : 'error';
header("Location: penggajuanbunga.php?{$status}=1&message=" . urlencode($message));
exit();
?>