<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <?php include "contact.php"; ?>
    <title>Daily</title>
</head>
<body>
    <?php 
    if($_SESSION['TYPE'] === "0") {
        include 'navbar.php'; 
    }else{
        include 'navbar-admin.php'; 
    }
    ?>

    <div class="container mt-3">
        <?php 
        $symbol = $_GET['coinSymbol']; 
        
        if($_SESSION['TYPE'] === "0") { 
            ?>
            <h1><?=$symbol?><a href="user-main.php" class="btn btn-danger float-end mb-3">Back</a></h1> 
            <?php
        }else{
            ?>
            <h1><?=$symbol?><a href="admin-main.php" class="btn btn-danger float-end mb-3">Back</a></h1> 
            <?php
        }
        ?>


        <?php 
        
        $sql = "SELECT * FROM stocks WHERE symbol = '$symbol' ORDER BY price_date DESC";
        $result = mysqli_query($conn, $sql)->fetch_all();
        $count = 0;
        ?>

        <table class="table table-bordered table-dark table-hover">
            <tr>
                <th>Date</th>
                <th>Symbol</th>
                <th>Price</th>
            </tr>

            <?php
            for($i=0;$i<30;$i++) {
                ?>
                <tr>
                    <td><?=$result[$count][2];?></td>
                    <td><?=$result[$count][1];?></td>
                    <td><?=$result[$count][3];?>$</td>
                </tr>
                <?php
                $count = $count + 1;
            }
            ?>
        </table>
    </div>

</body>
</html>