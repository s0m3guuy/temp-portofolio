<head>
<link rel="stylesheet" href="styls.css">  
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
</head>
<body style="background-image: url('spaground.jpg'); background-size: cover; background-repeat: no-repeat; height: 100vh; background-attachment: fixed;">

<nav class="navbar navbar-expand-lg ml-auto" style="background-color: rgb(24, 20, 19); padding-left:300px !important; padding-right:400px !important;">


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
      <button class="btn my-2 my-sm-0 btn-login" type="submit"style="color:black;">Login</button>
    </form>
  </div>
  </div>
</nav>

<link rel="stylesheet" href="styl.css">
<div class=base>
<form action="" method="post">
  <div class="container">

    <h1>register</h1>
    <p></p>
    <hr>
    
    <label for="username" style="margin-right:42px; margin-top:10px" ><b>username</b></label><b>:</b>
    <input type="text" placeholder="Enter username" name="username" required>
    <br/>
    <label for="disname" style="margin-right:15px; margin-top:10px" ><b>display name</b></label><b>:</b>
    <input type="text" placeholder="Enter display name" name="disname" required>
    <br/>
    <label for="age" style="margin-right:87px; margin-top:10px" ><b>age</b></label><b>:</b>
    <input type="text" placeholder="Enter age" name="age" required>
    <br/>
    <label for="email"style="margin-right:75px"><b>email </b></label><b>:</b>
    <input type="email" placeholder="Enter email" name="email" required>
    <br/>
    <label for="psw" style ="margin-right:44px"><b>Password</b></label><b>:</b>
    <input type="password" placeholder="Enter Password" name="password" required>
    <br/>

    <div class="clearfix">
        <button type="submit" name="register" class="register" style="font-weight:bold;background-color:rgba(5, 218, 87, 1); margin-left:122px; max-width:500px; font-family: 'Poppins', 'Nunito', sans-serif; font-size:19px; border-radius:30px; text-align:center; padding:10px; width:200px; cursor:pointer;
">register</button>
    </div>
    <div> Already have an account? <a href="login.php">Login here</a>.</div>
  </div>
</form>

</div>

</body>
<?php
include 'koneksi.php';
if(isset ($_POST['register'])){
    $username = $_POST['username'];
    $disname = $_POST['disname'];
    $age = $_POST['age'];
    $email = $_POST['email'];

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);


    $query = "INSERT INTO users (username, disname, email,age, password) VALUES ('$username','$disname', '$email','$age', '$password')";
    $result = mysqli_query($koneksi, $query);
    if($result){
        echo "Registration successful. You can now <a href='login.php'>login</a>.";
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>