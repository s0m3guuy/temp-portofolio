<link rel="stylesheet" href="styls.css">
<head>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
</head>

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
<div class="full" style="padding-bottom: ">
    <br />
    <br />
    <table border ='1' style="color:white;">
        <tr>
            <th>song title</th>
            <th>song desc</th>
            <th>release date</th>
            <th>cocreator</th>
            <th>cover art</th>
            <th>audio file</th>
            <th>edit</th>
        </tr>
    <?php 
        include 'koneksi.php';
        $no = 1;
        $data = mysqli_query($koneksi,"select * from mahasiswa");
        while($d = mysqli_fetch_array($data)){
            ?>
            <tr>
                <td><?php echo $d['song_title']; ?></td>
                <td><?php echo $d['song_desc']; ?></td>
                <td><?php echo $d['release_date']; ?></td>
                <td><?php echo $d['cocreator']; ?></td>
                <td><img src="upload/images/<?php echo $d['img']; ?>" style="width: 100px;"></td>
                <td>
                    <audio controls>
                        <source src="upload/audio/<?php echo $d['audio']; ?>" type="audio/mpeg">
                        Your browser does not support the audio element.
                    </audio>
                </td>
                <td>
                    <a href="update.php?id=<?php echo $d['id']; ?>">EDIT</a>
                    |
                    <a href="hapus.php?id=<?php echo $d['id']; ?>">DELETE</a>
            </tr>
            <?php 
        }

        ?>
    </table>
    <br />
    <a href='tambah.php' style="display:flex; justify-content:center; font-size: 20px;">
        + add your own music
    </a>


    </div>