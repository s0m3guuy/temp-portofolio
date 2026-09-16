<?php
    require 'auth.php';
    require 'koneksi.php';

    $query = "SELECT * FROM anggota ORDER BY joindate DESC LIMIT 10";
    $result = mysqli_query($koneksi, $query);

    // Active loans per member -- powers the "bayar" / "libur" quick-action
    // buttons below so they can be pre-scoped to just that member's loan(s)
    // without navigating to laporan3.php / libur.php first.
    $queryLoans = "SELECT p.id, p.anggota_id, p.kode_pinjaman, p.bayar_angsuran, p.jangka_waktu,
                   (SELECT COUNT(*) FROM pembayaran pb WHERE pb.pinjaman_id = p.id) as angsuran_terbayar
                   FROM pinjaman p
                   WHERE p.status_pinjaman = 'aktif'";
    $resultLoans = mysqli_query($koneksi, $queryLoans);
    $loansByAnggota = [];
    if ($resultLoans) {
        while ($loanRow = mysqli_fetch_assoc($resultLoans)) {
            $loansByAnggota[$loanRow['anggota_id']][] = [
                'id'                => (int)$loanRow['id'],
                'kode_pinjaman'     => $loanRow['kode_pinjaman'],
                'bayar_angsuran'    => (float)$loanRow['bayar_angsuran'],
                'jangka_waktu'      => (int)$loanRow['jangka_waktu'],
                'angsuran_terbayar' => (int)$loanRow['angsuran_terbayar'],
            ];
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anggota</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="mobile.css">
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
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
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
        .page-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            margin-top: -10px;
        }
        .modal-buttons .btn {
            margin-right: 8px;
        }
        .readonly-field {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        .amount-green {
            font-weight: 600;
            color: #155724;
        }
        .confirm-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 15px;
        }
        .confirm-summary dt {
            color: #6c757d;
            font-weight: 500;
            font-size: .85rem;
        }
        .confirm-summary dd {
            font-size: 1.05rem;
            margin-bottom: 10px;
        }
        .confirm-checkbox-box {
            background: #fff3cd;
            border: 1px solid #ffe69c;
            border-radius: 8px;
            padding: 15px;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
            font-size: .85rem;
            color: #adb5bd;
        }
        .step-indicator .active {
            color: #667eea;
            font-weight: 600;
        }
        .modal-for-member {
            font-size: .9rem;
            opacity: .9;
            font-weight: normal;
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
                <a href="anggota.php" class="active"><i class="bi bi-people"></i><span>Anggota</span></a>
                <a href="blacklist.php"><i class="bi bi-x-circle"></i><span>Blacklist</span></a>
                <a href="tabungan.php" ><i class="bi bi-wallet2"></i><span>Tabungan</span></a>
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
            <h3 class="mb-0">Anggota</h3>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Daftar Anggota</span>
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Anggota
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="anggotaTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Anggota</th>
                                <th>Jenis Usaha</th>
                                <th>Status</th>
                                <th>Bergabung Sejak</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>   
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                    // Format status
                                    $statusClass = $row['status'] == 'aktif' ? 'status-active' : 'status-inactive';
                                    $statusText = $row['status'] == 'aktif' ? 'Aktif' : 'Nonaktif';
                                    
                                    // Format join date
                                    $joinDate = date('d M Y', strtotime($row['joindate']));

                                    $memberLoans = $loansByAnggota[$row['id']] ?? [];
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama']); ?></td>
                                    <td><?= htmlspecialchars($row['usaha']); ?></td>
                                    <td>
                                        <form action="anggota_status.php" method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="status-badge <?= $statusClass ?> border-0">
                                                <?= $statusText ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td><?= $joinDate ?></td>
                                    <td class="action-buttons">
                                        <!-- View Button -->
                                        <button class="btn btn-info btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewModal<?= $row['id'] ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- View Modal -->
                                <div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Detail Anggota</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Nama:</label>
                                                            <p class="form-control-static"><?= htmlspecialchars($row['nama']) ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Jenis Usaha:</label>
                                                            <p class="form-control-static"><?= htmlspecialchars($row['usaha']) ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">No Telephone:</label>
                                                            <p class="form-control-static"><?= htmlspecialchars($row['no_telp']) ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Email:</label>
                                                            <p class="form-control-static"><?= htmlspecialchars($row['email']) ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Alamat:</label>
                                                            <p class="form-control-static"><?= htmlspecialchars($row['alamat']) ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">NIK:</label>
                                                            <p class="form-control-static"><?= htmlspecialchars($row['NIK']) ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">TTL:</label>
                                                            <p class="form-control-static"><?= htmlspecialchars($row['ttl']) ?></p>
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
                                                            <label class="form-label">Bergabung Sejak:</label>
                                                            <p class="form-control-static"><?= $joinDate ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-buttons text-start p-3">
                                                <button type="button" class="btn btn-success btn-pengajuan"
                                                        data-anggota-id="<?= $row['id'] ?>"
                                                        data-anggota-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>">
                                                    <i class="bi bi-file-earmark-plus me-1"></i>Pengajuan Baru
                                                </button>
                                                <button type="button" class="btn btn-danger btn-bayar"
                                                        data-anggota-id="<?= $row['id'] ?>"
                                                        data-anggota-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>"
                                                        data-loans='<?= htmlspecialchars(json_encode($memberLoans), ENT_QUOTES) ?>'>
                                                    <i class="bi bi-cash me-1"></i>Bayar Angsuran
                                                </button>
                                                <button type="button" class="btn btn-info btn-libur"
                                                        data-anggota-id="<?= $row['id'] ?>"
                                                        data-anggota-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>"
                                                        data-loans='<?= htmlspecialchars(json_encode($memberLoans), ENT_QUOTES) ?>'>
                                                    <i class="bi bi-calendar me-1"></i>Libur
                                                </button>
                                            </div>
                                            <div class="modal-buttons text-end p-3">
                                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tutup</button>
                                            </div>
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
                        Showing 1 to <?= min(10, mysqli_num_rows($result)) ?> of entries
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

<!-- ADD MEMBER MODAL -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Anggota Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="anggota_add.php" method="POST" id="addMemberForm">
                    <div class="mb-3">
                        <label class="form-label">Nama *</label>
                        <input type="text" class="form-control" name="nama" placeholder="Nama lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Usaha</label>
                        <input type="text" class="form-control" name="usaha" placeholder="Jenis usaha">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No Telephone *</label>
                        <input type="text" class="form-control" name="no_telp" placeholder="Nomor telepon" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="Email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-control" name="alamat" placeholder="Alamat" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NIK</label>
                        <input type="text" class="form-control" name="nik" placeholder="Nomor Induk Kependudukan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">TTL</label>
                        <input type="text" class="form-control" name="ttl" placeholder="Tempat, Tanggal Lahir">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="addMemberForm" class="btn btn-primary">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- QUICK-ACTION: PENGAJUAN BARU (mirrors pengajuan.php's loan form, member pre-selected) -->
<div class="modal fade" id="addPengajuanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Form Input Pinjaman
                    <div class="modal-for-member">Untuk: <span id="pengajuanModalMemberName">-</span></div>
                </h5>
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

<!-- QUICK-ACTION: BAYAR ANGSURAN (mirrors laporan3.php's confirm-based payment modal, loan list pre-scoped to the member) -->
<div class="modal fade" id="addBayarModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form action="pembayaran_add.php" method="POST" id="addBayarForm">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-cash me-2"></i>Bayar Angsuran
                    <div class="modal-for-member">Untuk: <span id="bayarModalMemberName">-</span></div>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="step-indicator">
                    <span id="bayarStepLabel1" class="active">1. Data Angsuran</span>
                    <span>›</span>
                    <span id="bayarStepLabel2">2. Konfirmasi</span>
                </div>

                <div id="bayarStep1">
                    <input type="hidden" name="anggota_id" id="pay_anggota_id">
                    <div class="mb-3">
                        <label class="form-label">Pilih Pinjaman *</label>
                        <select class="form-control" name="pinjaman_id" id="pay_pinjaman_id" required onchange="loadPinjamanDetailBayar(this)">
                            <option value="">-- Pilih Pinjaman --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Angsuran Ke</label>
                        <input type="text" class="form-control readonly-field" id="pay_angsuran_ke_display" readonly placeholder="Pilih pinjaman dulu">
                        <input type="hidden" name="angsuran_ke" id="pay_angsuran_ke">
                        <small class="text-muted" id="pay_jangka_info"></small>
                        <div class="alert alert-warning py-2 px-3 mt-2 mb-0" id="pay_lunas_warning" style="display:none">
                            <i class="bi bi-exclamation-triangle me-1"></i>Pinjaman ini sudah lunas — semua angsuran telah tercatat.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Angsuran Minimum</label>
                        <div class="input-group"><span class="input-group-text">Rp</span>
                            <input type="text" class="form-control readonly-field" id="pay_wajib" readonly></div>
                        <small class="text-muted">Ini adalah jumlah tetap per angsuran. Pembayaran tercatat penuh sesuai jumlah ini.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal & Waktu Bayar *</label>
                        <input type="datetime-local" class="form-control" name="tanggal_bayar" id="pay_tanggal" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="2" placeholder="Keterangan jika perlu..."></textarea>
                    </div>
                </div>

                <div id="bayarStep2" style="display:none">
                    <dl class="confirm-summary row mb-0">
                        <div class="col-6"><dt>Nama Anggota</dt><dd id="bayar_conf_nama">-</dd></div>
                        <div class="col-6"><dt>Kode Pinjaman</dt><dd id="bayar_conf_kode">-</dd></div>
                        <div class="col-6"><dt>Angsuran Ke</dt><dd id="bayar_conf_angsuran">-</dd></div>
                        <div class="col-6"><dt>Tanggal Bayar</dt><dd id="bayar_conf_tanggal">-</dd></div>
                        <div class="col-12"><dt>Jumlah yang Tercatat Dibayar</dt><dd class="amount-green" id="bayar_conf_jumlah">-</dd></div>
                    </dl>
                    <div class="confirm-checkbox-box">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bayar_confirm_paid" required>
                            <label class="form-check-label" for="bayar_confirm_paid">
                                Saya konfirmasi anggota telah membayar <strong>penuh</strong> angsuran minimum ini secara tunai/transfer.
                            </label>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-outline-secondary" id="bayarBtnBack" style="display:none" onclick="goToStep1Bayar()">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </button>
                <button type="button" class="btn btn-primary" id="bayarBtnNext" onclick="goToStep2Bayar()">
                    Lanjutkan<i class="bi bi-arrow-right ms-1"></i>
                </button>
                <button type="submit" class="btn btn-primary" id="bayarBtnSubmit" style="display:none">
                    <i class="bi bi-check-circle me-1"></i>Konfirmasi & Simpan
                </button>
            </div>
        </form>
    </div></div>
</div>

<!-- QUICK-ACTION: LIBUR (mirrors libur.php's add form, loan list pre-scoped to the member) -->
<div class="modal fade" id="addLiburModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="libur_add.php" method="POST" id="addLiburForm">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar me-2"></i>Tambah Angsuran Libur
                        <div class="modal-for-member">Untuk: <span id="liburModalMemberName">-</span></div>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="anggota_id" id="quick_libur_anggota_id">
                    <input type="hidden" name="pinjaman_id" id="quick_libur_pinjaman_id">

                    <div class="mb-3">
                        <label class="form-label">Pinjaman *</label>
                        <select class="form-control" id="quick_libur_pinjaman_select" required onchange="updateLiburPinjamanInfo(this)">
                            <option value="">-- Pilih Pinjaman --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Angsuran Ke *</label>
                        <input type="number" class="form-control" name="angsuran_ke"
                               id="quick_libur_angsuran_ke" min="1" required
                               placeholder="contoh: 5">
                        <small class="text-muted" id="quick_libur_jangka_info"></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Libur *</label>
                        <input type="date" class="form-control" name="tanggal_libur"
                               id="quick_libur_tanggal" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alasan Libur *</label>
                        <select class="form-control" name="alasan" required>
                            <option value="">-- Pilih Alasan --</option>
                            <option value="Sakit">Sakit</option>
                            <option value="Musibah">Musibah</option>
                            <option value="Keperluan Mendesak">Keperluan Mendesak</option>
                            <option value="Libur Hari Raya">Libur Hari Raya</option>
                            <option value="Usaha Sedang Sepi">Usaha Sedang Sepi</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan Tambahan</label>
                        <textarea class="form-control" name="keterangan" rows="3"
                                  placeholder="Tambahkan keterangan jika perlu..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Simpan
                    </button>
                </div>
            </form>
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
    $('#anggotaTable').DataTable({
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

// Auto-close alerts after 5 seconds
setTimeout(function() {
    $('.alert').alert('close');
}, 5000);

/* =========================================================
   STACKED MODALS SUPPORT
   Quick-action modals (Pengajuan/Bayar/Libur) are opened from
   inside the already-open "Detail Anggota" modal. Bootstrap gives
   every modal + backdrop the same z-index by default, which can
   leave the top modal looking undarkened underneath or, worse,
   non-interactive. This bumps z-index per nesting depth so each
   newly-opened modal sits cleanly above what's already open.
   ========================================================= */
document.addEventListener('show.bs.modal', function (event) {
    var openModalsCount = document.querySelectorAll('.modal.show').length;
    if (openModalsCount > 0) {
        var zIndex = 1055 + (openModalsCount * 20);
        event.target.style.zIndex = zIndex;
        setTimeout(function () {
            var backdrops = document.querySelectorAll('.modal-backdrop:not(.modal-stacked)');
            var lastBackdrop = backdrops[backdrops.length - 1];
            if (lastBackdrop) {
                lastBackdrop.style.zIndex = zIndex - 10;
                lastBackdrop.classList.add('modal-stacked');
            }
        }, 0);
    }
});

document.addEventListener('hidden.bs.modal', function () {
    if (document.querySelectorAll('.modal.show').length === 0) {
        document.querySelectorAll('.modal-backdrop').forEach(function (bd) {
            bd.classList.remove('modal-stacked');
        });
    }
});

/* =========================================================
   QUICK ACTION: PENGAJUAN BARU
   ========================================================= */
$(document).on('click', '.btn-pengajuan', function() {
    var anggotaId = $(this).data('anggota-id');
    var nama = $(this).data('anggota-nama');
    openPengajuanModal(anggotaId, nama);
});

function openPengajuanModal(anggotaId, nama) {
    fetch('get_member_details.php?id=' + anggotaId)
        .then(function(r) {
            if (!r.ok) { throw new Error('HTTP ' + r.status); }
            return r.text();
        })
        .then(function(text) {
            var data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Respons get_member_details.php bukan JSON valid:', text);
                throw new Error('Respons server tidak valid');
            }

            if (data.blacklisted) {
                alert('⛔ Anggota ini sedang di-BLACKLIST!\nAlasan: ' + data.alasan + '\n\nAnggota tidak dapat mengajukan pinjaman.');
                return;
            }

            document.getElementById('member_id').value = anggotaId;
            document.getElementById('pengajuanModalMemberName').textContent = nama;

            var today = new Date().toISOString().split('T')[0];
            document.getElementById('tanggal_pengambilan').value = today;
            var futureDate = new Date();
            futureDate.setDate(futureDate.getDate() + 30);
            document.getElementById('jatuh_tempo').value = futureDate.toISOString().split('T')[0];

            document.getElementById('kekurangan_pinjaman').value = 0;
            document.getElementById('denda_pinjaman_lama').value = 0;
            document.getElementById('biaya_admin').value = 0;

            var d = new Date();
            var year = d.getFullYear().toString().substr(-2);
            var month = (d.getMonth() + 1).toString().padStart(2, '0');
            var day = d.getDate().toString().padStart(2, '0');
            var random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            document.getElementById('kode_pinjaman').value = 'P' + year + month + day + random;

            var modal = new bootstrap.Modal(document.getElementById('addPengajuanModal'));
            modal.show();
        })
        .catch(function(err) {
            console.error('Error:', err);
            alert('Gagal memuat data anggota. Silakan coba lagi.\n\n(' + err.message + ')');
        });
}

function calculateTotal() {
    var pinjaman = parseFloat(document.getElementById('pinjaman').value) || 0;
    var jangka = parseFloat(document.getElementById('jangka_waktu').value) || 0;
    var bunga = parseFloat(document.getElementById('bunga').value) || 0;

    if (pinjaman > 0 && jangka > 0 && bunga > 0) {
        var bungaAmount = pinjaman * (bunga / 100);
        var total = pinjaman + (bungaAmount * jangka);
        document.getElementById('total_pinjaman_bunga').value = Math.round(total);

        var monthlyPayment = total / jangka;
        document.getElementById('bayar_angsuran').value = Math.round(monthlyPayment);

        var biayaAdmin = parseFloat(document.getElementById('biaya_admin').value) || 0;
        var terimaPinjaman = pinjaman - biayaAdmin;
        document.getElementById('terima_pinjaman').value = Math.round(terimaPinjaman);
    }

    if (!document.getElementById('kekurangan_pinjaman').value) {
        document.getElementById('kekurangan_pinjaman').value = 0;
    }
    if (!document.getElementById('denda_pinjaman_lama').value) {
        document.getElementById('denda_pinjaman_lama').value = 0;
    }
    if (!document.getElementById('biaya_admin').value) {
        document.getElementById('biaya_admin').value = 0;
    }
}

function submitLoanForm() {
    var form = document.getElementById('loanForm');
    var requiredFields = form.querySelectorAll('[required]');
    var valid = true;

    var numericFields = ['kekurangan_pinjaman', 'denda_pinjaman_lama', 'biaya_admin'];
    numericFields.forEach(function(fieldId) {
        var field = document.getElementById(fieldId);
        if (!field.value || field.value.trim() === '') {
            field.value = 0;
        }
    });

    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            valid = false;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    });

    if (valid) {
        form.submit();
    } else {
        alert('Harap lengkapi semua field yang wajib diisi!');
    }
}

/* =========================================================
   QUICK ACTION: BAYAR ANGSURAN
   ========================================================= */
$(document).on('click', '.btn-bayar', function() {
    var anggotaId = $(this).data('anggota-id');
    var nama = $(this).data('anggota-nama');
    var loans = $(this).data('loans'); // jQuery auto-parses JSON data attrs
    openBayarModal(anggotaId, nama, loans || []);
});

function openBayarModal(anggotaId, nama, loans) {
    if (!loans || loans.length === 0) {
        alert('Anggota ini tidak memiliki pinjaman aktif.');
        return;
    }

    document.getElementById('pay_anggota_id').value = anggotaId;
    document.getElementById('bayarModalMemberName').textContent = nama;

    var select = document.getElementById('pay_pinjaman_id');
    select.innerHTML = '<option value="">-- Pilih Pinjaman --</option>';
    loans.forEach(function(loan) {
        var opt = document.createElement('option');
        opt.value = loan.id;
        opt.textContent = loan.kode_pinjaman + ' (Rp ' + Number(loan.bayar_angsuran).toLocaleString('id-ID') + ' / angsuran)';
        opt.dataset.bayar = loan.bayar_angsuran;
        opt.dataset.jangka = loan.jangka_waktu;
        opt.dataset.terbayar = loan.angsuran_terbayar;
        select.appendChild(opt);
    });

    if (loans.length === 1) {
        select.value = loans[0].id;
        select.disabled = true;
    } else {
        select.disabled = false;
    }

    document.getElementById('pay_tanggal').value = new Date().toISOString().slice(0, 16);

    if (select.value) {
        loadPinjamanDetailBayar(select);
    } else {
        document.getElementById('pay_wajib').value = '';
        document.getElementById('pay_angsuran_ke').value = '';
        document.getElementById('pay_angsuran_ke_display').value = '';
        document.getElementById('pay_jangka_info').textContent = '';
    }

    goToStep1Bayar();
    var modal = new bootstrap.Modal(document.getElementById('addBayarModal'));
    modal.show();
}

function loadPinjamanDetailBayar(select) {
    var opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) return;

    var bayar = parseFloat(opt.dataset.bayar) || 0;
    document.getElementById('pay_wajib').value = bayar ? bayar.toLocaleString('id-ID') : '';
    var jangka = parseInt(opt.dataset.jangka) || 0;
    var terbayar = parseInt(opt.dataset.terbayar) || 0;
    var next = terbayar + 1;

    document.getElementById('pay_angsuran_ke').value = next;

    var lunasWarning = document.getElementById('pay_lunas_warning');
    var btnNext = document.getElementById('bayarBtnNext');
    if (jangka && next > jangka) {
        document.getElementById('pay_angsuran_ke_display').value = '-';
        document.getElementById('pay_jangka_info').textContent = 'Sudah dibayar ' + terbayar + ' / ' + jangka + ' kali';
        lunasWarning.style.display = 'block';
        btnNext.disabled = true;
    } else {
        document.getElementById('pay_angsuran_ke_display').value = next + ' dari ' + jangka + ' angsuran';
        document.getElementById('pay_jangka_info').textContent = jangka ? 'Sudah dibayar ' + terbayar + ' / ' + jangka + ' kali' : '';
        lunasWarning.style.display = 'none';
        btnNext.disabled = false;
    }
}

function goToStep2Bayar() {
    var select = document.getElementById('pay_pinjaman_id');
    var angsuranKe = document.getElementById('pay_angsuran_ke');
    var tanggal = document.getElementById('pay_tanggal');

    if (!select.value) { alert('Pilih pinjaman terlebih dahulu.'); return; }
    if (!angsuranKe.value) { alert('Pinjaman ini sudah lunas atau angsuran belum bisa ditentukan.'); return; }
    if (!tanggal.reportValidity()) return;

    var opt = select.options[select.selectedIndex];
    document.getElementById('bayar_conf_nama').textContent = document.getElementById('bayarModalMemberName').textContent;
    document.getElementById('bayar_conf_kode').textContent = opt.textContent;
    document.getElementById('bayar_conf_angsuran').textContent = angsuranKe.value + ' / ' + (opt.dataset.jangka || '-');
    document.getElementById('bayar_conf_tanggal').textContent = tanggal.value.replace('T', ' ');
    document.getElementById('bayar_conf_jumlah').textContent = 'Rp ' + document.getElementById('pay_wajib').value;

    document.getElementById('bayarStep1').style.display = 'none';
    document.getElementById('bayarStep2').style.display = 'block';
    document.getElementById('bayarStepLabel1').classList.remove('active');
    document.getElementById('bayarStepLabel2').classList.add('active');
    document.getElementById('bayarBtnNext').style.display = 'none';
    document.getElementById('bayarBtnBack').style.display = 'inline-block';
    document.getElementById('bayarBtnSubmit').style.display = 'inline-block';
}

function goToStep1Bayar() {
    document.getElementById('bayarStep2').style.display = 'none';
    document.getElementById('bayarStep1').style.display = 'block';
    document.getElementById('bayarStepLabel2').classList.remove('active');
    document.getElementById('bayarStepLabel1').classList.add('active');
    document.getElementById('bayarBtnBack').style.display = 'none';
    document.getElementById('bayarBtnSubmit').style.display = 'none';
    document.getElementById('bayarBtnNext').style.display = 'inline-block';
}

document.getElementById('addBayarForm').addEventListener('submit', function(e) {
    if (!document.getElementById('bayar_confirm_paid').checked) {
        e.preventDefault();
        alert('Silakan centang konfirmasi pembayaran terlebih dahulu.');
    }
});

document.getElementById('addBayarModal').addEventListener('hidden.bs.modal', function() {
    goToStep1Bayar();
    document.getElementById('addBayarForm').reset();
    document.getElementById('pay_pinjaman_id').innerHTML = '<option value="">-- Pilih Pinjaman --</option>';
    document.getElementById('pay_pinjaman_id').disabled = false;
    document.getElementById('pay_wajib').value = '';
    document.getElementById('pay_angsuran_ke').value = '';
    document.getElementById('pay_angsuran_ke_display').value = '';
    document.getElementById('pay_jangka_info').textContent = '';
    document.getElementById('pay_lunas_warning').style.display = 'none';
    document.getElementById('bayarBtnNext').disabled = false;
});

/* =========================================================
   QUICK ACTION: LIBUR
   ========================================================= */
$(document).on('click', '.btn-libur', function() {
    var anggotaId = $(this).data('anggota-id');
    var nama = $(this).data('anggota-nama');
    var loans = $(this).data('loans');
    openLiburModal(anggotaId, nama, loans || []);
});

function openLiburModal(anggotaId, nama, loans) {
    if (!loans || loans.length === 0) {
        alert('Anggota ini tidak memiliki pinjaman aktif.');
        return;
    }

    document.getElementById('quick_libur_anggota_id').value = anggotaId;
    document.getElementById('liburModalMemberName').textContent = nama;

    var select = document.getElementById('quick_libur_pinjaman_select');
    select.innerHTML = '<option value="">-- Pilih Pinjaman --</option>';
    loans.forEach(function(loan) {
        var opt = document.createElement('option');
        opt.value = loan.id;
        opt.textContent = loan.kode_pinjaman + ' (' + loan.jangka_waktu + ' kali)';
        opt.dataset.jangka = loan.jangka_waktu;
        select.appendChild(opt);
    });

    if (loans.length === 1) {
        select.value = loans[0].id;
        select.disabled = true;
        updateLiburPinjamanInfo(select);
    } else {
        select.disabled = false;
        document.getElementById('quick_libur_pinjaman_id').value = '';
        document.getElementById('quick_libur_jangka_info').textContent = '';
        document.getElementById('quick_libur_angsuran_ke').removeAttribute('max');
    }

    document.getElementById('quick_libur_tanggal').value = new Date().toISOString().split('T')[0];

    var modal = new bootstrap.Modal(document.getElementById('addLiburModal'));
    modal.show();
}

function updateLiburPinjamanInfo(select) {
    var opt = select.options[select.selectedIndex];
    var pinjamanId = opt ? opt.value : '';
    var jangka = opt ? (opt.dataset.jangka || '') : '';
    document.getElementById('quick_libur_pinjaman_id').value = pinjamanId;
    if (jangka) {
        document.getElementById('quick_libur_jangka_info').textContent = 'Jangka waktu pinjaman: ' + jangka + ' kali';
        document.getElementById('quick_libur_angsuran_ke').max = jangka;
    } else {
        document.getElementById('quick_libur_jangka_info').textContent = '';
        document.getElementById('quick_libur_angsuran_ke').removeAttribute('max');
    }
}

document.getElementById('addLiburModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('addLiburForm').reset();
    document.getElementById('quick_libur_pinjaman_select').innerHTML = '<option value="">-- Pilih Pinjaman --</option>';
    document.getElementById('quick_libur_pinjaman_select').disabled = false;
    document.getElementById('quick_libur_pinjaman_id').value = '';
    document.getElementById('quick_libur_jangka_info').textContent = '';
});
</script>
<!-- MOBILE BOTTOM NAV -->
<nav class="mobile-nav">
    <a href="dashboard.php">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
    </a>
    <a href="anggota.php" class="active">
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