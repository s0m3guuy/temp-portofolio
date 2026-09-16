<?php
require 'auth.php';
require 'koneksi.php';

$query = "SELECT pb.*, a.nama, a.usaha, p.kode_pinjaman, p.bayar_angsuran, p.jangka_waktu
          FROM pembayaran pb
          JOIN anggota a ON pb.anggota_id = a.id
          JOIN pinjaman p ON pb.pinjaman_id = p.id
          ORDER BY pb.tanggal_bayar DESC
          LIMIT 10";
$result = mysqli_query($koneksi, $query);

$totalEntries = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pembayaran"))['total'];

$queryPinjaman = "SELECT p.id, p.kode_pinjaman, p.bayar_angsuran, p.jangka_waktu, a.nama, a.id as anggota_id
                  FROM pinjaman p JOIN anggota a ON p.anggota_id = a.id
                  WHERE p.status_pinjaman = 'aktif' ORDER BY a.nama";
$resultPinjaman = mysqli_query($koneksi, $queryPinjaman);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan 3 - Input Pembayaran</title>
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
        .readonly-field{background-color:#f8f9fa;cursor:not-allowed}
        .input-group-text{background-color:#e9ecef}
        .status-badge{padding:5px 10px;border-radius:20px;font-size:.85rem;font-weight:500}
        .status-lunas{background-color:#d4edda;color:#155724}
        .status-kurang{background-color:#fff3cd;color:#856404}
        .amount-green{font-weight:600;color:#155724}
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
                    <a href="laporan2.php"><i class="bi bi-file-earmark-x"></i><span>Kurang Bayar</span></a>
                    <a href="laporan3.php" class="active"><i class="bi bi-file-earmark-plus"></i><span>Input Bayar</span></a>
                </div>
            </div>
        </nav>
    </aside>
    <main class="content">
        <div class="page-title">
            <h3 class="mb-0"><i class="bi bi-file-earmark-plus me-2"></i>Laporan 3 — Input Pembayaran</h3>
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Data Pembayaran Angsuran</span>
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                    <i class="bi bi-plus-circle me-2"></i>Input Pembayaran
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="paymentTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th><th>Nama Anggota</th><th>Kode Pinjaman</th>
                                <th>Angsuran Ke</th><th>Wajib Bayar</th><th>Dibayar</th>
                                <th>Status</th><th>Tanggal Bayar</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no=1; if($result && mysqli_num_rows($result)>0): while($row=mysqli_fetch_assoc($result)): ?>
                        <?php $kurang=$row['bayar_angsuran']-$row['jumlah_bayar']; $sc=$kurang<=0?'status-lunas':'status-kurang'; $st=$kurang<=0?'Lunas':'Kurang Bayar'; ?>
                        <tr>
                            <td><?=$no++?></td>
                            <td><?=htmlspecialchars($row['nama'])?></td>
                            <td><?=htmlspecialchars($row['kode_pinjaman'])?></td>
                            <td><?=$row['angsuran_ke']?></td>
                            <td>Rp <?=number_format($row['bayar_angsuran'],0,',','.')?></td>
                            <td class="amount-green">Rp <?=number_format($row['jumlah_bayar'],0,',','.')?></td>
                            <td><span class="status-badge <?=$sc?>"><?=$st?></span></td>
                            <td><?=date('d F Y H:i',strtotime($row['tanggal_bayar']))?></td>
                            <td class="action-buttons">
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?=$row['id']?>"><i class="bi bi-eye"></i></button>
                                <button class="btn btn-danger btn-sm" onclick="deletePayment(<?=$row['id']?>)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <!-- View Modal -->
                        <div class="modal fade" id="viewModal<?=$row['id']?>" tabindex="-1">
                            <div class="modal-dialog"><div class="modal-content">
                                <div class="modal-header"><h5 class="modal-title">Detail Pembayaran</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3"><label class="form-label">Nama:</label><p><?=htmlspecialchars($row['nama'])?></p></div>
                                            <div class="mb-3"><label class="form-label">Kode Pinjaman:</label><p><?=htmlspecialchars($row['kode_pinjaman'])?></p></div>
                                            <div class="mb-3"><label class="form-label">Angsuran Ke:</label><p><?=$row['angsuran_ke']?> / <?=$row['jangka_waktu']?></p></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3"><label class="form-label">Wajib Bayar:</label><p>Rp <?=number_format($row['bayar_angsuran'],0,',','.')?></p></div>
                                            <div class="mb-3"><label class="form-label">Dibayar:</label><p class="amount-green">Rp <?=number_format($row['jumlah_bayar'],0,',','.')?></p></div>
                                            <div class="mb-3"><label class="form-label">Kekurangan:</label>
                                                <p style="color:<?=$kurang>0?'#856404':'#155724'?>">Rp <?=number_format(max(0,$kurang),0,',','.')?></p></div>
                                        </div>
                                    </div>
                                    <div class="mb-3"><label class="form-label">Tanggal Bayar:</label><p><?=date('d F Y H:i',strtotime($row['tanggal_bayar']))?></p></div>
                                    <div class="mb-3"><label class="form-label">Status:</label><p><span class="status-badge <?=$sc?>"><?=$st?></span></p></div>
                                    <?php if(!empty($row['keterangan'])): ?>
                                    <div class="mb-3"><label class="form-label">Keterangan:</label><p><?=htmlspecialchars($row['keterangan'])?></p></div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
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

<!-- ADD PAYMENT MODAL -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form action="pembayaran_add.php" method="POST">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-file-earmark-plus me-2"></i>Input Pembayaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Pilih Pinjaman *</label>
                    <select class="form-control" name="pinjaman_id" id="pay_pinjaman_id" required onchange="loadPinjamanDetail(this)">
                        <option value="">-- Pilih Anggota / Pinjaman --</option>
                        <?php if($resultPinjaman && mysqli_num_rows($resultPinjaman)>0): while($p=mysqli_fetch_assoc($resultPinjaman)): ?>
                        <option value="<?=$p['id']?>" data-anggota="<?=$p['anggota_id']?>" data-nama="<?=htmlspecialchars($p['nama'])?>"
                                data-kode="<?=htmlspecialchars($p['kode_pinjaman'])?>" data-bayar="<?=$p['bayar_angsuran']?>" data-jangka="<?=$p['jangka_waktu']?>">
                            <?=htmlspecialchars($p['nama'])?> — <?=htmlspecialchars($p['kode_pinjaman'])?>
                        </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <input type="hidden" name="anggota_id" id="pay_anggota_id">
                <div class="mb-3">
                    <label class="form-label">Angsuran Ke *</label>
                    <input type="number" class="form-control" name="angsuran_ke" id="pay_angsuran_ke" min="1" required placeholder="contoh: 3">
                    <small class="text-muted" id="pay_jangka_info"></small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Wajib Bayar per Angsuran</label>
                    <div class="input-group"><span class="input-group-text">Rp</span>
                        <input type="text" class="form-control readonly-field" id="pay_wajib" readonly></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Dibayar *</label>
                    <div class="input-group"><span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" name="jumlah_bayar" id="pay_jumlah" min="1" required placeholder="contoh: 100000" oninput="hitungKurang()"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kekurangan</label>
                    <div class="input-group"><span class="input-group-text">Rp</span>
                        <input type="text" class="form-control readonly-field" id="pay_kurang" readonly></div>
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
            </div>
        </form>
    </div></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
document.getElementById("sidebarToggle").onclick = function() { document.getElementById("sidebar").classList.toggle("collapsed"); };
$(document).ready(function() {
    if($.fn.DataTable.isDataTable('#paymentTable')) $('#paymentTable').DataTable().destroy();
    $('#paymentTable').DataTable({ "paging":false,"searching":true,"ordering":true,"info":false,"language":{"search":"Cari:","zeroRecords":"Tidak ada data"} });

    // Set default to current date & time (local timezone-safe)
    var now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('pay_tanggal').value = now.toISOString().slice(0,16);
});
function toggleDropdown(e) { e.preventDefault(); e.currentTarget.closest('.nav-dropdown').classList.toggle('open'); }
function loadPinjamanDetail(select) {
    var opt = select.options[select.selectedIndex];
    document.getElementById('pay_anggota_id').value = opt.dataset.anggota || '';
    var bayar = parseFloat(opt.dataset.bayar) || 0;
    document.getElementById('pay_wajib').value = bayar ? bayar.toLocaleString('id-ID') : '';
    var jangka = opt.dataset.jangka || '';
    document.getElementById('pay_jangka_info').textContent = jangka ? 'Total jangka: ' + jangka + ' kali' : '';
    document.getElementById('pay_angsuran_ke').max = jangka || '';
    hitungKurang();
}
function hitungKurang() {
    var wajib = parseFloat(document.getElementById('pay_wajib').value.replace(/\./g,'').replace(',','.')) || 0;
    var dibayar = parseFloat(document.getElementById('pay_jumlah').value) || 0;
    var kurang = wajib - dibayar;
    document.getElementById('pay_kurang').value = kurang > 0 ? Math.round(kurang).toLocaleString('id-ID') : '0';
}
function deletePayment(id) {
    if(confirm('Hapus data pembayaran ini?')) {
        fetch('pembayaran_delete.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id='+id })
        .then(r=>r.json()).then(data=>{ if(data.success){alert('Dihapus!');location.reload();}else alert('Gagal: '+data.message); });
    }
}
setTimeout(function(){$('.alert').alert('close');},5000);
</script>
</body>
</html>