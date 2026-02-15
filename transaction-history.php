<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<?php include "contact.php"; ?>

<title>User-Transaction-History</title>

<style>
tr.buy-row{
--bs-table-bg:#6f42c1!important;
--bs-table-hover-bg:#6f42c1!important;
--bs-table-color:#fff!important;
}

tr.sell-profit{
--bs-table-bg:#198754!important;
--bs-table-hover-bg:#198754!important;
--bs-table-color:#fff!important;
}

tr.sell-loss{
--bs-table-bg:#dc3545!important;
--bs-table-hover-bg:#dc3545!important;
--bs-table-color:#fff!important;
}
</style>

</head>

<body>

<?php
include 'navbar-admin.php';

$ID = (int)$_GET['id'];

$queryUsername = "SELECT * FROM users WHERE userid='$ID'";
$usern = mysqli_query($conn, $queryUsername)->fetch_all();

$username = $usern[0][1] ?? 'User';

/* ================= PAGINATION ================= */

$perPage = 5;
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

/* ===== COUNT ===== */

$stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM transaction_history WHERE userid=?");
$stmtCount->bind_param("i",$ID);
$stmtCount->execute();
$totalRows = (int)$stmtCount->get_result()->fetch_assoc()['total'];
$stmtCount->close();

$totalPages = max(1,ceil($totalRows / $perPage));

if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

/* ===== DATA ===== */

$stmt = $conn->prepare("
SELECT symbol, shares, purchase_price, purchase_date,
       sell_price, sell_date, profit_loss
FROM transaction_history
WHERE userid=?
ORDER BY COALESCE(sell_date,purchase_date) DESC
LIMIT ? OFFSET ?
");
$stmt->bind_param("iii",$ID,$perPage,$offset);
$stmt->execute();
$res = $stmt->get_result();

$rows=[];
while($r=$res->fetch_assoc()) $rows[]=$r;
$stmt->close();

/* ========== PAGINATION FUNCTION (SAME STYLE) ========== */

function renderPagination($baseUrl,$current,$total){
if($total<=1) return;

echo '<nav class="mt-3">';
echo '<ul class="pagination pagination-sm justify-content-center">';

$prev = $current-1;
$next = $current+1;

echo '<li class="page-item '.($current==1?'disabled':'').'">
<a class="page-link" href="'.$baseUrl.'&page='.$prev.'">Prev</a></li>';

for($i=1;$i<=$total;$i++){
$active = $i==$current?'active':'';
echo '<li class="page-item '.$active.'">
<a class="page-link" href="'.$baseUrl.'&page='.$i.'">'.$i.'</a></li>';
}

echo '<li class="page-item '.($current==$total?'disabled':'').'">
<a class="page-link" href="'.$baseUrl.'&page='.$next.'">Next</a></li>';

echo '</ul></nav>';
}
?>

<div class="container mt-3">

<h3 class="mt-2">
User ID: <?= $ID ?> | Username: <?= htmlspecialchars($username) ?>
</h3>

<div class="d-flex justify-content-between align-items-center mt-3 mb-2">
    <h4 class="mb-0">Transaction History</h4>
    <a href="user-management.php" class="btn btn-danger">Back</a>
</div>


<?php if($totalRows>0): ?>

<table class="table table-bordered table-dark table-hover">

<tr>
<th>Date</th>
<th>Symbol</th>
<th>Shares</th>
<th>Trade Price ($)</th>
<th>Profit/Loss ($)</th>
</tr>

<?php foreach($rows as $row): ?>

<?php
$dateValue = $row['sell_date'] ?? $row['purchase_date'];
$tradePrice = $row['sell_price'] ?? $row['purchase_price'];

$isSell = $row['sell_date'] !== null;
$pl = $row['profit_loss'];

if(!$isSell){
$rowClass='buy-row';
}elseif($pl>0){
$rowClass='sell-profit';
}else{
$rowClass='sell-loss';
}
?>

<tr class="<?= $rowClass ?>">
<td><?= $dateValue ?></td>
<td><?= htmlspecialchars($row['symbol']) ?></td>
<td><?= number_format($row['shares'],8) ?></td>
<td><?= number_format($tradePrice,2) ?></td>
<td><?= $pl!==null?number_format($pl,2):'' ?></td>
</tr>

<?php endforeach; ?>

</table>

<div style="position: relative; margin-top: 20px;">
    <div style="display: flex; justify-content: center;">
        <?php
        $base = "transaction-history.php?id=".$ID;
        renderPagination($base,$page,$totalPages);
        ?>
    </div>
    
    <div style="position: absolute; right: 0; top: 0; display: flex; gap: 15px; align-items: center;">
        <div style="display: flex; align-items: center; gap: 5px;">
            <div style="width: 15px; height: 15px; background-color: #6f42c1; border-radius: 2px;"></div>
            <span style="font-size: 0.75rem; font-weight: 500;">Purchase</span>
        </div>
        <div style="display: flex; align-items: center; gap: 5px;">
            <div style="width: 15px; height: 15px; background-color: #198754; border-radius: 2px;"></div>
            <span style="font-size: 0.75rem; font-weight: 500;">Sell (Profit)</span>
        </div>
        <div style="display: flex; align-items: center; gap: 5px;">
            <div style="width: 15px; height: 15px; background-color: #dc3545; border-radius: 2px;"></div>
            <span style="font-size: 0.75rem; font-weight: 500;">Sell (Loss)</span>
        </div>
    </div>
</div>

<?php else: ?>

<h5 class="text-danger mt-3">This user has no transaction history.</h5>

<?php endif; ?>

</div>

</body>
</html>