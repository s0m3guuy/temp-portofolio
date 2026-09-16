<?php
require 'auth.php';
require 'koneksi.php';

// Get statistics for dashboard

// 1. Total active members
$queryAnggota = "SELECT COUNT(*) as total FROM anggota WHERE status='aktif'";
$resultAnggota = mysqli_query($koneksi, $queryAnggota);
$totalAnggota = mysqli_fetch_assoc($resultAnggota)['total'];

// 2. Total loans (all statuses)
$queryPinjaman = "SELECT COUNT(*) as total FROM pinjaman";
$resultPinjaman = mysqli_query($koneksi, $queryPinjaman);
$totalPinjaman = mysqli_fetch_assoc($resultPinjaman)['total'];

// 3. Active loans (pending or aktif status)
$queryPinjamanAktif = "SELECT COUNT(*) as total FROM pinjaman WHERE status_pinjaman IN ('aktif', 'pending')";
$resultPinjamanAktif = mysqli_query($koneksi, $queryPinjamanAktif);
$totalPinjamanAktif = mysqli_fetch_assoc($resultPinjamanAktif)['total'];

// Today's date
$today = date('Y-m-d');
$queryPengajuanHari = "SELECT COUNT(*) as total FROM pinjaman WHERE DATE(created_at) = '$today'";
$resultPengajuanHari = mysqli_query($koneksi, $queryPengajuanHari);
$pengajuanHari = mysqli_fetch_assoc($resultPengajuanHari)['total'];

// This month
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$queryPengajuanBulan = "SELECT COUNT(*) as total FROM pinjaman WHERE DATE(created_at) BETWEEN '$monthStart' AND '$monthEnd'";
$resultPengajuanBulan = mysqli_query($koneksi, $queryPengajuanBulan);
$pengajuanBulan = mysqli_fetch_assoc($resultPengajuanBulan)['total'];

// Get recent loans (ALL loans, not just aktif) - FIXED HERE
$queryLoans = "SELECT p.*, a.nama, a.usaha 
               FROM pinjaman p 
               JOIN anggota a ON p.anggota_id = a.id 
               ORDER BY p.created_at DESC 
               LIMIT 10";
$resultLoans = mysqli_query($koneksi, $queryLoans);

// Get total loan amount
$queryTotalAmount = "SELECT SUM(pinjaman) as total FROM pinjaman WHERE status_pinjaman IN ('aktif', 'pending')";
$resultTotalAmount = mysqli_query($koneksi, $queryTotalAmount);
$totalAmountRow = mysqli_fetch_assoc($resultTotalAmount);
$totalAmount = $totalAmountRow['total'] ?? 0;


// Build payment schedule for calendar
$calendarEvents = [];

$pinjamanQuery = "SELECT p.id, p.kode_pinjaman, p.tanggal_pengambilan,
                  p.bayar_angsuran, p.jangka_waktu, a.nama
                  FROM pinjaman p
                  JOIN anggota a ON p.anggota_id = a.id";

$pinjamanResult = mysqli_query($koneksi, $pinjamanQuery);

