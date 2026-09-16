<?php
require 'auth.php';
require 'koneksi.php';

// Main table is grouped by member: net balance, transaction count, and most
// recent activity date. Individual setor/tarik entries are shown in each
// member's view modal instead.
$queryGrouped = "SELECT a.id as anggota_id, a.nama, a.usaha,
                 COALESCE(SUM(CASE WHEN t.jenis = 'tarik' THEN -t.jumlah ELSE t.jumlah END), 0) as saldo,
                 COUNT(t.id) as jumlah_transaksi,
                 MAX(t.tanggal_setor) as last_date
                 FROM tabungan t
                 JOIN anggota a ON t.anggota_id = a.id
                 GROUP BY t.anggota_id, a.id, a.nama, a.usaha
                 ORDER BY last_date DESC
                 LIMIT 10";
$result = mysqli_query($koneksi, $queryGrouped);

// Number of distinct members with at least one transaction (for the pagination footer)
$totalEntries = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(DISTINCT anggota_id) as total FROM tabungan"))['total'];

// Net total saved across everyone (deposits minus withdrawals)
$grandTotal = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COALESCE(SUM(CASE WHEN jenis = 'tarik' THEN -jumlah ELSE jumlah END), 0) as grand_total
     FROM tabungan"))['grand_total'];

// Total individual transactions (deposits + withdrawals combined)
$totalTransaksi = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) as total FROM tabungan"))['total'];

// Active members for the deposit / withdraw dropdowns
$queryAnggota = "SELECT * FROM anggota WHERE status = 'aktif' ORDER BY nama";
$resultAnggota = mysqli_query($koneksi, $queryAnggota);

