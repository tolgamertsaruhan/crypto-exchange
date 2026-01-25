<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <?php include "contact.php"; ?>
    <title>User-Portfolio</title>

    <style>
    /* Bootstrap table-hover aksan rengini CSS variable ile veriyor.
       Biz row üzerinde bu variable'ları sabitleyince hover da aynı renk olur. */

    tr.history-buy {
        --bs-table-bg: #6f42c1 !important;
        --bs-table-striped-bg: #6f42c1 !important;
        --bs-table-hover-bg: #6f42c1 !important;
        --bs-table-active-bg: #6f42c1 !important;
        --bs-table-color: #ffffff !important;
    }

    tr.history-sell-profit {
        --bs-table-bg: #198754 !important;
        --bs-table-striped-bg: #198754 !important;
        --bs-table-hover-bg: #198754 !important;
        --bs-table-active-bg: #198754 !important;
        --bs-table-color: #ffffff !important;
    }

    tr.history-sell-loss {
        --bs-table-bg: #dc3545 !important;
        --bs-table-striped-bg: #dc3545 !important;
        --bs-table-hover-bg: #dc3545 !important;
        --bs-table-active-bg: #dc3545 !important;
        --bs-table-color: #ffffff !important;
    }
</style>

</head>

<body>
<?php
// Admin ise navbar-admin + GET id ile user görüntüleme
// User ise navbar + SESSION id ile kendi portfolio görüntüleme

if ($_SESSION['TYPE'] === "1") {
    include 'navbar-admin.php';

    $ID = $_GET['id'];
    $queryUsername = "SELECT * FROM users WHERE userid='$ID'";
    $usern = mysqli_query($conn, $queryUsername)->fetch_all();
} else {
    include 'navbar.php';

    $ID = $_SESSION['ID'];
    $queryUsername = "SELECT * FROM users WHERE userid='$ID'";
    $usern = mysqli_query($conn, $queryUsername)->fetch_all();
}

// Username
$usernameText = isset($usern[0][1]) ? $usern[0][1] : "User";

// Pagination
$portfolioPage = isset($_GET['ppage']) ? max(1, (int) $_GET['ppage']) : 1;
$historyPage   = isset($_GET['hpage']) ? max(1, (int) $_GET['hpage']) : 1;

$perPage = 3;

// Pagination helper
function renderPagination(string $baseUrl, int $currentPage, int $totalPages, string $pageParam): void
{
    if ($totalPages <= 1) return;

    $window = 1;
    $start = max(2, $currentPage - $window);
    $end   = min($totalPages - 1, $currentPage + $window);

    if ($currentPage <= 1 + $window) {
        $start = 2;
        $end = min($totalPages - 1, 1 + ($window * 2));
    }
    if ($currentPage >= $totalPages - $window) {
        $end = $totalPages - 1;
        $start = max(2, $totalPages - ($window * 2));
    }

    $joiner = (strpos($baseUrl, '?') !== false) ? '&' : '?';

    echo '<nav class="mt-3">';
    echo '<ul class="pagination pagination-sm justify-content-center flex-wrap">';

    $prevDisabled = ($currentPage <= 1) ? 'disabled' : '';
    $prevPage = $currentPage - 1;
    echo '<li class="page-item '.$prevDisabled.'"><a class="page-link" href="'.$baseUrl.$joiner.$pageParam.'='.$prevPage.'">Prev</a></li>';

    $active1 = ($currentPage == 1) ? 'active' : '';
    echo '<li class="page-item '.$active1.'"><a class="page-link" href="'.$baseUrl.$joiner.$pageParam.'=1">1</a></li>';

    if ($start > 2) {
        echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }

    for ($p = $start; $p <= $end; $p++) {
        $active = ($p == $currentPage) ? 'active' : '';
        echo '<li class="page-item '.$active.'"><a class="page-link" href="'.$baseUrl.$joiner.$pageParam.'='.$p.'">'.$p.'</a></li>';
    }

    if ($end < $totalPages - 1) {
        echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }

    $activeLast = ($currentPage == $totalPages) ? 'active' : '';
    echo '<li class="page-item '.$activeLast.'"><a class="page-link" href="'.$baseUrl.$joiner.$pageParam.'='.$totalPages.'">'.$totalPages.'</a></li>';

    $nextDisabled = ($currentPage >= $totalPages) ? 'disabled' : '';
    $nextPage = $currentPage + 1;
    echo '<li class="page-item '.$nextDisabled.'"><a class="page-link" href="'.$baseUrl.$joiner.$pageParam.'='.$nextPage.'">Next</a></li>';

    echo '</ul>';
    echo '</nav>';
}