while ($loan = mysqli_fetch_assoc($pinjamanResult)) {
    $startDate = new DateTime($loan['tanggal_pengambilan']);
    $today = new DateTime();

    for ($n = 1; $n <= $loan['jangka_waktu']; $n++) {
        $dueDate = clone $startDate;
        $dueDate->modify("+{$n} month");

        // Check if this installment was paid
        $checkQuery = "SELECT SUM(jumlah_bayar) as total FROM pembayaran 
                        WHERE pinjaman_id = {$loan['id']} AND angsuran_ke = {$n}";
        $checkResult = mysqli_fetch_assoc(mysqli_query($koneksi, $checkQuery));
        $paidAmount = $checkResult['total'] ?? 0;

        if ($paidAmount >= $loan['bayar_angsuran']) {
            $status = 'paid';
            $color = '#38ef7d'; // green
        } elseif ($dueDate < $today) {
            $status = 'overdue';
            $color = '#e74c3c'; // red
        } else {
            $status = 'upcoming';
            $color = '#f39c12'; // orange
        }

        $calendarEvents[] = [
            'title' => $loan['nama'] . ' (Rp ' . number_format($loan['bayar_angsuran'], 0, ',', '.') . ')',
            'start' => $dueDate->format('Y-m-d'),
            'color' => $color,
            'extendedProps' => [
                'kode_pinjaman' => $loan['kode_pinjaman'],
                'angsuran_ke' => $n,
                'jangka_waktu' => $loan['jangka_waktu'],
                'status' => $status
            ]
        ];
    }
}
$calendarEventsJson = json_encode($calendarEvents);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .topbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .brand {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .logout {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        .logout:hover {
            color: #e2e8f0;
        }
        .layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            transition: all 0.3s;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar.collapsed {
            width: 70px;
        }
        .sidebar .logo {
            width: 80%;
            margin: 0 auto 20px;
            display: block;
            border-radius: 10px;
        }
        .sidebar.collapsed .logo {
            width: 50px;
        }
        .sidebar nav a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        .sidebar nav a:hover {
            background: #34495e;
            color: #fff;
            border-left-color: #667eea;
        }
        .sidebar nav a.active {
            background: #34495e;
            border-left-color: #667eea;
            font-weight: 500;
        }
        .sidebar nav a i {
            margin-right: 10px;
            font-size: 1.2rem;
            min-width: 24px;
        }
        .sidebar.collapsed nav a span {
            display: none;
        }
        .sidebar.collapsed nav a i {
            margin-right: 0;
            font-size: 1.5rem;
        }
        .content {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
        }
        .page-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .page-title h3 {
            margin: 0;
            font-weight: 600;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: none;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h6 {
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            line-height: 1;
        }
        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        .stat-row {
            display: flex;
            justify-content: space-between;
            text-align: center;
            gap: 15px;
        }
        .stat-row > div {
            flex: 1;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
            padding: 18px 25px;
            font-size: 1.1rem;
        }
        .loan-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s;
        }
        .loan-item:last-child {
            border-bottom: none;
        }
        .loan-item:hover {
            background-color: #f7fafc;
        }
        .loan-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .loan-info {
            flex: 1;
        }
        .loan-info strong {
            color: #2d3748;
            font-weight: 600;
            display: block;
            margin-bottom: 3px;
        }
        .loan-info small {
            color: #718096;
            font-size: 0.85rem;
        }
        .loan-amount {
            color: #48bb78;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .btn-light {
            background: white;
            border: 1px solid #e2e8f0;
            color: #4a5568;
            font-weight: 500;
        }
        .btn-light:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        .icon-bg-1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .icon-bg-2 {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
            color: white;
        }
        .icon-bg-3 {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            color: white;
        }
        .icon-bg-4 {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }
        .icon-bg-5 {
            background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%);
            color: white;
        }
        .icon-bg-6 {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
            color: white;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }
        .dashboard-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .section-title {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #a0aec0;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        .loan-status {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-left: 8px;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-aktif {
            background-color: #d4edda;
            color: #155724;
        }
        .status-lunas {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-terlambat {
            background-color: #f8d7da;
            color: #721c24;
        }
        .total-amount {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            margin: 10px 0;
        }
          /* ── TABLET (max 992px) ── */

    </style>
</head>
<body>

<!-- NAVBAR -->
<header class="topbar">
    <div class="brand"> 
        <strong>PRI L</strong>ink
        <button id="sidebarToggle" class="btn btn-sm btn-light ms-3">
            <i class="bi bi-list"></i>
        </button>
    </div>
    <a href="logout.php" class="logout"> 
        Logout <i class="bi bi-box-arrow-right ms-1"></i>
    </a>
  <link rel="stylesheet" href="mobile.css">
</header>

<div class="layout">
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <img src="gmbr/logo.png" class="logo">
            <nav>
                <a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                <a href="anggota.php"><i class="bi bi-people"></i><span>Anggota</span></a>
                <a href="blacklist.php"><i class="bi bi-x-circle"></i><span>Blacklist</span></a>
                <a href="tabungan.php"><i class="bi bi-wallet2"></i><span>Tabungan</span></a>
                <a href="libur.php"><i class="bi bi-calendar"></i><span>Libur</span></a>

            </nav>

            <style>
            .nav-dropdown { position: relative; }

            .nav-dropdown-toggle {
                display: flex;
                align-items: center;
                justify-content: space-between;
                cursor: pointer;
            }

            .dropdown-arrow {
                margin-left: auto;
                transition: transform 0.3s ease;
            }

            .nav-dropdown.open .dropdown-arrow {
                transform: rotate(180deg);
            }

            .nav-dropdown-menu {
                display: none;
                flex-direction: column;
                padding-left: 1rem; /* indent sub-items */
            }

            .nav-dropdown.open .nav-dropdown-menu {
                display: flex;
            }
            </style>

            <script>
            function toggleDropdown(e) {
                e.preventDefault();
                e.currentTarget.closest('.nav-dropdown').classList.toggle('open');
            }
            </script>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="content">
        <div class="page-title">
            <h3>Dashboard Overview</h3>
            <p class="mb-0 opacity-75">Ringkasan statistik dan aktivitas terbaru</p>
        </div>

        <div class="dashboard-grid">
            <!-- Stat Card 1: Total Anggota -->
            <div class="stat-card">
                <div class="stat-icon icon-bg-1">
                    <i class="bi bi-people"></i>
                </div>
                <h6>Total Anggota</h6>
                <div class="stat-number"><?= number_format($totalAnggota) ?></div>
                <div class="stat-label">Anggota aktif</div>
            </div>

            <!-- Stat Card 2: Total Pinjaman -->
            <div class="stat-card">
                <div class="stat-icon icon-bg-2">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <h6>Total Pinjaman</h6>
                <div class="stat-number"><?= number_format($totalPinjaman) ?></div>
                <div class="stat-label">Semua pengajuan</div>
            </div>

            <!-- Stat Card 3: Pinjaman Aktif -->
            <div class="stat-card">
                <div class="stat-icon icon-bg-3">
                    <i class="bi bi-clock-history"></i>
                </div>
                <h6>Pinjaman Aktif</h6>
                <div class="stat-number"><?= number_format($totalPinjamanAktif) ?></div>
                <div class="stat-label">Pending / Aktif</div>
            </div>

            <!-- Stat Card 4: Pengajuan Hari Ini -->
            <div class="stat-card">
                <div class="stat-icon icon-bg-4">
                    <i class="bi bi-file-earmark-plus"></i>
                </div>
                <h6>Pengajuan Hari Ini</h6>
                <div class="stat-number"><?= number_format($pengajuanHari) ?></div>
                <div class="stat-label"><?= date('d M Y') ?></div>
            </div>

            <!-- Stat Card 5: Total Amount -->
            <div class="stat-card">
                <div class="stat-icon icon-bg-5">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <h6>Total Nilai Pinjaman</h6>
                <div class="total-amount">Rp <?= number_format($totalAmount, 0, ',', '.') ?></div>
                <div class="stat-label">Pinjaman aktif</div>
            </div>

            <!-- Stat Card 6: Pengajuan Bulan Ini -->
            <div class="stat-card">
                <div class="stat-icon icon-bg-6">
                    <i class="bi bi-calendar-month"></i>
                </div>
                <h6>Pengajuan Bulan Ini</h6>
                <div class="stat-number"><?= number_format($pengajuanBulan) ?></div>
                <div class="stat-label"><?= date('M Y') ?></div>
            </div>
        </div>

        <div class="row">
            <!-- LEFT: Recent Loans -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>10 Pengajuan Pinjaman Terbaru</span>
                        <a href="pengajuan.php" class="btn btn-sm btn-light">
                            <i class="bi bi-arrow-right me-1"></i>Lihat Semua
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if(mysqli_num_rows($resultLoans) > 0): ?>
                            <?php while($loan = mysqli_fetch_assoc($resultLoans)): ?>
                                <?php
                                    // Format currency
                                    $amount = number_format($loan['pinjaman'], 0, ',', '.');
                                    $initials = strtoupper(substr($loan['nama'], 0, 2));
                                    
                                    // Status badge
                                    $statusClass = '';
                                    $statusText = '';
                                    switch($loan['status_pinjaman']) {
                                        case 'pending':
                                            $statusClass = 'status-pending';
                                            $statusText = 'Pending';
                                            break;
                                        case 'aktif':
                                            $statusClass = 'status-aktif';
                                            $statusText = 'Aktif';
                                            break;
                                        case 'lunas':
                                            $statusClass = 'status-lunas';
                                            $statusText = 'Lunas';
                                            break;
                                        case 'rejected':
                                            $statusClass = 'status-rejected';
                                            $statusText = 'Ditolak';
                                            break;
                                        case 'terlambat':
                                            $statusClass = 'status-terlambat';
                                            $statusText = 'Terlambat';
                                            break;
                                        default:
                                            $statusClass = 'status-pending';
                                            $statusText = $loan['status_pinjaman'];
                                    }
                                ?>
                                <div class="loan-item">
                                    <div class="loan-avatar">
                                        <?= $initials ?>
                                    </div>
                                    <div class="loan-info">
                                        <div class="d-flex align-items-center">
                                            <strong><?= htmlspecialchars($loan['nama']) ?></strong>
                                            <span class="loan-status <?= $statusClass ?>"><?= $statusText ?></span>
                                        </div>
                                        <small>
                                            <?= htmlspecialchars($loan['usaha']) ?> • 
                                            Jangka: <?= $loan['jangka_waktu'] ?> bulan • 
                                            Bunga: <?= $loan['bunga'] ?>%
                                        </small>
                                    </div>
                                    <div class="loan-amount">
                                        Rp <?= $amount ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-cash-stack"></i>
                                <p>Belum ada pengajuan pinjaman</p>
                                <a href="pengajuan.php" class="btn btn-primary mt-3">
                                    <i class="bi bi-plus-circle me-2"></i>Tambah Pengajuan Pertama
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="card">
                            <div class="card-header"><span><i class="bi bi-calendar3 me-2"></i>Kalender Pembayaran</span></div>
                            <div class="card-body">
                                <div id="paymentCalendar"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Quick Stats -->
            <div class="col-lg-4">
                <div class="dashboard-section">
                    <div class="section-title">
                        <span>Quick Actions</span>
                        <i class="bi bi-lightning"></i>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="anggota.php" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-person-plus me-2"></i>Tambah Anggota
                        </a>
                        <a href="pengajuan.php" class="btn btn-outline-success btn-lg">
                            <i class="bi bi-file-earmark-plus me-2"></i>Pengajuan Baru
                        </a>
                    </div>
                </div>

                <div class="dashboard-section mt-4">
                    <div class="section-title">
                        <span>Ringkasan</span>
                        <i class="bi bi-pie-chart"></i>
                    </div>
                    <div class="stat-row">
                        <div>
                            <div class="stat-number text-center"><?= $pengajuanHari ?></div>
                            <div class="stat-label text-center">Hari Ini</div>
                        </div>
                        <div>
                            <div class="stat-number text-center"><?= $pengajuanBulan ?></div>
                            <div class="stat-label text-center">Bulan Ini</div>
                        </div>
                        <div>
                            <div class="stat-number text-center"><?= $totalAnggota ?></div>
                            <div class="stat-label text-center">Total Anggota</div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-section mt-4">
                    <div class="section-title">
                        <span>Sistem Info</span>
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tanggal Hari Ini:</span>
                            <span class="fw-medium"><?= date('d M Y, H:i') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Anggota:</span>
                            <span class="fw-medium"><?= $totalAnggota ?> orang</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Pinjaman:</span>
                            <span class="fw-medium"><?= $totalPinjaman ?> data</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Status Sistem:</span>
                            <span class="badge bg-success">Online</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
// Sidebar toggle
document.getElementById("sidebarToggle").onclick = function () {
    document.getElementById("sidebar").classList.toggle("collapsed");
};

// Auto-refresh dashboard every 60 seconds
setTimeout(function() {
    window.location.reload();
}, 60000);
</script>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('paymentCalendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 600,
        events: <?= $calendarEventsJson ?>,
        eventClick: function(info) {
            var p = info.event.extendedProps;
            alert(
                'Kode Pinjaman: ' + p.kode_pinjaman + '\n' +
                'Angsuran ke: ' + p.angsuran_ke + ' / ' + p.jangka_waktu + '\n' +
                'Status: ' + p.status
            );
        }
    });
    calendar.render();
});

// Sidebar toggle
document.getElementById("sidebarToggle").onclick = function () {
    document.getElementById("sidebar").classList.toggle("collapsed");
};

// Auto-refresh dashboard every 60 seconds
setTimeout(function() {
    window.location.reload();
}, 60000);
</script>
<!-- MOBILE BOTTOM NAV -->
<nav class="mobile-nav">
    <a href="dashboard.php" class="active">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
    </a>
    <a href="anggota.php">
        <i class="bi bi-people"></i>
        <span>Anggota</span>
    </a>

    <a href="blacklist.php">
      <i class="bi bi-x-circle"></i>
      <span>Blacklist</span>
    </a>
  
    <a href="tabungan.php">
        <i class="bi bi-wallet2"></i>
        <span>Tabungan</span>
    </a>

    <a href="libur.php">
      <i class="bi bi-calendar"></i>
      <span>Libur</span>
    </a>
</nav>


</body>
</html>
