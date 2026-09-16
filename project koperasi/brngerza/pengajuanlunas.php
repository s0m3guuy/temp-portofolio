<?php
require 'auth.php';
require 'koneksi.php';

// Get all loan applications pending lunas approval
$query = "SELECT p.*, a.nama, a.usaha 
          FROM pinjaman p 
          JOIN anggota a ON p.anggota_id = a.id 
          WHERE p.status_pinjaman IN ('aktif', 'lunas', 'pending_lunas')
          ORDER BY p.created_at DESC 
          LIMIT 10";
$result = mysqli_query($koneksi, $query);

// Get total count for pagination
$queryCount = "SELECT COUNT(*) as total FROM pinjaman WHERE status_pinjaman IN ('aktif', 'lunas', 'pending_lunas')";
$resultCount = mysqli_query($koneksi, $queryCount);
$rowCount = mysqli_fetch_assoc($resultCount);
$totalEntries = $rowCount['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Lunas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { background-color: #f8f9fa; }
        .topbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 15px 20px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .brand { font-size: 1.5rem; font-weight: bold; }
        .logout { color: white; text-decoration: none; }
        .layout { display: flex; min-height: 100vh; }
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
        .content { flex: 1; padding: 20px; overflow-y: auto; }
        .card { border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; border: none; }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border-radius: 10px 10px 0 0 !important;
            font-weight: bold; padding: 15px 20px;
        }
        .table th { background-color: #f8f9fa; font-weight: 600; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-pending  { background-color: #fff3cd; color: #856404; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .action-buttons .btn { margin-right: 5px; padding: 5px 10px; }
        .pagination-container {
            display: flex; justify-content: space-between;
            align-items: center; margin-top: 20px; padding: 10px 0;
        }
        .dataTables_info { color: #6c757d; }
        .modal-content { border-radius: 10px; border: none; }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border-radius: 10px 10px 0 0;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%); }
        .form-label { font-weight: 500; color: #495057; }
    </style>
</head>
<body>

<?php
if(isset($_GET['success'])) {
    $message = isset($_GET['message']) ? urldecode($_GET['message']) : 'Operasi berhasil!';
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            ' . htmlspecialchars($message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}
if(isset($_GET['error'])) {
    $message = isset($_GET['message']) ? urldecode($_GET['message']) : 'Terjadi kesalahan!';
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            ' . htmlspecialchars($message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}
?>

<!-- NAVBAR -->
<header class="topbar">
    <div class="brand">
        <strong>PRI Link</strong>
        <button id="sidebarToggle" class="btn btn-sm btn-light ms-3">
            <i class="bi bi-list"></i>
        </button>
    </div>
    <a href="logout.php" class="logout">
        Logout <i class="bi bi-box-arrow-right ms-1"></i>
    </a>
</header>

<div class="layout">
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <img src="gmbr/logo.png" class="logo">
            <nav>
                <a href="dashboard.php"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                <a href="anggota.php"><i class="bi bi-people"></i><span>Anggota</span></a>
                <a href="pengajuan.php"><i class="bi bi-file-earmark"></i><span>Pengajuan</span></a>
                <a href="pengajuanbunga.php"><i class="bi bi-percent"></i><span>Pengajuan Bunga</span></a>
                <a href="pengajuanlunas.php" class="active"><i class="bi bi-check-circle"></i><span>Pengajuan Lunas</span></a>
                <a href="blacklist.php"><i class="bi bi-x-circle"></i><span>Blacklist</span></a>
                <a href="tabungan.php"><i class="bi bi-wallet2"></i><span>Tabungan</span></a>
                <a href="libur.php"><i class="bi bi-calendar"></i><span>Libur</span></a>

                <!-- Dropdown -->
                <div class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle" onclick="toggleDropdown(event)">
                        <i class="bi bi-graph-up"></i><span>Laporan</span>
                        <i class="bi bi-chevron-down dropdown-arrow"></i>
                    </a>
                    <div class="nav-dropdown-menu">
                        <a href="laporan1.php"><i class="bi bi-file-earmark-text"></i><span>Bunga</span></a>
                        <a href="laporan2.php"><i class="bi bi-file-earmark-bar-graph"></i><span>Keuangan</span></a>
                        <a href="laporan3.php"><i class="bi bi-file-earmark-spreadsheet"></i><span>Anggota</span></a>
                    </div>
                </div>
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
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Anggota Lunas Pinjaman</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="lunasTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Anggota</th>
                                <th>Jenis Usaha</th>
                                <th>Jumlah Pinjaman</th>
                                <th>Jangka Waktu</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php if(isset($result) && mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <?php
                                        // Status logic
                                        if($row['status_pinjaman'] == 'lunas') {
                                            $statusClass = 'status-approved';
                                            $statusText  = 'Sudah Lunas';
                                        } elseif($row['status_pinjaman'] == 'pending_lunas') {
                                            $statusClass = 'status-pending';
                                            $statusText  = 'Menunggu Konfirmasi';
                                        } else {
                                            $statusClass = 'status-pending';
                                            $statusText  = 'Belum Lunas';
                                        }

                                        $jumlahPinjaman = number_format($row['pinjaman'], 0, ',', '.');
                                        $totalBunga     = ($row['pinjaman'] * $row['bunga']) / 100;
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama']) ?> - <?= !empty($row['kode_pinjaman']) ? htmlspecialchars(substr($row['kode_pinjaman'], -1)) : 'A' ?></td>
                                        <td><?= htmlspecialchars($row['usaha']) ?></td>
                                        <td>Rp <?= $jumlahPinjaman ?></td>
                                        <td><?= $row['jangka_waktu'] ?> Kali</td>
                                        <td>
                                            <span class="status-badge <?= $statusClass ?>">
                                                <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <!-- View Button -->
                                            <button class="btn btn-info btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewModal<?= $row['id'] ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>

                                            <!-- Approve Lunas Button -->
                                            <button class="btn btn-success btn-sm"
                                                    onclick="updateLunas(<?= $row['id'] ?>, 'lunas')"
                                                    title="Konfirmasi Lunas"
                                                    <?= $row['status_pinjaman'] == 'lunas' ? 'disabled' : '' ?>>
                                                <i class="bi bi-check-circle"></i>
                                            </button>

                                            <!-- Deny / Revert Button -->
                                            <button class="btn btn-danger btn-sm"
                                                    onclick="updateLunas(<?= $row['id'] ?>, 'aktif')"
                                                    title="Batalkan Lunas"
                                                    <?= $row['status_pinjaman'] == 'aktif' ? 'disabled' : '' ?>>
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- View Modal -->
                                    <div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detail Pinjaman Lunas</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Nama Anggota:</label>
                                                                <p class="form-control-static"><?= htmlspecialchars($row['nama']) ?></p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Jenis Usaha:</label>
                                                                <p class="form-control-static"><?= htmlspecialchars($row['usaha']) ?></p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Jumlah Pinjaman:</label>
                                                                <p class="form-control-static">Rp <?= number_format($row['pinjaman'], 0, ',', '.') ?></p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Bunga:</label>
                                                                <p class="form-control-static"><?= $row['bunga'] ?>%</p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Total Bunga:</label>
                                                                <p class="form-control-static">Rp <?= number_format($totalBunga, 0, ',', '.') ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Total Pinjaman + Bunga:</label>
                                                                <p class="form-control-static">Rp <?= number_format($row['total_pinjaman_bunga'], 0, ',', '.') ?></p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Bayar per Angsuran:</label>
                                                                <p class="form-control-static">Rp <?= number_format($row['bayar_angsuran'], 0, ',', '.') ?></p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Jangka Waktu:</label>
                                                                <p class="form-control-static"><?= $row['jangka_waktu'] ?> Kali</p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Biaya Admin:</label>
                                                                <p class="form-control-static">Rp <?= number_format($row['biaya_admin'], 0, ',', '.') ?></p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Terima Pinjaman:</label>
                                                                <p class="form-control-static">Rp <?= number_format($row['terima_pinjaman'], 0, ',', '.') ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Tanggal Pengambilan:</label>
                                                                <p class="form-control-static">
                                                                    <?= !empty($row['tanggal_pengambilan']) ? date('d F Y', strtotime($row['tanggal_pengambilan'])) : '-' ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Jatuh Tempo:</label>
                                                                <p class="form-control-static">
                                                                    <?= !empty($row['jatuh_tempo']) ? date('d F Y', strtotime($row['jatuh_tempo'])) : '-' ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Denda Pinjaman Lama:</label>
                                                                <p class="form-control-static">Rp <?= number_format($row['denda_pinjaman_lama'], 0, ',', '.') ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Status:</label>
                                                                <p class="form-control-static">
                                                                    <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    <?php if($row['status_pinjaman'] != 'lunas'): ?>
                                                    <button type="button" class="btn btn-success"
                                                            onclick="updateLunas(<?= $row['id'] ?>, 'lunas')"
                                                            data-bs-dismiss="modal">
                                                        <i class="bi bi-check-circle me-1"></i>Konfirmasi Lunas
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if($row['status_pinjaman'] != 'aktif'): ?>
                                                    <button type="button" class="btn btn-danger"
                                                            onclick="updateLunas(<?= $row['id'] ?>, 'aktif')"
                                                            data-bs-dismiss="modal">
                                                        <i class="bi bi-x-circle me-1"></i>Batalkan Lunas
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data pengajuan lunas</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Info -->
                <div class="pagination-container">
                    <div class="dataTables_info">
                        Showing <?= min(10, $totalEntries) ?> of <?= number_format($totalEntries) ?> entries
                    </div>
                    <div>
                        <button class="btn btn-outline-primary btn-sm" id="prevBtn" disabled>Previous</button>
                        <button class="btn btn-outline-primary btn-sm" id="nextBtn" <?= $totalEntries <= 10 ? 'disabled' : '' ?>>Next</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
document.getElementById("sidebarToggle").onclick = function () {
    document.getElementById("sidebar").classList.toggle("collapsed");
};

$(document).ready(function() {
    $('#lunasTable').DataTable({
        "paging": false,
        "searching": true,
        "ordering": true,
        "info": false,
        "language": {
            "search": "Cari:",
            "zeroRecords": "Tidak ada data ditemukan"
        }
    });
});

function updateLunas(loanId, status) {
    var confirmMsg = status === 'lunas'
        ? 'Konfirmasi pinjaman ini sebagai LUNAS?'
        : 'Batalkan status lunas dan kembalikan ke AKTIF?';

    if(confirm(confirmMsg)) {
        fetch('lunas_update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + loanId + '&status=' + status
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Status berhasil diubah!');
                location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan.');
        });
    }
}
</script>

</body>
</html>