// Current balance per member -- embedded on the withdraw dropdown's options
// so staff see the limit before submitting (server still re-checks on save).
$saldoByAnggota = [];
$resultSaldo = mysqli_query($koneksi,
    "SELECT anggota_id, COALESCE(SUM(CASE WHEN jenis = 'tarik' THEN -jumlah ELSE jumlah END), 0) as saldo
     FROM tabungan GROUP BY anggota_id");
if ($resultSaldo) {
    while ($r = mysqli_fetch_assoc($resultSaldo)) {
        $saldoByAnggota[$r['anggota_id']] = (float) $r['saldo'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabungan</title>

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
        .card {
            border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px; border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border-radius: 10px 10px 0 0 !important;
            font-weight: bold; padding: 15px 20px;
        }
        .summary-card {
            border-radius: 10px; padding: 20px;
            color: white; margin-bottom: 20px;
        }
        .summary-card.total { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .summary-card.count { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .summary-card .label { font-size: 0.9rem; opacity: 0.85; }
        .summary-card .value { font-size: 1.6rem; font-weight: bold; }
        .table th { background-color: #f8f9fa; font-weight: 600; }
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
        .modal-header.tarik-header {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%); }
        .readonly-field { background-color: #f8f9fa; cursor: not-allowed; }
        .form-label { font-weight: 500; color: #495057; }
        .amount-display { font-weight: 600; color: #155724; }
        .input-group-text { background-color: #e9ecef; }
        .tx-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee; }
        .tx-row:last-child { border-bottom: none; }
        .tx-badge { padding: 4px 10px; border-radius: 20px; font-size: .75rem; font-weight: 600; }
        .tx-badge.setor { background-color: #d4edda; color: #155724; }
        .tx-badge.tarik { background-color: #f8d7da; color: #721c24; }
        .tx-amount.setor { color: #155724; font-weight: 600; }
        .tx-amount.tarik { color: #721c24; font-weight: 600; }
        .saldo-hint { font-size: .85rem; color: #6c757d; }
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
                <a href="pengajuanlunas.php"><i class="bi bi-check-circle"></i><span>Pengajuan Lunas</span></a>
                <a href="blacklist.php"><i class="bi bi-x-circle"></i><span>Blacklist</span></a>
                <a href="tabungan.php" class="active"><i class="bi bi-wallet2"></i><span>Tabungan</span></a>
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

        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="summary-card total">
                    <div class="label"><i class="bi bi-cash-stack me-1"></i>Total Tabungan (Saldo Bersih)</div>
                    <div class="value">Rp <?= number_format($grandTotal, 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="summary-card count">
                    <div class="label"><i class="bi bi-receipt me-1"></i>Total Transaksi (Setor + Tarik)</div>
                    <div class="value"><?= number_format($totalTransaksi) ?> Transaksi</div>
                </div>
            </div>
        </div>

        <!-- Main Table Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-wallet2 me-2"></i>Data Tabungan Anggota</span>
                <div>
                    <button class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#addTabunganModal">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Setoran
                    </button>
                    <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#tarikTabunganModal">
                        <i class="bi bi-dash-circle me-2"></i>Tarik Tabungan
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabunganTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Anggota</th>
                                <th>Jenis Usaha</th>
                                <th>Saldo Tabungan</th>
                                <th>Jumlah Transaksi</th>
                                <th>Aktivitas Terakhir</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php if(isset($result) && mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <?php
                                        // Full transaction history for this member, used in the view modal below
                                        $stmtTx = mysqli_prepare($koneksi,
                                            "SELECT * FROM tabungan WHERE anggota_id = ? ORDER BY tanggal_setor DESC, created_at DESC");
                                        mysqli_stmt_bind_param($stmtTx, 'i', $row['anggota_id']);
                                        mysqli_stmt_execute($stmtTx);
                                        $transaksi = mysqli_stmt_get_result($stmtTx);
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama']) ?></td>
                                        <td><?= htmlspecialchars($row['usaha']) ?></td>
                                        <td class="amount-display">
                                            Rp <?= number_format($row['saldo'], 0, ',', '.') ?>
                                        </td>
                                        <td><?= number_format($row['jumlah_transaksi']) ?>x</td>
                                        <td><?= $row['last_date'] ? date('d F Y', strtotime($row['last_date'])) : '-' ?></td>
                                        <td class="action-buttons">
                                            <button class="btn btn-info btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewModal<?= $row['anggota_id'] ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- View Modal: full transaction history for this member -->
                                    <div class="modal fade" id="viewModal<?= $row['anggota_id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Riwayat Tabungan — <?= htmlspecialchars($row['nama']) ?></h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Jenis Usaha:</label>
                                                            <p><?= htmlspecialchars($row['usaha']) ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Saldo Saat Ini:</label>
                                                            <p class="amount-display">Rp <?= number_format($row['saldo'], 0, ',', '.') ?></p>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <h6 class="mb-2">Riwayat Transaksi</h6>
                                                    <?php if ($transaksi && mysqli_num_rows($transaksi) > 0): ?>
                                                        <?php while ($tx = mysqli_fetch_assoc($transaksi)): ?>
                                                            <?php $jenis = $tx['jenis'] ?? 'setor'; ?>
                                                            <div class="tx-row">
                                                                <div>
                                                                    <span class="tx-badge <?= $jenis ?>"><?= $jenis === 'tarik' ? 'Tarik' : 'Setor' ?></span>
                                                                    <span class="ms-2"><?= date('d F Y', strtotime($tx['tanggal_setor'])) ?></span>
                                                                    <?php if (!empty($tx['keterangan'])): ?>
                                                                        <div class="text-muted small"><?= htmlspecialchars($tx['keterangan']) ?></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="tx-amount <?= $jenis ?> me-3">
                                                                        <?= $jenis === 'tarik' ? '-' : '+' ?>Rp <?= number_format($tx['jumlah'], 0, ',', '.') ?>
                                                                    </span>
                                                                    <button class="btn btn-outline-danger btn-sm" onclick="deleteTabungan(<?= $tx['id'] ?>)" title="Hapus">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        <?php endwhile; ?>
                                                    <?php else: ?>
                                                        <p class="text-muted">Belum ada transaksi.</p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Info -->
                <div class="pagination-container">
                    <div class="dataTables_info">
                        Showing <?= min(10, $totalEntries) ?> of <?= number_format($totalEntries) ?> anggota
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

<!-- ADD TABUNGAN (SETOR) MODAL -->
<div class="modal fade" id="addTabunganModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="tabungan_add.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-wallet2 me-2"></i>Tambah Setoran Tabungan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Anggota *</label>
                        <select class="form-control" name="anggota_id" id="tab_anggota_id" required
                                onchange="loadAnggotaInfo(this)">
                            <option value="">-- Pilih Anggota --</option>
                            <?php
                            if(isset($resultAnggota) && mysqli_num_rows($resultAnggota) > 0):
                                while($anggota = mysqli_fetch_assoc($resultAnggota)):
                            ?>
                                <option value="<?= $anggota['id'] ?>"
                                        data-usaha="<?= htmlspecialchars($anggota['usaha']) ?>">
                                    <?= htmlspecialchars($anggota['nama']) ?> - <?= htmlspecialchars($anggota['usaha']) ?>
                                </option>
                            <?php
                                endwhile;
                            endif;
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jenis Usaha</label>
                        <input type="text" class="form-control readonly-field" id="tab_usaha" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jumlah Setoran *</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" name="jumlah"
                                   min="1" required placeholder="contoh: 50000">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Setor *</label>
                        <input type="date" class="form-control" name="tanggal_setor"
                               id="tab_tanggal" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="2"
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

<!-- TARIK TABUNGAN (WITHDRAW) MODAL -->
<div class="modal fade" id="tarikTabunganModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="tabungan_tarik.php" method="POST" id="tarikForm">
                <div class="modal-header tarik-header">
                    <h5 class="modal-title"><i class="bi bi-dash-circle me-2"></i>Tarik Tabungan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Anggota *</label>
                        <select class="form-control" name="anggota_id" id="tarik_anggota_id" required
                                onchange="loadSaldoInfo(this)">
                            <option value="">-- Pilih Anggota --</option>
                            <?php
                            if(isset($resultAnggota) && mysqli_num_rows($resultAnggota) > 0):
                                mysqli_data_seek($resultAnggota, 0);
                                while($anggota = mysqli_fetch_assoc($resultAnggota)):
                                    $saldoAnggota = $saldoByAnggota[$anggota['id']] ?? 0;
                            ?>
                                <option value="<?= $anggota['id'] ?>"
                                        data-usaha="<?= htmlspecialchars($anggota['usaha']) ?>"
                                        data-saldo="<?= $saldoAnggota ?>">
                                    <?= htmlspecialchars($anggota['nama']) ?> - <?= htmlspecialchars($anggota['usaha']) ?>
                                </option>
                            <?php
                                endwhile;
                            endif;
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Saldo Saat Ini</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control readonly-field" id="tarik_saldo" readonly>
                        </div>
                        <div class="saldo-hint" id="tarik_saldo_hint">Pilih anggota untuk melihat saldo.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jumlah Penarikan *</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" name="jumlah" id="tarik_jumlah"
                                   min="1" required placeholder="contoh: 50000" oninput="checkSaldoCukup()">
                        </div>
                        <div class="alert alert-danger py-2 px-3 mt-2 mb-0" id="tarik_saldo_warning" style="display:none">
                            <i class="bi bi-exclamation-triangle me-1"></i>Jumlah penarikan melebihi saldo yang tersedia.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Tarik *</label>
                        <input type="date" class="form-control" name="tanggal_setor"
                               id="tarik_tanggal" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="2"
                                  placeholder="Tambahkan keterangan jika perlu..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="tarikSubmitBtn">
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
document.getElementById("sidebarToggle").onclick = function () {
    document.getElementById("sidebar").classList.toggle("collapsed");
};

$(document).ready(function() {
    if($.fn.DataTable.isDataTable('#tabunganTable')) {
        $('#tabunganTable').DataTable().destroy();
    }
    $('#tabunganTable').DataTable({
        "paging": false,
        "searching": true,
        "ordering": true,
        "info": false,
        "language": {
            "search": "Cari:",
            "zeroRecords": "Tidak ada data tabungan"
        }
    });

    var today = new Date().toISOString().split('T')[0];
    document.getElementById('tab_tanggal').value = today;
    document.getElementById('tarik_tanggal').value = today;
});

function toggleDropdown(e) {
    e.preventDefault();
    e.currentTarget.closest('.nav-dropdown').classList.toggle('open');
}

function loadAnggotaInfo(select) {
    var selected = select.options[select.selectedIndex];
    document.getElementById('tab_usaha').value = selected.dataset.usaha || '';
}

function loadSaldoInfo(select) {
    var selected = select.options[select.selectedIndex];
    var saldo = parseFloat(selected.dataset.saldo) || 0;
    document.getElementById('tarik_saldo').value = saldo.toLocaleString('id-ID');
    document.getElementById('tarik_saldo_hint').textContent = selected.value
        ? 'Penarikan tidak boleh melebihi saldo ini.'
        : 'Pilih anggota untuk melihat saldo.';
    checkSaldoCukup();
}

function checkSaldoCukup() {
    var select = document.getElementById('tarik_anggota_id');
    var selected = select.options[select.selectedIndex];
    var saldo = parseFloat(selected.dataset.saldo) || 0;
    var jumlah = parseFloat(document.getElementById('tarik_jumlah').value) || 0;
    var warning = document.getElementById('tarik_saldo_warning');
    var submitBtn = document.getElementById('tarikSubmitBtn');

    if (select.value && jumlah > saldo) {
        warning.style.display = 'block';
        submitBtn.disabled = true;
    } else {
        warning.style.display = 'none';
        submitBtn.disabled = false;
    }
}

document.getElementById('tarikTabunganModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('tarikForm').reset();
    document.getElementById('tarik_saldo').value = '';
    document.getElementById('tarik_saldo_hint').textContent = 'Pilih anggota untuk melihat saldo.';
    document.getElementById('tarik_saldo_warning').style.display = 'none';
    document.getElementById('tarikSubmitBtn').disabled = false;
    document.getElementById('tarik_tanggal').value = new Date().toISOString().split('T')[0];
});

function deleteTabungan(id) {
    if(confirm('Hapus data transaksi ini? Tindakan ini tidak dapat dibatalkan.')) {
        fetch('tabungan_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) { alert('Data berhasil dihapus!'); location.reload(); }
            else { alert('Gagal: ' + data.message); }
        });
    }
}
</script>
<!-- MOBILE BOTTOM NAV -->
<nav class="mobile-nav">
    <a href="dashboard.php">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
    </a>
    <a href="anggota.php">
        <i class="bi bi-people"></i>
        <span>Anggota</span>
    </a>
    <a href="pengajuan.php">
        <i class="bi bi-file-earmark"></i>
        <span>Pengajuan</span>
    </a>

    <a href="pengajuanbunga.php">
      <i class="bi bi-percent"></i>
      <span>Pengajuan Bunga</span>
    </a>

    <a href="pengajuanlunas.php">
      <i class="bi bi-check-circle"></i>
      <span>Pengajuan Lunas</span>
    </a>

    <a href="blacklist.php">
      <i class="bi bi-x-circle"></i>
      <span>Blacklist</span>
    </a>

    <a href="tabungan.php" class="active">
        <i class="bi bi-wallet2"></i>
        <span>Tabungan</span>
    </a>

    <a href="libur.php">
      <i class="bi bi-calendar"></i>
      <span>Libur</span>
    </a>

    <a href="laporan1.php">
      <i class="bi bi-file-earmark-text"></i>
      <span>Bayar</span>
    </a>

    <a href="laporan2.php">
      <i class="bi bi-file-earmark-bar-graph"></i>
      <span>Keuangan</span>
    </a>

</nav>
</body>
</html>