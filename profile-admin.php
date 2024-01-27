<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <?php include "contact.php"; ?>
    <title>Admin-Profile</title>
</head>
<body>
    <?php 
    include 'navbar-admin.php';
    include "contact.php";
    ?>

    <div class="container mt-5">
        <table class="table table-bordered table-dark table-hover">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Password</th>
            </tr>
            <tr>
                <td><?=$_SESSION["ID"];?></td>
                <td><?=$_SESSION["USERNAME"];?></td>
                <td><?=$_SESSION["PASSWORD"];?></td>
            </tr>
        </table>
    </div>

    <div class="container2 mt-5 text-center">
        <h3>Change Password</h3>
        <form method="post" action="change-password.php" enctype="multipart/form-data">
            <?php if (isset($_GET['error'])) { ?>
                <p class="error"><?php echo $_GET['error']; ?></p>
            <?php }?>
            <div class="form-group">
                <input type="password" class="form-control mt-3" id="password" name="password">
            </div>

            <button type="submit" name="change" class="btn btn-primary mt-3">Change</button>
        </form>
    </div>

</body>
</html>