// ----------------------------------------------------
// PORTFOLIO
// ----------------------------------------------------
$portfolioOffset = ($portfolioPage - 1) * $perPage;

$stmtCountP = $conn->prepare("SELECT COUNT(*) AS total FROM stock_portfolio WHERE userid = ?");
$stmtCountP->bind_param("i", $ID);
$stmtCountP->execute();
$totalPortfolioRows = (int) $stmtCountP->get_result()->fetch_assoc()['total'];
$stmtCountP->close();

$totalPortfolioPages = max(1, (int) ceil($totalPortfolioRows / $perPage));
if ($portfolioPage > $totalPortfolioPages) $portfolioPage = $totalPortfolioPages;
$portfolioOffset = ($portfolioPage - 1) * $perPage;

$stmtP = $conn->prepare("
    SELECT portfolio_id, userid, symbol, shares, purchase_price
    FROM stock_portfolio
    WHERE userid = ?
    ORDER BY portfolio_id DESC
    LIMIT ? OFFSET ?
");
$stmtP->bind_param("iii", $ID, $perPage, $portfolioOffset);
$stmtP->execute();
$portfolioResult = $stmtP->get_result();
$portfolioRows = [];
while ($r = $portfolioResult->fetch_assoc()) $portfolioRows[] = $r;
$stmtP->close();

function getLatestPrice(mysqli $conn, string $symbol): ?float
{
    $stmt = $conn->prepare("SELECT price FROM stocks WHERE symbol = ? ORDER BY price_date DESC LIMIT 1");
    $stmt->bind_param("s", $symbol);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if (!$row) return null;
    return (float) $row['price'];
}

// ----------------------------------------------------
// HISTORY
// ----------------------------------------------------
$historyOffset = ($historyPage - 1) * $perPage;

$stmtCountH = $conn->prepare("SELECT COUNT(*) AS total FROM transaction_history WHERE userid = ?");
$stmtCountH->bind_param("i", $ID);
$stmtCountH->execute();
$totalHistoryRows = (int) $stmtCountH->get_result()->fetch_assoc()['total'];
$stmtCountH->close();

$totalHistoryPages = max(1, (int) ceil($totalHistoryRows / $perPage));
if ($historyPage > $totalHistoryPages) $historyPage = $totalHistoryPages;
$historyOffset = ($historyPage - 1) * $perPage;

$stmtH = $conn->prepare("
    SELECT portfolio_id, userid, symbol, shares,
           purchase_price, purchase_date,
           sell_price, sell_date,
           profit_loss
    FROM transaction_history
    WHERE userid = ?
    ORDER BY COALESCE(sell_date, purchase_date) DESC, portfolio_id DESC
    LIMIT ? OFFSET ?
");
$stmtH->bind_param("iii", $ID, $perPage, $historyOffset);
$stmtH->execute();
$historyResult = $stmtH->get_result();
$historyRows = [];
while ($r = $historyResult->fetch_assoc()) $historyRows[] = $r;
$stmtH->close();
?>

<div class="container mt-3">

    <?php if ($_SESSION['TYPE'] === "1") { ?>
        <a href="user-management.php" class="btn btn-danger float-end">Back</a>
        <div class="clearfix"></div>
    <?php } ?>

    <h3 class="mt-3">Portfolio</h3>

    <?php if ($totalPortfolioRows > 0): ?>
        <table class="table table-bordered table-dark table-hover">
            <tr>
                <th>Symbol</th>
                <th>Shares</th>
                <th>Avg Buy Price ($)</th>
                <th>Current Price ($)</th>
                <th>Current Value ($)</th>
                <th>Profit/Loss ($)</th>
            </tr>

            <?php foreach ($portfolioRows as $row): ?>
                <?php
                $symbol = $row['symbol'];
                $shares = (float) $row['shares'];
                $avgBuy = (float) $row['purchase_price'];
                $cur = getLatestPrice($conn, $symbol);
                $curPrice = ($cur === null) ? 0.0 : (float) $cur;

                $value = $curPrice * $shares;
                $pl = ($curPrice - $avgBuy) * $shares;

                $plClass = '';
                if ($pl > 0) $plClass = 'text-success';
                elseif ($pl < 0) $plClass = 'text-danger';
                ?>
                <tr>
                    <td><?= htmlspecialchars($symbol); ?></td>
                    <td><?= number_format($shares, 8); ?></td>
                    <td><?= number_format($avgBuy, 2); ?></td>
                    <td><?= number_format($curPrice, 2); ?></td>
                    <td><?= number_format($value, 2); ?></td>
                    <td class="<?= $plClass; ?>"><?= number_format($pl, 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <?php
        $pBase = ($_SESSION['TYPE'] === "1")
            ? "portfolio.php?id=" . urlencode((string)$ID) . "&hpage=" . $historyPage
            : "portfolio.php?hpage=" . $historyPage;

        renderPagination($pBase, $portfolioPage, $totalPortfolioPages, "ppage");
        ?>

    <?php else: ?>
        <h5 class="text-danger mt-2">Your portfolio is empty.</h5>
    <?php endif; ?>

    <h3 class="mt-4">History</h3>

    <?php if ($totalHistoryRows > 0): ?>
        <table class="table table-bordered table-dark table-hover">
            <tr>
                <th>Date</th>
                <th>Symbol</th>
                <th>Shares</th>
                <th>Trade Price ($)</th>
                <th>Profit/Loss ($)</th>
            </tr>

            <?php foreach ($historyRows as $row): ?>
                <?php
                $symbol = $row['symbol'];
                $shares = (float) $row['shares'];

                $dateValue = ($row['sell_date'] !== null) ? $row['sell_date'] : $row['purchase_date'];

                $tradePriceValue = null;
                if ($row['sell_price'] !== null) {
                    $tradePriceValue = (float) $row['sell_price'];
                } elseif ($row['purchase_price'] !== null) {
                    $tradePriceValue = (float) $row['purchase_price'];
                }

                $tradePriceText = ($tradePriceValue === null) ? '' : number_format($tradePriceValue, 2);

                $isSell = ($row['sell_date'] !== null || $row['sell_price'] !== null);

                $plText = '';
                $plValue = null;
                if ($isSell && $row['profit_loss'] !== null && $row['profit_loss'] !== '') {
                    $plValue = (float) $row['profit_loss'];
                    $plText = number_format($plValue, 2);
                }

                $rowClass = '';
                if (!$isSell) {
                    $rowClass = 'history-buy';
                } else {
                    if ($plValue !== null && $plValue > 0) {
                        $rowClass = 'history-sell-profit';
                    } else {
                        $rowClass = 'history-sell-loss';
                    }
                }
                ?>
                <tr class="<?= $rowClass; ?>">
                    <td><?= htmlspecialchars((string)$dateValue); ?></td>
                    <td><?= htmlspecialchars($symbol); ?></td>
                    <td><?= number_format($shares, 8); ?></td>
                    <td><?= $tradePriceText; ?></td>
                    <td><?= $plText; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <?php
        $hBase = ($_SESSION['TYPE'] === "1")
            ? "portfolio.php?id=" . urlencode((string)$ID) . "&ppage=" . $portfolioPage
            : "portfolio.php?ppage=" . $portfolioPage;

        renderPagination($hBase, $historyPage, $totalHistoryPages, "hpage");
        ?>

    <?php else: ?>
        <h5 class="text-danger mt-2">You don’t have any history yet.</h5>
    <?php endif; ?>

</div>

</body>
</html>
