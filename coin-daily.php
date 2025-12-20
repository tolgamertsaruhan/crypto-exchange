<?php
session_start();
include "contact.php";

// --- Parametreler ---
$symbol = $_GET['coinSymbol'] ?? '';
$symbol = trim($symbol);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$perPage = 7;
$offset  = ($page - 1) * $perPage;

// Eğer coinSymbol yoksa güvenli şekilde geri dön
if ($symbol === '') {
    // İstersen index'e veya main sayfaya atabilirsin
    header("Location: user-main.php");
    exit();
}

// --- Toplam kayıt sayısı ---
$stmtCount = $conn->prepare("SELECT COUNT(*) FROM stocks WHERE symbol = ?");
$stmtCount->bind_param("s", $symbol);
$stmtCount->execute();
$stmtCount->bind_result($totalRows);
$stmtCount->fetch();
$stmtCount->close();

$totalPages = (int)ceil($totalRows / $perPage);
if ($totalPages < 1) $totalPages = 1;
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// --- Sayfa verisi (10 kayıt) ---
$stmt = $conn->prepare("
    SELECT data_id, symbol, price_date, price
    FROM stocks
    WHERE symbol = ?
    ORDER BY price_date DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("sii", $symbol, $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <title>Daily</title>
</head>
<body>

<?php
// Navbar seçimi
if (isset($_SESSION['TYPE']) && $_SESSION['TYPE'] === "0") {
    include 'navbar.php';
} else {
    include 'navbar-admin.php';
}
?>

<div class="container mt-3">

    <?php if (isset($_SESSION['TYPE']) && $_SESSION['TYPE'] === "0") : ?>
        <h1>
            <?= htmlspecialchars($symbol) ?>
            <a href="user-main.php" class="btn btn-danger float-end mb-3">Back</a>
        </h1>
    <?php else: ?>
        <h1>
            <?= htmlspecialchars($symbol) ?>
            <a href="admin-main.php" class="btn btn-danger float-end mb-3">Back</a>
        </h1>
    <?php endif; ?>

    <table class="table table-bordered table-dark table-hover">
        <thead>
        <tr>
            <th>Date</th>
            <th>Symbol</th>
            <th>Price</th>
        </tr>
        </thead>

        <tbody>
        <?php if (count($rows) === 0): ?>
            <tr>
                <td colspan="3">Bu coin için veri bulunamadı.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['price_date']) ?></td>
                    <td><?= htmlspecialchars($r['symbol']) ?></td>
                    <td><?= htmlspecialchars($r['price']) ?>$</td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <?php
        // kaç sayfa butonu göstereceğiz (aktif sayfanın sağ/solu)
        $window = 2; // aktif sayfanın iki sağı, iki solu (toplam 5 buton gibi düşün)

        $start = max(2, $page - $window);
        $end   = min($totalPages - 1, $page + $window);

        // Eğer başa çok yakınsak daha çok sağa doğru göster
        if ($page <= 1 + $window) {
            $start = 2;
            $end = min($totalPages - 1, 1 + ($window * 2));
        }

        // Eğer sona çok yakınsak daha çok sola doğru göster
        if ($page >= $totalPages - $window) {
            $end = $totalPages - 1;
            $start = max(2, $totalPages - ($window * 2));
        }
    ?>

    <nav aria-label="Page navigation" class="mt-3">
        <ul class="pagination pagination-sm justify-content-center flex-wrap">


            <!-- Prev -->
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link"
                   href="coin-daily.php?coinSymbol=<?= urlencode($symbol) ?>&page=<?= $page - 1 ?>">
                    Prev
                </a>
            </li>

            <!-- 1 -->
            <li class="page-item <?= ($page == 1) ? 'active' : '' ?>">
                <a class="page-link" href="coin-daily.php?coinSymbol=<?= urlencode($symbol) ?>&page=1">1</a>
            </li>

            <!-- Sol ... -->
            <?php if ($start > 2): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>

            <!-- Orta sayfalar -->
            <?php for ($p = $start; $p <= $end; $p++): ?>
                <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                    <a class="page-link"
                       href="coin-daily.php?coinSymbol=<?= urlencode($symbol) ?>&page=<?= $p ?>">
                        <?= $p ?>
                    </a>
                </li>
            <?php endfor; ?>

            <!-- Sağ ... -->
            <?php if ($end < $totalPages - 1): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>

            <!-- Son sayfa -->
            <li class="page-item <?= ($page == $totalPages) ? 'active' : '' ?>">
                <a class="page-link"
                   href="coin-daily.php?coinSymbol=<?= urlencode($symbol) ?>&page=<?= $totalPages ?>">
                    <?= $totalPages ?>
                </a>
            </li>

            <!-- Next -->
            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link"
                   href="coin-daily.php?coinSymbol=<?= urlencode($symbol) ?>&page=<?= $page + 1 ?>">
                    Next
                </a>
            </li>

        </ul>
    </nav>
<?php endif; ?>


</div>

</body>
</html>
