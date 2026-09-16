<?php
require 'auth.php';
require 'koneksi.php';

// Get all blacklisted members
$query = "SELECT b.*, a.nama, a.usaha, a.no_telp 
          FROM blacklist b 
          JOIN anggota a ON b.anggota_id = a.id 
          ORDER BY b.created_at DESC";
$result = mysqli_query($koneksi, $query);

// Get total count
$queryCount = "SELECT COUNT(*) as total FROM blacklist";
$resultCount = mysqli_query($koneksi, $queryCount);
$rowCount = mysqli_fetch_assoc($resultCount);
$totalEntries = $rowCount['total'];

// Get members NOT already blacklisted, for the add dropdown
$queryAnggota = "SELECT * FROM anggota 
                 WHERE status = 'aktif' 
                 AND id NOT IN (SELECT anggota_id FROM blacklist)
                 ORDER BY nama";
$resultAnggota = mysqli_query($koneksi, $queryAnggota);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blacklist</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
          <link rel="stylesheet" href="mobile.css">

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
        .table th { background-color: #f8f9fa; font-weight: 600; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
        .status-blacklist { background-color: #f8d7da; color: #721c24; }
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
        .readonly-field { background-color: #f8f9fa; cursor: not-allowed; }
        .blacklist-icon { color: #dc3545; font-size: 1.1rem; }
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
                <a href="blacklist.php" class="active"><i class="bi bi-x-circle"></i><span>Blacklist</span></a>
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
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-x-circle me-2"></i>Daftar Blacklist Anggota</span>
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addBlacklistModal">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Blacklist
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="blacklistTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Anggota</th>
                                <th>Jenis Usaha</th>
                                <th>No. Telepon</th>
                                <th>Alasan</th>
                                <th>Tanggal Blacklist</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php if(isset($result) && mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama']) ?></td>
                                        <td><?= htmlspecialchars($row['usaha']) ?></td>
                                        <td><?= htmlspecialchars($row['no_telp']) ?></td>
                                        <td><?= htmlspecialchars($row['alasan']) ?></td>
                                        <td><?= date('d F Y', strtotime($row['created_at'])) ?></td>
                                        <td>
                                            <span class="status-badge status-blacklist">
                                                <i class="bi bi-x-circle me-1"></i>Blacklisted
                                            </span>
                                        </td>
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
                                                    <h5 class="modal-title">Detail Blacklist</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Nama Anggota:</label>
                                                                <p><?= htmlspecialchars($row['nama']) ?></p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Jenis Usaha:</label>
                                                                <p><?= htmlspecialchars($row['usaha']) ?></p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">No. Telepon:</label>
                                                                <p><?= htmlspecialchars($row['no_telp']) ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Tanggal Blacklist:</label>
                                                                <p><?= date('d F Y', strtotime($row['created_at'])) ?></p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Di-blacklist oleh:</label>
                                                                <p><?= htmlspecialchars($row['dibuat_oleh'] ?? '-') ?></p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Status:</label>
                                                                <p><span class="status-badge status-blacklist"><i class="bi bi-x-circle me-1"></i>Blacklisted</span></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Alasan Blacklist:</label>
                                                                <p><?= htmlspecialchars($row['alasan']) ?></p>
                                                            </div>
                                                            <?php if(!empty($row['keterangan'])): ?>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Keterangan Tambahan:</label>
                                                                <p><?= htmlspecialchars($row['keterangan']) ?></p>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-check-circle text-success fs-3 d-block mb-2"></i>
                                        Tidak ada anggota yang di-blacklist
                                    </td>
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

<!-- ADD BLACKLIST MODAL -->
<div class="modal fade" id="addBlacklistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="blacklist_add.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>Tambah ke Blacklist</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Anggota *</label>
                        <select class="form-control" name="anggota_id" id="bl_anggota_id" required>
                            <option value="">-- Pilih Anggota --</option>
                            <?php if(isset($resultAnggota) && mysqli_num_rows($resultAnggota) > 0): ?>
                                <?php while($anggota = mysqli_fetch_assoc($resultAnggota)): ?>
                                    <option value="<?= $anggota['id'] ?>"
                                            data-usaha="<?= htmlspecialchars($anggota['usaha']) ?>"
                                            data-telp="<?= htmlspecialchars($anggota['no_telp']) ?>">
                                        <?= htmlspecialchars($anggota['nama']) ?> - <?= htmlspecialchars($anggota['usaha']) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis Usaha</label>
                        <input type="text" class="form-control readonly-field" id="bl_usaha" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No. Telepon</label>
                        <input type="text" class="form-control readonly-field" id="bl_telp" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan Blacklist *</label>
                        <select class="form-control" name="alasan" required>
                            <option value="">-- Pilih Alasan --</option>
                            <option value="Gagal Bayar">Gagal Bayar</option>
                            <option value="Penipuan">Penipuan</option>
                            <option value="Kabur">Kabur</option>
                            <option value="Data Palsu">Data Palsu</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Keterangan Tambahan</label>
                        <textarea class="form-control" name="keterangan" rows="3"
                                  placeholder="Tambahkan keterangan jika perlu..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i>Blacklist Anggota
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
    $('#blacklistTable').DataTable({
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

// Auto-fill usaha and telp when member selected
document.getElementById('bl_anggota_id').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    document.getElementById('bl_usaha').value = selected.dataset.usaha || '';
    document.getElementById('bl_telp').value  = selected.dataset.telp  || '';
});

// Remove from blacklist
function removeBlacklist(id, nama) {
    if(confirm('Hapus "' + nama + '" dari blacklist? Anggota ini akan bisa mengajukan pinjaman kembali.')) {
        fetch('blacklist_remove.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                alert('Anggota berhasil dihapus dari blacklist!');
                location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
        });
    }
}

// Delete blacklist record permanently
function deleteBlacklist(id, nama) {
    if(confirm('Hapus permanen data blacklist untuk "' + nama + '"?')) {
        fetch('blacklist_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                alert('Data berhasil dihapus!');
                location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
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
    <a href="anggota.php" >
        <i class="bi bi-people"></i>
        <span>Anggota</span>
    </a>

    <a href="blacklist.php" class="active">
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
