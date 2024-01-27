<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <?php include "contact.php"; ?>
    <title>Admin-Page</title>
</head>
<body>
    <?php include 'navbar-admin.php'; ?>

    <div class="container mt-3">
        <h1>Coins</h1>

        <?php 
        $sql = "SELECT * FROM stocks ORDER BY price_date DESC ";
        $result = mysqli_query($conn, $sql)->fetch_all();
        $count = 0;
        ?>

        <table class="table table-bordered table-dark table-hover">
            <tr>
                <th>Date</th>
                <th>Symbol</th>
                <th>Price</th>
                <th>Details</th>
            </tr>
            <tr>
                    <td><?=$result[$count][2];?></td>
                    <td><?=$result[$count][1];?></td>
                    <td><?=$result[$count][3];?>$</td>
                    <td class="text-center"><a href="coin-daily.php?coinSymbol=<?=$result[$count][1];?>" class="btn btn-primary">Details</a></td>
            </tr>
            <?php
            for($i=0;$i<count($result);$i++) {
                if ($result[$count][2] === $result[$count+1][2]) {
                    $count = $count + 1;?>
                    <tr>
                    <td><?=$result[$count][2];?></td>
                    <td><?=$result[$count][1];?></td>
                    <td><?=$result[$count][3];?>$</td>
                    <td class="text-center"><a href="coin-daily.php?coinSymbol=<?=$result[$count][1];?>" class="btn btn-primary">Details</a></td>
                    </tr>
                    <?php
                }
            }
            ?>
        </table>
    </div>

</body>
</html>