<?php
require 'auth.php';
require 'koneksi.php';

// Get payments where jumlah_bayar < bayar_angsuran (kurang bayar)
$query = "SELECT pb.*, a.nama, a.usaha, p.kode_pinjaman, p.bayar_angsuran, p.jangka_waktu,
                 (p.bayar_angsuran - pb.jumlah_bayar) as kekurangan
          FROM pembayaran pb
          JOIN anggota a ON pb.anggota_id = a.id
          JOIN pinjaman p ON pb.pinjaman_id = p.id
          WHERE pb.jumlah_bayar < p.bayar_angsuran
          ORDER BY pb.tanggal_bayar DESC
          LIMIT 10";
$result = mysqli_query($koneksi, $query);

$totalEntries = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) as total FROM pembayaran pb
     JOIN pinjaman p ON pb.pinjaman_id = p.id
     WHERE pb.jumlah_bayar < p.bayar_angsuran"))['total'];

// Total kekurangan
$totalKurang = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT SUM(p.bayar_angsuran - pb.jumlah_bayar) as total
     FROM pembayaran pb JOIN pinjaman p ON pb.pinjaman_id = p.id
     WHERE pb.jumlah_bayar < p.bayar_angsuran"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan 2 - Kurang Bayar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body{background-color:#f8f9fa}
        .topbar{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
        .brand{font-size:1.5rem;font-weight:bold}.logout{color:white;text-decoration:none}
        .layout{display:flex;min-height:100vh}
        .sidebar{width:250px;background:#2c3e50;color:white;padding:20px 0;transition:all 0.3s;box-shadow:2px 0 5px rgba(0,0,0,0.1)}
        .sidebar.collapsed{width:70px}
        .sidebar .logo{width:80%;margin:0 auto 20px;display:block;border-radius:10px}
        .sidebar.collapsed .logo{width:50px}
        .sidebar nav a{display:flex;align-items:center;padding:12px 20px;color:#ecf0f1;text-decoration:none;transition:all 0.3s;border-left:4px solid transparent}
        .sidebar nav a:hover{background:#34495e;color:#fff;border-left-color:#667eea}
        .sidebar nav a.active{background:#34495e;border-left-color:#667eea;font-weight:500}
        .sidebar nav a i{margin-right:10px;font-size:1.2rem;min-width:24px}
        .sidebar.collapsed nav a span{display:none}
        .sidebar.collapsed nav a i{margin-right:0;font-size:1.5rem}
        .content{flex:1;padding:20px;overflow-y:auto}
        .card{border-radius:10px;box-shadow:0 2px 4px rgba(0,0,0,0.1);margin-bottom:20px;border:none}
        .card-header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border-radius:10px 10px 0 0!important;font-weight:bold;padding:15px 20px}
        .table th{background-color:#f8f9fa;font-weight:600}
        .action-buttons .btn{margin-right:5px;padding:5px 10px}
        .pagination-container{display:flex;justify-content:space-between;align-items:center;margin-top:20px;padding:10px 0}
        .modal-content{border-radius:10px;border:none}
        .modal-header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border-radius:10px 10px 0 0}
        .btn-primary{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border:none}
        .btn-primary:hover{background:linear-gradient(135deg,#5a6fd8 0%,#6a4190 100%)}
        .page-title{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:15px;border-radius:10px;margin-bottom:20px;margin-top:-10px}
        .form-label{font-weight:500;color:#495057}
        .summary-card{border-radius:10px;padding:20px;color:white;margin-bottom:20px}
        .summary-card.warning{background:linear-gradient(135deg,#f7971e 0%,#ffd200 100%);color:#333}
        .summary-card.danger{background:linear-gradient(135deg,#cb2d3e 0%,#ef473a 100%)}
        .summary-card .label{font-size:.9rem;opacity:.85}
        .summary-card .value{font-size:1.6rem;font-weight:bold}
        .amount-red{font-weight:600;color:#721c24}
        .nav-dropdown{position:relative}
        .nav-dropdown-toggle{display:flex;align-items:center;justify-content:space-between;cursor:pointer}
        .dropdown-arrow{margin-left:auto;transition:transform 0.3s ease}
        .nav-dropdown.open .dropdown-arrow{transform:rotate(180deg)}
        .nav-dropdown-menu{display:none;flex-direction:column;padding-left:1rem}
        .nav-dropdown.open .nav-dropdown-menu{display:flex}
    </style>
</head>
<body>
<?php
if(isset($_GET['success'])){$msg=urldecode($_GET['message']??'Berhasil!');echo'<div class="alert alert-success alert-dismissible fade show m-3">'.htmlspecialchars($msg).'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';}
if(isset($_GET['error'])){$msg=urldecode($_GET['message']??'Error!');echo'<div class="alert alert-danger alert-dismissible fade show m-3">'.htmlspecialchars($msg).'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';}
?>
<header class="topbar">
    <div class="brand"><strong>PRI L</strong>ink
        <button id="sidebarToggle" class="btn btn-sm btn-light ms-3"><i class="bi bi-list"></i></button>
    </div>
    <a href="logout.php" class="logout">Logout <i class="bi bi-box-arrow-right ms-1"></i></a>
</header>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <img src="gmbr/logo.png" class="logo">
        <nav>
            <a href="dashboard.php"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
            <a href="anggota.php"><i class="bi bi-people"></i><span>Anggota</span></a>
            <a href="pengajuan.php"><i class="bi bi-file-earmark"></i><span>Pengajuan</span></a>
            <a href="pengajuanbunga.php"><i class="bi bi-percent"></i><span>Pengajuan Bunga</span></a>
            <a href="pengajuanlunas.php"><i class="bi bi-check-circle"></i><span>Pengajuan Lunas</span></a>
            <a href="blacklist.php"><i class="bi bi-x-circle"></i><span>Blacklist</span></a>
            <a href="tabungan.php"><i class="bi bi-wallet2"></i><span>Tabungan</span></a>
            <a href="libur.php"><i class="bi bi-calendar"></i><span>Libur</span></a>
            <div class="nav-dropdown open">
                <a href="#" class="nav-dropdown-toggle" onclick="toggleDropdown(event)">
                    <i class="bi bi-graph-up"></i><span>Laporan</span>
                    <i class="bi bi-chevron-down dropdown-arrow"></i>
                </a>
                <div class="nav-dropdown-menu">
                    <a href="laporan1.php"><i class="bi bi-file-earmark-check"></i><span>Lunas</span></a>
                    <a href="laporan2.php" class="active"><i class="bi bi-file-earmark-x"></i><span>Kurang Bayar</span></a>
                    <a href="laporan3.php"><i class="bi bi-file-earmark-plus"></i><span>Input Bayar</span></a>
                </div>
            </div>
        </nav>
    </aside>
    <main class="content">
        <div class="page-title">
            <h3 class="mb-0"><i class="bi bi-file-earmark-x me-2"></i>Laporan 2 — Kurang Bayar</h3>
        </div>

        <!-- Summary -->
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="summary-card danger">
                    <div class="label"><i class="bi bi-exclamation-triangle me-1"></i>Total Kekurangan</div>
                    <div class="value">Rp <?=number_format($totalKurang,0,',','.')?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="summary-card warning">
                    <div class="label"><i class="bi bi-people me-1"></i>Jumlah Angsuran Kurang</div>
                    <div class="value"><?=number_format($totalEntries)?> Angsuran</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span>Daftar Pembayaran Kurang Bayar</span></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="kurangTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th><th>Nama Anggota</th><th>Kode Pinjaman</th>
                                <th>Angsuran Ke</th><th>Wajib Bayar</th><th>Dibayar</th>
                                <th>Kekurangan</th><th>Tanggal Bayar</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no=1; if($result && mysqli_num_rows($result)>0): while($row=mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?=$no++?></td>
                            <td><?=htmlspecialchars($row['nama'])?></td>
                            <td><?=htmlspecialchars($row['kode_pinjaman'])?></td>
                            <td><?=$row['angsuran_ke']?></td>
                            <td>Rp <?=number_format($row['bayar_angsuran'],0,',','.')?></td>
                            <td>Rp <?=number_format($row['jumlah_bayar'],0,',','.')?></td>
                            <td class="amount-red">Rp <?=number_format($row['kekurangan'],0,',','.')?></td>
                            <td><?=date('d F Y',strtotime($row['tanggal_bayar']))?></td>
                            <td class="action-buttons">
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?=$row['id']?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <!-- View Modal -->
                        <div class="modal fade" id="viewModal<?=$row['id']?>" tabindex="-1">
                            <div class="modal-dialog"><div class="modal-content">
                                <div class="modal-header"><h5 class="modal-title">Detail Kurang Bayar</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3"><label class="form-label">Nama:</label><p><?=htmlspecialchars($row['nama'])?></p></div>
                                            <div class="mb-3"><label class="form-label">Jenis Usaha:</label><p><?=htmlspecialchars($row['usaha'])?></p></div>
                                            <div class="mb-3"><label class="form-label">Kode Pinjaman:</label><p><?=htmlspecialchars($row['kode_pinjaman'])?></p></div>
                                            <div class="mb-3"><label class="form-label">Angsuran Ke:</label><p><?=$row['angsuran_ke']?> / <?=$row['jangka_waktu']?></p></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3"><label class="form-label">Wajib Bayar:</label><p>Rp <?=number_format($row['bayar_angsuran'],0,',','.')?></p></div>
                                            <div class="mb-3"><label class="form-label">Dibayar:</label><p>Rp <?=number_format($row['jumlah_bayar'],0,',','.')?></p></div>
                                            <div class="mb-3"><label class="form-label">Kekurangan:</label><p class="amount-red">Rp <?=number_format($row['kekurangan'],0,',','.')?></p></div>
                                            <div class="mb-3"><label class="form-label">Tanggal Bayar:</label><p><?=date('d F Y',strtotime($row['tanggal_bayar']))?></p></div>
                                        </div>
                                    </div>
                                    <?php if(!empty($row['keterangan'])): ?>
                                    <div class="mb-3"><label class="form-label">Keterangan:</label><p><?=htmlspecialchars($row['keterangan'])?></p></div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                    <a href="laporan3.php" class="btn btn-primary"><i class="bi bi-file-earmark-plus me-1"></i>Input Pembayaran Lagi</a>
                                </div>
                            </div></div>
                        </div>
                        <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-container">
                    <div class="dataTables_info">Showing <?=min(10,$totalEntries)?> of <?=number_format($totalEntries)?> entries</div>
                    <div>
                        <button class="btn btn-outline-primary btn-sm" disabled>Previous</button>
                        <button class="btn btn-outline-primary btn-sm" <?=$totalEntries<=10?'disabled':''?>>Next</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
document.getElementById("sidebarToggle").onclick = function() { document.getElementById("sidebar").classList.toggle("collapsed"); };
$(document).ready(function() {
    if($.fn.DataTable.isDataTable('#kurangTable')) $('#kurangTable').DataTable().destroy();
    $('#kurangTable').DataTable({ "paging":false,"searching":true,"ordering":true,"info":false,"language":{"search":"Cari:","zeroRecords":"Tidak ada pembayaran kurang bayar"} });
});
function toggleDropdown(e) { e.preventDefault(); e.currentTarget.closest('.nav-dropdown').classList.toggle('open'); }
setTimeout(function(){$('.alert').alert('close');},5000);
</script>
</body>
</html>
