
<head>
  <link rel="stylesheet" href="styls.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
</head>
<body style="background-image: url('spaground.jpg'); background-size: cover; background-repeat: no-repeat; height: 100vh; background-attachment: fixed; ">

<nav class="navbar navbar-expand-lg ml-auto" style="background-color: rgb(24, 20, 19); padding-left:300px !important; padding-right:400px !important; ">


  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <a class="navbar-brand" href="dashboard.php" style="color: white; font-size: 20x; font-weight: bold;">
      <img src="spogo.jpg" style="width: 40px;"> spotify
    </a>  

  </div>

  <div class="ml-auto d-flex align-items-center"> 
    <li class="nav-item">
      <a href="abt.php">
        <b class="navbar-brand" style="color:rgba(255, 255, 255, 1); font-size:15px">about us</b>
      </a>
    </li>
    <a href="https://www.spotify.com/us/premium">
      <b class="navbar-brand" style="color:rgba(255, 255, 255, 1); font-size:15px">
        Premium
      </b>
    </a>  

    <b class="navbar-brand" style="color:rgba(255, 255, 255, 1); font-size:15px">Download</b>
    <b class="navbar-brand" style="color:rgba(255, 255, 255, 1); font-size:15px">|</b>

    <form class="form-inline my-2 my-lg-0" action="register.php">
      <b><button class="btn my-2 my-sm-0 btn-register" type="submit" style="background-color:transparent !important; color:white;">sign up</button></b>
    </form>

    <form class="form-inline my-2 my-lg-0" action="login.php">
      <button class="btn my-2 my-sm-0 btn-login" type="submit" style="color:black;">Login</button>
    </form>
  </div>
  </div>
</nav>

</body>
<?php
session_start();
include 'koneksi.php';

if(isset($_POST['login'])) {
    $username = $_POST['uname'];
    $password = $_POST['psw'];

    $query = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($koneksi, $query);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    }
    else {
        echo "<script>alert('Invalid username or password.');</script>";
    }
    
}

?>
<link rel="stylesheet" href="styl.css">
<div class=base >
    <form method="post">
        <h2>Login Form</h2>
    <hr>
    <div class="container">
            <label for="uname"style ="margin-right:42px"><b>Username </b></label><b>:</b>
            <input type="text" placeholder="Enter Username" name="uname" required>
        </br>
            <label for="psw" style ="margin-right:46px"><b>Password  </b></label><b>:</b>
            <input type="password" placeholder="Enter Password" name="psw" required>
        </br>
        <button type="submit" name ="login" style="font-weight:bold;background-color:rgba(5, 218, 87, 1); margin-left:122px; max-width:500px; font-family: 'Poppins', 'Nunito', sans-serif; font-size:19px; border-radius:30px; text-align:center; padding:10px; width:200px; cursor:pointer;">Login</button>

    </div>

    <div class="container" >
        <span class="psw"> <a href="#">Forgot password?</a> <a href="register.php"> register </a></span>
    </div>
    </form>
</div>

