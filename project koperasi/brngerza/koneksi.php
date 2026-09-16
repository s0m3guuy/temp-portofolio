<?php
$host = getenv('MYSQLHOST')     ?: 'sql210.infinityfree.com';
$user = getenv('MYSQLUSER')     ?: 'if0_42321190';
$pass = getenv('MYSQLPASSWORD') ?: '66dFeNfAsFjF579';
$db   = getenv('MYSQLDATABASE') ?: 'if0_42321190_PRILink';
$port = getenv('MYSQLPORT')     ?: 3306;

$koneksi = mysqli_connect($host, $user, $pass, $db, (int)$port);

if(!$koneksi) {
    die("Connection failed: " . mysqli_connect_error());
}
