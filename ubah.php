<?php
include 'koneksi.php';

$id      = $_POST['id'];
$title   = $_POST['song_title'];
$descrip = $_POST['description'];
$date    = $_POST['date'];
$colaber = $_POST['cocreator'];

$imageDir = "upload/images/";
$audioDir = "upload/audio/";
//bikin like loc klo gaada, 0777 access perms
if (!is_dir($imageDir)) mkdir($imageDir, 0777, true);
if (!is_dir($audioDir)) mkdir($audioDir, 0777, true);

$result = mysqli_query($koneksi, "SELECT img, audio FROM mahasiswa WHERE id='$id'");
$old = mysqli_fetch_assoc($result);
$oldImage = $old['img'];
$oldAudio = $old['audio'];

// img klo ada bru
if (!empty($_FILES["image"]["name"])) {
    $imageName = basename($_FILES["image"]["name"]);
    $imagePath = $imageDir . $imageName;
    $imageType = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
    $allowedImages = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($imageType, $allowedImages)) {
        move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);
    } else {
        echo "Invalid image type.";
        exit;
    }
} else {
    $imageName = $oldImage; // nyimpen yg lama line 18
}

// audio bru klo ada
if (!empty($_FILES["song"]["name"])) {
    $audioName = basename($_FILES["song"]["name"]);
    $audioPath = $audioDir . $audioName;
    $audioType = strtolower(pathinfo($audioPath, PATHINFO_EXTENSION));
    $allowedAudio = ['mp3'];

    if (in_array($audioType, $allowedAudio)) {
        move_uploaded_file($_FILES["song"]["tmp_name"], $audioPath);
    } else {
        echo "Invalid audio type.";
        exit;
    }
} else {
    $audioName = $oldAudio; // nyimpen yg lama line 19
}

$query = "UPDATE mahasiswa SET 
            song_title='$title',
            song_desc='$descrip',
            release_date='$date',
            cocreator='$colaber',
            img='$imageName',
            audio='$audioName'
          WHERE id='$id'";

if (mysqli_query($koneksi, $query)) {
    echo "<script>
            window.location.href='dashboard.php';
          </script>";
} else {
    echo "Error updating record: " . mysqli_error($koneksi);
}
?>
