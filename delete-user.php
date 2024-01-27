<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<?php
    include "contact.php";
    if (isset($_GET['id'])) {
        $query= $conn->prepare("DELETE FROM users WHERE userid=?");
        $query->bind_param('s', $_GET['id']);
        $control=$query->execute();
    }

    if($control) {
        header("location:user-management.php");
    }
?>
</body>
</html>