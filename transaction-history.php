<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <?php include "contact.php"; ?>
    <title>User-Transaction-History</title>
</head>
<body>
    <?php 
    include 'navbar-admin.php';
    include "contact.php";

    $ID = $_GET['id'];
    $queryUsername = "SELECT * FROM users WHERE userid='$ID'";
    $usern = mysqli_query($conn, $queryUsername)->fetch_all();
    ?>

    <div class="container mt-3">
        <h1>ID: <?=$ID;?> &nbsp; &nbsp; &nbsp; Username: <?=$usern[0][0];?></h1> <a href="user-management.php" class="btn btn-danger float-end">Back</a>
        <h3>Transaction history table</h3>
    </div>

</body>
</html>