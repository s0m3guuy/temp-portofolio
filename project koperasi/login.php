<?php
session_start();
// If already logged in, redirect based on role
if(isset($_SESSION['user'])) {
    if($_SESSION['role'] == 'admin') {
        header('Location: brngerza/dashboard.php');
    } else {
        header('Location: mobileerza/dashboard.php');
    }
    exit();
}
require 'koneksi.php';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $query = "SELECT * FROM user WHERE username = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        if($user['role'] == 'admin') {
            header('Location: brngerza/dashboard.php');
        } else {
            header('Location: mobileerza/dashboard.php');
        }
        exit();
    } else {
        header('Location: login.php?error=1&message=Username+atau+password+salah');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PRI Link</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <?php if(isset($_GET['error'])): ?>
        <div style="position:fixed;top:0;left:0;right:0;background:#f8d7da;color:#721c24;padding:12px;text-align:center;font-size:0.95rem;z-index:999;">
            <?= htmlspecialchars(urldecode($_GET['message'] ?? 'Terjadi kesalahan!')) ?>
        </div>
    <?php endif; ?>

    <div class="base">
        <div class="title">
            <h2>PRI Link</h2>
        </div>
        <div class="container">
            <form method="POST">
                <div class="uname" style="padding:7px">
                    <label for="username"><b>Username</b></label><br>
                    <input type="text" placeholder="Enter Username" name="username" id="username" required>
                </div>
                <br>
                <div class="psw" style="padding:7px">
                    <label for="password"><b>Password</b></label><br>
                    <input type="password" placeholder="Enter Password" name="password" id="password" required>
                </div>
                <br>
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
