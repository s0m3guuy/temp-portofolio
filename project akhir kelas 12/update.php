<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="styls.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
</head>
<body>
<nav class="navbar navbar-expand-lg ml-auto" style="background-color: black;">


  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <a class="navbar-brand" href="index.php">
      <img src="spogo.jpg" style="width: 40px">
    </a>  
    <ul class="navbar-nav mx-auto" style="padding-left: 350px;">
      <li class="nav-item active">
        <a class="nav-link" href="dashboard.php"><img src="home icon.png" style="width: 30px"> <span class="sr-only">(current)</span></a>
      </li>
      <li class ="nav-item">
        <form class="form-inline my-2 my-lg-0 ">
          <input class="form-control " type="search" placeholder="What do you want to play?" aria-label="Search"
            style="
              width: 470px;
              height : 50px;
              text-align: left;
              border: 1px solid rgb(43, 43, 43);
              border-radius: 40px;
              background-color: rgb(43, 43, 43);
              background-image: url('search icon.png'), url('brose icon.png');
              background-repeat: no-repeat, no-repeat;
              background-position: 10px center, calc(100% - 20px) center;
              background-size: 18px, 30px;
              padding-right: 50px;
              padding-left: 35px;
              color: #fff;
            ">
        </form>
      </li>
    </ul>
  </div>

  <div class="ml-auto d-flex align-items-center"> 
    <li class="nav-item">
      <a href="abt.php">
        <b class="navbar-brand" style="color:rgba(146, 146, 146, 1); font-size:15px">about us</b>
      </a>
    </li>
    <a href="https://www.spotify.com/us/premium">
      <b class="navbar-brand" style="color:rgba(146, 146, 146, 1); font-size:15px">
        Premium
      </b>
    </a>

    <b class="navbar-brand" style="color:rgba(146, 146, 146, 1); font-size:15px">Download</b>
    <b class="navbar-brand" style="color:rgba(146, 146, 146, 1); font-size:15px">|</b>

    <form class="form-inline my-2 my-lg-0" action="register.php">
      <b><button class="btn my-2 my-sm-0 btn-register" type="submit">sign up</button></b>
    </form>

    <form class="form-inline my-2 my-lg-0" action="login.php">
      <button class="btn my-2 my-sm-0 btn-login" type="submit">Login</button>
    </form>



  </div>

  </div>
</nav>

<?php 
include 'koneksi.php';
$id = $_GET['id'];
$data = mysqli_query($koneksi,"select * from mahasiswa where id='$id'");
while($d = mysqli_fetch_array($data)){
    ?>

  <div class="full" style="margin-top:70px;">  
    <fieldset>
      <legend style="color:white; font-weight:bold; font-size:34px;">Edit Your Music </legend>

      <form method="post" action="ubah.php" enctype="multipart/form-data">
     <table style="color:white">
        <tr>
          <td>Song title : </td>
          <td><input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                <input class="inbox" type="text" name="song_title" value="<?php echo $d['song_title']; ?>">
            </td>
        </tr>
        <tr>
          <td>Song description : </td>
          <td><input type="text" name="description" class="inbox" required value="<?php echo $d['song_desc']?>"></td>
        </tr>
        <tr>
          <td>Release date : </td>
          <td><input type="text" name="date" class="inbox" required value="<?php echo $d['release_date']?>"></td>
        </tr>
        <tr>
          <td>Cocreators :</td>
          <td><input type="text" name="cocreator" class="inbox" value= "<?php echo $d['cocreator']?>"></td>
        </tr>
        <tr>
          <td>Song cover image :</td>
          <td>
            <input type="file" name="image" accept="image/*"  >
        </tr>
        <tr>
          <td>Audio file (MP3) :</td>
          <td>
            <input type="file" name="song" accept="audio/mpeg,audio/mp3">
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" value="Update" style="font-weight:bold;background-color:rgba(5, 218, 87, 1); margin-left:-30px; max-width:500px; font-family: 'Poppins', 'Nunito', sans-serif; font-size:19px; border-radius:30px; text-align:center; padding:10px; width:200px; cursor:pointer;"></td>
            <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
        </tr>
      </table>
    </form>


      <br>
      <a href="dashboard.php">KEMBALI</a>
    
  </fieldset>
  </div>
</body>
</html>

    <?php 
}
