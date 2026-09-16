<?php
if(isset($_POST['password'])) {
    $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
    echo "<p>Hash: <strong>" . $hashed . "</strong></p>";
    echo "<p>Copy the hash above and paste it into your database.</p>";
}
?>
<!DOCTYPE html>
<html>
<body>
    <form method="POST">
        <input type="text" name="password" placeholder="Enter password to hash">
        <button type="submit">Generate Hash</button>
    </form>
</body>
</html>
