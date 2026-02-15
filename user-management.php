<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <?php include "contact.php"; ?>
    <title>User-Management</title>
</head>
<body>

<?php include 'navbar-admin.php'; ?>

<?php
/* ================= PAGINATION ================= */

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;

/* ===== COUNT ===== */

$countSql = "SELECT COUNT(*) AS total FROM users WHERE user_type='0'";
$countRes = mysqli_query($conn, $countSql);
$totalRows = (int)mysqli_fetch_assoc($countRes)['total'];

$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

/* ===== DATA ===== */

$sql = "
SELECT * 
FROM users 
WHERE user_type='0'
ORDER BY userid ASC
LIMIT $perPage OFFSET $offset
";
$result = mysqli_query($conn, $sql)->fetch_all();
?>

<div class="container mt-3">
    <h1>Users</h1>

    <table class="table table-bordered table-dark table-hover">
        <tr>
            <th>Delete</th>
            <th>ID</th>
            <th>Username</th>
            <th>Portfolio</th>
            <th>Details</th>
        </tr>

        <?php foreach ($result as $user): ?>
            <tr>
                <td class="text-center">
                    <a onclick="swalDelete(<?= $user[0]; ?>)" class="btn btn-danger">Delete</a>
                </td>
                <td><?= $user[0]; ?></td>
                <td><?= $user[1]; ?></td>
                <td class="text-center">
                    <a href="portfolio.php?id=<?= $user[0]; ?>" class="btn btn-primary">Portfolio</a>
                </td>
                <td class="text-center">
                    <a href="transaction-history.php?id=<?= $user[0]; ?>" class="btn btn-primary">Transaction History</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
        <?php
        $window = 2;
        $start = max(2, $page - $window);
        $end   = min($totalPages - 1, $page + $window);

        if ($page <= 1 + $window) {
            $start = 2;
            $end = min($totalPages - 1, 1 + ($window * 2));
        }
        if ($page >= $totalPages - $window) {
            $end = $totalPages - 1;
            $start = max(2, $totalPages - ($window * 2));
        }
        ?>
        <nav class="mt-3">
            <ul class="pagination pagination-sm justify-content-center flex-wrap">

                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>">Prev</a>
                </li>

                <li class="page-item <?= ($page == 1) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=1">1</a>
                </li>

                <?php if ($start > 2): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>

                <?php for ($p = $start; $p <= $end; $p++): ?>
                    <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($end < $totalPages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>

                <li class="page-item <?= ($page == $totalPages) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
                </li>

                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                </li>

            </ul>
        </nav>
    <?php endif; ?>

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
            window.location.href = 'delete-user.php?id=' + id;
        }
    });
}
</script>

</body>
</html>
