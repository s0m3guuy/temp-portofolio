<?php

    require 'auth.php';
    require 'koneksi.php';
    
    // Get all loan applications
    $query = "SELECT p.*, a.nama, a.usaha 
              FROM pinjaman p 
              JOIN anggota a ON p.anggota_id = a.id 
              ORDER BY p.created_at DESC 
              LIMIT 10";
    $result = mysqli_query($koneksi, $query);
    
    // Get total count for pagination
    $queryCount = "SELECT COUNT(*) as total FROM pinjaman";
    $resultCount = mysqli_query($koneksi, $queryCount);
    $rowCount = mysqli_fetch_assoc($resultCount);
    $totalEntries = $rowCount['total'];
    
    // Get active members for selection
    $queryAnggota = "SELECT * FROM anggota WHERE status='aktif' ORDER BY nama";
    $resultAnggota = mysqli_query($koneksi, $queryAnggota);
?>

<!DOCTYPE html>
<html lang="en">
<head>



    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Pinjaman</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
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
            padding: 20px;
            overflow-y: auto;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: bold;
            padding: 15px 20px;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .action-buttons .btn {
            margin-right: 5px;
            padding: 5px 10px;
        }
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 10px 0;
        }
        .dataTables_info {
            color: #6c757d;
        }
        .modal-content {
            border-radius: 10px;
            border: none;
        }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .form-group label {
            font-weight: 500;
            margin-bottom: 5px;
            color: #495057;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
    </style>
</head>
<body>
    <?php
    // Display success/error messages
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
        <strong>PRI L</strong>ink
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
                <a href="pengajuan.php" class="active"><i class="bi bi-file-earmark"></i><span>Pengajuan</span></a>
                <a href="pengajuanbunga.php"><i class="bi bi-percent"></i><span>Pengajuan Bunga</span></a>
                <a href="pengajuanlunas.php"><i class="bi bi-check-circle"></i><span>Pengajuan Lunas</span></a>
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
                        <a href="laporan1.php"><i class="bi bi-file-earmark-text"></i><span>Bayar</span></a>
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
                <span>Daftar Pengajuan Pinjaman</span>
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#selectMemberModal">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Pengajuan
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="pengajuanTable" class="table table-hover">
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
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                    // Format status
                                    $statusClass = '';
                                    $statusText = '';
                                    if($row['status_pinjaman'] == 'aktif') {
                                        $statusClass = 'status-approved';
                                        $statusText = 'Sudah Disetujui';
                                    } elseif($row['status_pinjaman'] == 'pending') {
                                        $statusClass = 'status-pending';
                                        $statusText = 'Menunggu Persetujuan';
                                    } else {
                                        $statusClass = 'status-rejected';
                                        $statusText = 'Ditolak';
                                    }
                                    
                                    // Format currency
                                    $jumlahPinjaman = number_format($row['pinjaman'], 0, ',', '.');
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
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
                                        
                                        <!-- Edit Button -->
                                        <button class="btn btn-warning btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?= $row['id'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        
                                        <!-- Approve Button -->
                                        <button class="btn btn-success btn-sm" 
                                                onclick="updateStatus(<?= $row['id'] ?>, 'aktif')">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                        
                                        <!-- Reject Button -->
                                        <button class="btn btn-danger btn-sm" 
                                                onclick="updateStatus(<?= $row['id'] ?>, 'rejected')">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- View Modal -->
                                <div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Detail Pengajuan Pinjaman</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                                            <label class="form-label">Jangka Waktu:</label>
                                                            <p class="form-control-static"><?= $row['jangka_waktu'] ?> Kali</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Bunga:</label>
                                                            <p class="form-control-static"><?= $row['bunga'] ?>%</p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Total Pinjaman + Bunga:</label>
                                                            <p class="form-control-static">Rp <?= number_format($row['total_pinjaman_bunga'], 0, ',', '.') ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Bayar per Angsuran:</label>
                                                            <p class="form-control-static">Rp <?= number_format($row['bayar_angsuran'], 0, ',', '.') ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Status:</label>
                                                            <p class="form-control-static">
                                                                <span class="status-badge <?= $statusClass ?>">
                                                                    <?= $statusText ?>
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-3">
                                                            <label class="form-label">Keterangan:</label>
                                                            <p class="form-control-static">Pengajuan pinjaman untuk <?= htmlspecialchars($row['usaha']) ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="pinjaman_update.php" method="POST">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Pengajuan Pinjaman</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Jumlah Pinjaman</label>
                                                        <input type="number" class="form-control" name="pinjaman" value="<?= $row['pinjaman'] ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Jangka Waktu (Kali)</label>
                                                        <input type="number" class="form-control" name="jangka_waktu" value="<?= $row['jangka_waktu'] ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Bunga (%)</label>
                                                        <input type="number" step="0.01" class="form-control" name="bunga" value="<?= $row['bunga'] ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Status</label>
                                                        <select class="form-control" name="status_pinjaman">
                                                            <option value="pending" <?= $row['status_pinjaman'] == 'pending' ? 'selected' : '' ?>>Menunggu Persetujuan</option>
                                                            <option value="aktif" <?= $row['status_pinjaman'] == 'aktif' ? 'selected' : '' ?>>Sudah Disetujui</option>
                                                            <option value="rejected" <?= $row['status_pinjaman'] == 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination Info -->
                <div class="pagination-container">
                    <div class="dataTables_info">
                        Showing 1 to <?= min(10, $totalEntries) ?> of <?= number_format($totalEntries) ?> entries
                    </div>
                    <div>
                        <button class="btn btn-outline-primary btn-sm" id="prevBtn" disabled>
                            Previous
                        </button>
                        <button class="btn btn-outline-primary btn-sm" id="nextBtn">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- SELECT MEMBER MODAL -->
<div class="modal fade" id="selectMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Anggota</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="selectMemberForm">
                    <div class="mb-3">
                        <label class="form-label">Nama Anggota *</label>
                        <select class="form-control" id="anggota_id" name="anggota_id" required>
                            <option value="">-- Pilih Anggota --</option>
                            <?php while($anggota = mysqli_fetch_assoc($resultAnggota)): ?>
                                <option value="<?= $anggota['id'] ?>">
                                    <?= htmlspecialchars($anggota['nama']) ?> - <?= htmlspecialchars($anggota['usaha']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Usaha *</label>
                        <input type="text" class="form-control" id="jenis_usaha" name="jenis_usaha" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No Telephone *</label>
                        <input type="text" class="form-control" id="no_telepon" name="no_telepon" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat *</label>
                        <textarea class="form-control" id="alamat" name="alamat" readonly rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NIK *</label>
                        <input type="text" class="form-control" id="nik" name="nik" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">TTL *</label>
                        <input type="text" class="form-control" id="ttl" name="ttl" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bergabung Sejak *</label>
                        <input type="text" class="form-control" id="bergabung_sejak" name="bergabung_sejak" readonly>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="nextToLoanForm">Lanjut ke Form Pinjaman</button>
            </div>
        </div>
    </div>
</div>

<!-- LOAN FORM MODAL -->
<div class="modal fade" id="loanFormModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Form Input Pinjaman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="pinjaman_add.php" method="POST" id="loanForm">
                    <input type="hidden" id="member_id" name="anggota_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kode Pinjaman</label>
                                <input type="text" class="form-control" id="kode_pinjaman" name="kode_pinjaman" placeholder="Code Pinjaman">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tipe Pinjaman</label>
                                <select class="form-control" id="tipe_pinjaman" name="tipe_pinjaman" required>
                                    <option value="">-- Pilih Tipe Pinjaman --</option>
                                    <option value="reguler">Reguler</option>
                                    <option value="khusus">Khusus</option>
                                    <option value="mikro">Mikro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tanggal Pengambilan *</label>
                                <input type="date" class="form-control" id="tanggal_pengambilan" name="tanggal_pengambilan" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jatuh Tempo Pelunasan *</label>
                                <input type="date" class="form-control" id="jatuh_tempo" name="jatuh_tempo" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Pinjaman *</label>
                                <input type="number" class="form-control" id="pinjaman" name="pinjaman" required 
                                       placeholder="contoh: 1000000" onchange="calculateTotal()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Jangka Waktu * (Kali)</label>
                                <input type="number" class="form-control" id="jangka_waktu" name="jangka_waktu" required 
                                       placeholder="contoh: 10" onchange="calculateTotal()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Bunga * (%)</label>
                                <input type="number" class="form-control" id="bunga" name="bunga" required 
                                       placeholder="contoh: 10" step="0.01" onchange="calculateTotal()">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Total Pinjaman + Bunga *</label>
                                <input type="text" class="form-control" id="total_pinjaman_bunga" name="total_pinjaman_bunga" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bayar @ angsuran *</label>
                                <input type="number" class="form-control" id="bayar_angsuran" name="bayar_angsuran" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Kekurangan Pinjaman</label>
                                <input type="number" class="form-control" id="kekurangan_pinjaman" name="kekurangan_pinjaman" placeholder="contoh: 100000">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Denda Pinjaman Lama *</label>
                                <input type="number" class="form-control" id="denda_pinjaman_lama" name="denda_pinjaman_lama" required 
                                       placeholder="contoh: 100000" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Biaya Admin</label>
                                <input type="number" class="form-control" id="biaya_admin" name="biaya_admin" placeholder="contoh: 10000">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Terima Pinjaman *</label>
                                <input type="number" class="form-control" id="terima_pinjaman" name="terima_pinjaman" required 
                                       placeholder="contoh: 1000000">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" onclick="submitLoanForm()">Simpan Pinjaman</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
// Sidebar toggle
document.getElementById("sidebarToggle").onclick = function () {
    document.getElementById("sidebar").classList.toggle("collapsed");
};

// Initialize DataTable
$(document).ready(function() {
    $('#pengajuanTable').DataTable({
        "paging": false,
        "searching": true,
        "ordering": true,
        "info": false,
        "language": {
            "search": "Cari:",
            "zeroRecords": "Tidak ada data ditemukan",
            "paginate": {
                "previous": "Sebelumnya",
                "next": "Selanjutnya"
            }
        }
    });
});

// When member is selected, populate member details
document.getElementById('anggota_id').addEventListener('change', function() {
    var memberId = this.value;
    if(memberId) {
        fetch('get_member_details.php?id=' + memberId)
            .then(response => response.json())
            .then(data => {
                // BLACKLIST CHECK
                if(data.blacklisted) {
                    alert('⛔ Anggota ini sedang di-BLACKLIST!\nAlasan: ' + data.alasan + '\n\nAnggota tidak dapat mengajukan pinjaman.');
                    // Reset the dropdown back to empty
                    document.getElementById('anggota_id').value = '';
                    document.getElementById('jenis_usaha').value    = '';
                    document.getElementById('no_telepon').value     = '';
                    document.getElementById('email').value          = '';
                    document.getElementById('alamat').value         = '';
                    document.getElementById('nik').value            = '';
                    document.getElementById('ttl').value            = '';
                    document.getElementById('bergabung_sejak').value = '';
                    return;
                }
 
                // Not blacklisted — fill in fields normally
                document.getElementById('jenis_usaha').value     = data.usaha    || '';
                document.getElementById('no_telepon').value      = data.no_telp  || '';
                document.getElementById('email').value           = data.email    || '';
                document.getElementById('alamat').value          = data.alamat   || '';
                document.getElementById('nik').value             = data.nik      || '';
                document.getElementById('ttl').value             = data.ttl      || '';
                document.getElementById('bergabung_sejak').value = data.joindate || '';
 
                // Generate auto loan code
                var date   = new Date();
                var year   = date.getFullYear().toString().substr(-2);
                var month  = (date.getMonth() + 1).toString().padStart(2, '0');
                var day    = date.getDate().toString().padStart(2, '0');
                var random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                document.getElementById('kode_pinjaman').value = 'P' + year + month + day + random;
            })
            .catch(error => console.error('Error:', error));
    }
});
 

// Move to loan form when Next button is clicked
document.getElementById('nextToLoanForm').addEventListener('click', function() {
    var memberId = document.getElementById('anggota_id').value;
    if(!memberId) {
        alert('Pilih anggota terlebih dahulu!');
        return;
    }
    
    // Set member ID in loan form
    document.getElementById('member_id').value = memberId;
    
    // Close member selection modal
    var memberModal = bootstrap.Modal.getInstance(document.getElementById('selectMemberModal'));
    memberModal.hide();
    
    // Show loan form modal
    var loanModal = new bootstrap.Modal(document.getElementById('loanFormModal'));
    loanModal.show();
    
    // Set default dates
    var today = new Date().toISOString().split('T')[0];
    document.getElementById('tanggal_pengambilan').value = today;
    
    // Set default jatuh tempo to 30 days from today
    var futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 30);
    var futureDateStr = futureDate.toISOString().split('T')[0];
    document.getElementById('jatuh_tempo').value = futureDateStr;
    
    // ADD THIS: Set default values for numeric fields
    document.getElementById('kekurangan_pinjaman').value = 0;
    document.getElementById('denda_pinjaman_lama').value = 0;
    document.getElementById('biaya_admin').value = 0;
});

// Calculate total loan + interest
function calculateTotal() {
    var pinjaman = parseFloat(document.getElementById('pinjaman').value) || 0;
    var jangka = parseFloat(document.getElementById('jangka_waktu').value) || 0;
    var bunga = parseFloat(document.getElementById('bunga').value) || 0;
    
    if(pinjaman > 0 && jangka > 0 && bunga > 0) {
        // Calculate total: principal + (principal * interest * period)
        var bungaAmount = pinjaman * (bunga/100);
        var total = pinjaman + (bungaAmount * jangka);
        document.getElementById('total_pinjaman_bunga').value = Math.round(total);
        
        // Auto-calculate monthly payment
        var monthlyPayment = total / jangka;
        document.getElementById('bayar_angsuran').value = Math.round(monthlyPayment);
        
        // Auto-calculate terima pinjaman (pinjaman - admin fee)
        var biayaAdmin = parseFloat(document.getElementById('biaya_admin').value) || 0;
        var terimaPinjaman = pinjaman - biayaAdmin;
        document.getElementById('terima_pinjaman').value = Math.round(terimaPinjaman);
    }
    
    // Set default 0 for empty fields
    if(!document.getElementById('kekurangan_pinjaman').value) {
        document.getElementById('kekurangan_pinjaman').value = 0;
    }
    if(!document.getElementById('denda_pinjaman_lama').value) {
        document.getElementById('denda_pinjaman_lama').value = 0;
    }
    if(!document.getElementById('biaya_admin').value) {
        document.getElementById('biaya_admin').value = 0;
    }
}


// Update loan status
function updateStatus(loanId, status) {
    if(confirm('Apakah Anda yakin ingin mengubah status pengajuan ini?')) {
        fetch('pinjaman_update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + loanId + '&status=' + status
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Status berhasil diubah!');
                location.reload();
            } else {
                alert('Gagal mengubah status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengubah status.');
        });
    }
}

// Submit loan form
function submitLoanForm() {
    var form = document.getElementById('loanForm');
    var requiredFields = form.querySelectorAll('[required]');
    var valid = true;
    
    // Ensure numeric fields have values
    var numericFields = ['kekurangan_pinjaman', 'denda_pinjaman_lama', 'biaya_admin'];
    numericFields.forEach(function(fieldId) {
        var field = document.getElementById(fieldId);
        if(!field.value || field.value.trim() === '') {
            field.value = 0;
        }
    });
    
    requiredFields.forEach(function(field) {
        if(!field.value.trim()) {
            valid = false;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if(valid) {
        form.submit();
    } else {
        alert('Harap lengkapi semua field yang wajib diisi!');
    }
}

// Format currency input
document.addEventListener('input', function(e) {
    if(e.target.type === 'number' && e.target.id.includes('pinjaman')) {
        var value = e.target.value;
        if(value) {
            var formatted = parseInt(value.replace(/\D/g, ''));
            if(!isNaN(formatted)) {
                e.target.value = formatted;
            }
        }
    }
});
</script>

</body>
</html>
