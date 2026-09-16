<?php
include 'koneksi.php';

$title   = $_POST['title'];
$descrip = $_POST['description'];
$date    = $_POST['date'];
$colaber = $_POST['cocreator'];

$imageDir = "upload/images/";
$audioDir = "upload/audio/";

//bikin like loc klo gaada, 0777 access perms
if (!is_dir($imageDir)) mkdir($imageDir, 0777, true);
if (!is_dir($audioDir)) mkdir($audioDir, 0777, true);

//upload file image
$imageName = basename($_FILES["image"]["name"]);
$imagePath = $imageDir . $imageName;
$imageType = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
$allowedImages = ['jpg', 'jpeg', 'png', 'gif'];

//upload file audio
$audioName = basename($_FILES["song"]["name"]);
$audioPath = $audioDir . $audioName;
$audioType = strtolower(pathinfo($audioPath, PATHINFO_EXTENSION));
$allowedAudio = ['mp3'];
//ngecek tipe file sdh sesuai ato ga
if (in_array($imageType, $allowedImages) && in_array($audioType, $allowedAudio)) {
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath)) {//naru image di folder
        if (move_uploaded_file($_FILES["song"]["tmp_name"], $audioPath)) {//naru audio di folder
            //naru data ke database
            $query = "INSERT INTO mahasiswa (song_title, song_desc, release_date, cocreator, img, audio)
                      VALUES ('$title', '$descrip', '$date', '$colaber', '$imageName', '$audioName')";

            if (mysqli_query($koneksi, $query)) {
                header("Location: dashboard.php");
                exit;
            } else {
                echo "<script>alert('Invalid username or password.');</script>" . mysqli_error($koneksi);
                
            }

        } else {
            echo "Failed to upload audio file.";
        }
    } else {
        echo "Failed to upload image file.";
    }
} else {
    echo "Invalid file type. Image must be JPG/PNG/GIF, and audio must be MP3.";
}
?>
