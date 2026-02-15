<?php
session_start();
include "contact.php";

date_default_timezone_set("Europe/Istanbul");

// 100 coin
$allowed = [
    "BTC","ETH","BNB","SOL","XRP","ADA","DOGE","TON","DOT","AVAX",
    "LINK","MATIC","UNI","LTC","ATOM","XLM","ALGO","VET","ICP","FIL",
    "HBAR","APT","QNT","NEAR","GRT","SAND","MANA","AXS","THETA","RUNE",
    "EOS","AAVE","MKR","SNX","COMP","CRV","YFI","SUSHI","BAL","REN",
    "ZRX","OMG","KNC","LRC","BAND","ANKR","CHZ","ENJ","BAT","ZIL",
    "CELO","WAVES","ICX","ONT","ZEC","DASH","XTZ","ETC","NEO","QTUM",
    "DCR","LSK","SC","DGB","RVN","BTG","STEEM","STRAT","ARK","KMD",
    "NXT","XEM","ARDR","BURST","SYS","VIA","BLOCK","NAV","PIVX","POT",
    "MONA","NMC","PPC","RDD","VTC","BLK","BAY","CLOAK","PINK","XMY",
    "EMC2","FAIR","START","KORE","XST","IOC","SWIFT","DMD","GRS","MLN"
];
$in = "'" . implode("','", $allowed) . "'";

// pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;

// En güncel tarih (sadece allowed coinler arasında)
$maxDateSql = "SELECT MAX(price_date) AS max_date FROM stocks WHERE symbol IN ($in)";
$maxDateRes = mysqli_query($conn, $maxDateSql);
$maxDateRow = mysqli_fetch_assoc($maxDateRes);
$maxDate = $maxDateRow['max_date'] ?? null;

$resultRows = [];
$totalPages = 1;

if ($maxDate !== null) {
    // toplam satır sayısı
    $countSql = "SELECT COUNT(*) AS total FROM stocks WHERE price_date = '$maxDate' AND symbol IN ($in)";
    $countRes = mysqli_query($conn, $countSql);
    $totalRows = (int)mysqli_fetch_assoc($countRes)['total'];
    $totalPages = max(1, (int)ceil($totalRows / $perPage));

    // sayfa sınırı
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    // sayfalı veri
    $sql = "SELECT price_date, symbol, price
            FROM stocks
            WHERE price_date = '$maxDate'
              AND symbol IN ($in)
            ORDER BY symbol ASC
            LIMIT $perPage OFFSET $offset";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        $resultRows = $res->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <title>User-Page</title>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-3">
    <h1>Coins</h1>

    <table class="table table-bordered table-dark table-hover">
        <tr>
            <th>Date</th>
            <th>Symbol</th>
            <th>Price</th>
            <th>Details</th>
            <th>Buy</th>
        </tr>

        <?php if (!empty($resultRows)): ?>
            <?php foreach ($resultRows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['price_date']); ?></td>
                    <td><?= htmlspecialchars($row['symbol']); ?></td>
                    <td><?= number_format((float)$row['price'], 2); ?>$</td>
                    <td class="text-center">
                        <a href="coin-daily.php?coinSymbol=<?= urlencode($row['symbol']); ?>&backPage=<?= $page ?>"
                           class="btn btn-primary">Details</a>
                    </td>
                    <td class="text-center">
                        <a href="purchase.php?Symbol=<?= urlencode($row['symbol']); ?>"
                           class="btn btn-success">Buy</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="text-center">No data</td>
            </tr>
        <?php endif; ?>
    </table>

    <?php if ($totalPages > 1): ?>
        <?php
        $window = 2;
        $start = max(2, $page - $window);
        $end = min($totalPages - 1, $page + $window);

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

</body>
</html>
