<?php
require 'koneksi.php';

$id = $_POST['id'];

mysqli_query($koneksi,"
UPDATE anggota
SET status = IF(status='aktif','nonaktif','aktif')
WHERE id=$id
");

header("Location: anggota.php");
?>