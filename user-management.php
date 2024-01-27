<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <?php include "contact.php"; ?>
    <title>User-Management</title>
</head>
<body>
    <?php include 'navbar-admin.php'; ?>

    <div class="container mt-3">
        <h1>Users</h1>

        <?php 
        $sql = "SELECT * FROM users WHERE user_type='0' ";
        $result = mysqli_query($conn, $sql)->fetch_all();
        ?>

        <table class="table table-bordered table-dark table-hover">
            <tr>
                <th>Delete</th>
                <th>ID</th>
                <th>Username</th>
                <th>Portfolio</th>
                <th>Details</th>
            </tr>
            <?php
            foreach ($result as $user) { ?>
                <tr>
                    <td class="text-center"><a onclick="swalDelete(<?=$user[0];?>)" class="btn btn-danger">Delete</a></td>
                    <td><?=$user[0];?></td>
                    <td><?=$user[1];?></td>
                    <td class="text-center"><a href="portfolio.php?id=<?=$user[0];?>" class="btn btn-primary">Portfolio</a></td>
                    <td class="text-center"><a href="transaction-history.php?id=<?=$user[0];?>" class="btn btn-primary">Transaction History</a></td>
                </tr>
            <?php 
            }
            ?>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript">
        function swalDelete(id) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'delete-user.php?id='+id;
                }
            });
        }
    </script>
</body>
</html>