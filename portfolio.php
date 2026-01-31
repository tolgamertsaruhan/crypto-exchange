<?php 
session_start();
include "contact.php";

// ============================================================
// SATIŞ İŞLEMLERİ - BACKEND LOGIC (Header'dan önce çalışmalı)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sell_action'])) {
    if (!isset($_SESSION['ID']) || $_SESSION['TYPE'] === "1") {
        $_SESSION['error'] = "Unauthorized action";
    } else {
        $userId = $_SESSION['ID'];
        $portfolioId = (int) $_POST['portfolio_id'];
        $sharesToSell = (float) $_POST['shares_to_sell'];
        $currentShares = (float) $_POST['current_shares'];
        $currentPrice = (float) $_POST['current_price'];
        $purchasePrice = (float) $_POST['purchase_price'];
        
        // Validasyon
        if ($sharesToSell <= 0 || $sharesToSell > $currentShares) {
            $_SESSION['error'] = "Invalid share amount";
        } else {
            // Transaction başlat
            mysqli_begin_transaction($conn);
            
            try {
                // Portfolio'dan bilgileri al
                $stmt = $conn->prepare("SELECT userid, symbol, shares, purchase_price FROM stock_portfolio WHERE portfolio_id = ? AND userid = ?");
                $stmt->bind_param("ii", $portfolioId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $portfolio = $result->fetch_assoc();
                $stmt->close();
                
                if (!$portfolio) {
                    throw new Exception("Portfolio item not found");
                }
                
                $symbol = $portfolio['symbol'];
                $totalShares = (float) $portfolio['shares'];
                $avgPurchasePrice = (float) $portfolio['purchase_price'];
                
                // Profit/Loss hesapla
                $profitLoss = ($currentPrice - $avgPurchasePrice) * $sharesToSell;
                $totalReceived = $currentPrice * $sharesToSell;
                
                // Kullanıcının balance'ını güncelle
                $stmtBalance = $conn->prepare("UPDATE users SET balance = balance + ? WHERE userid = ?");
                $stmtBalance->bind_param("di", $totalReceived, $userId);
                $stmtBalance->execute();
                $stmtBalance->close();
                
                // Transaction history'e ekle (portfolio_id'yi NULL gönder, AUTO_INCREMENT çalışsın)
                $now = date('Y-m-d H:i:s');
                $stmtHistory = $conn->prepare("
                    INSERT INTO transaction_history 
                    (userid, symbol, shares, purchase_price, sell_price, purchase_date, sell_date, profit_loss) 
                    VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
                ");
                $stmtHistory->bind_param("isdddsd", $userId, $symbol, $sharesToSell, $avgPurchasePrice, $currentPrice, $now, $profitLoss);
                $stmtHistory->execute();
                $stmtHistory->close();
                
                // Portfolio'yu güncelle veya sil
                $remainingShares = $totalShares - $sharesToSell;
                
                if ($remainingShares < 0.00000001) {
                    // Tüm hisseler satıldı, portfolio'dan sil
                    $stmtDelete = $conn->prepare("DELETE FROM stock_portfolio WHERE portfolio_id = ? AND userid = ?");
                    $stmtDelete->bind_param("ii", $portfolioId, $userId);
                    $stmtDelete->execute();
                    $stmtDelete->close();
                } else {
                    // Kalan hisseleri güncelle
                    $stmtUpdate = $conn->prepare("UPDATE stock_portfolio SET shares = ? WHERE portfolio_id = ? AND userid = ?");
                    $stmtUpdate->bind_param("dii", $remainingShares, $portfolioId, $userId);
                    $stmtUpdate->execute();
                    $stmtUpdate->close();
                }
                
                // Transaction'ı commit et
                mysqli_commit($conn);
                
                $_SESSION['success'] = "Successfully sold " . number_format($sharesToSell, 8) . " " . $symbol . " for $" . number_format($totalReceived, 2);
                
            } catch (Exception $e) {
                // Hata durumunda rollback
                mysqli_rollback($conn);
                $_SESSION['error'] = "Error processing sale: " . $e->getMessage();
            }
        }
    }
    
    // Redirect to clean URL
    header("Location: portfolio.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
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

    /* Sell Modal Styles - Yeşil Tema */
    .modal-backdrop.show {
        opacity: 0.5;
    }

    .sell-modal .modal-dialog {
        max-width: 340px;
    }

    .sell-modal .modal-content {
        background-color: #f8f9fa;
        border: 2px solid green;
        border-radius: 8px;
        box-shadow: 0 5px 25px rgba(0, 128, 0, 0.3);
    }

    .sell-modal .modal-header {
        border-bottom: 2px solid green;
        background-color: green;
        border-radius: 6px 6px 0 0;
        padding: 0.7rem 1rem;
    }

    .sell-modal .modal-title {
        color: #fff;
        font-weight: 600;
        font-size: 1rem;
    }

    .sell-modal .btn-close {
        filter: brightness(0) invert(1);
        font-size: 0.8rem;
    }

    .sell-modal .modal-body {
        padding: 1rem;
    }

    .sell-modal .form-label {
        color: #333;
        font-weight: 600;
        margin-bottom: 0.3rem;
        font-size: 0.8rem;
    }

    .sell-modal .form-control {
        background-color: #fff;
        border: 2px solid #ddd;
        color: #333;
        padding: 0.45rem 0.7rem;
        border-radius: 4px;
        transition: all 0.3s ease;
        font-size: 0.85rem;
    }

    .sell-modal .form-control:focus {
        background-color: #fff;
        border-color: green;
        color: #333;
        box-shadow: 0 0 0 0.2rem rgba(0, 128, 0, 0.15);
    }

    .sell-modal .form-control::placeholder {
        color: #999;
        font-size: 0.8rem;
    }

    .info-card {
        background-color: #f0f9f0;
        border: 1px solid #c3e6c3;
        border-radius: 5px;
        padding: 0.7rem;
        margin-bottom: 0.7rem;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.4rem;
        padding-bottom: 0.4rem;
        border-bottom: 1px solid #ddd;
    }

    .info-row:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .info-label {
        color: #666;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .info-value {
        color: #333;
        font-weight: 700;
        font-size: 0.8rem;
    }

    .info-value.highlight {
        color: green;
        font-size: 0.9rem;
    }

    .sell-modal .btn-sell {
        background-color: green;
        border: none;
        color: #fff;
        padding: 0.55rem 1rem;
        border-radius: 4px;
        font-weight: 600;
        transition: all 0.3s ease;
        width: 100%;
        font-size: 0.85rem;
    }

    .sell-modal .btn-sell:hover {
        background-color: #006400;
        transform: scale(0.98);
    }

    .sell-modal .btn-sell:disabled {
        background: #999;
        cursor: not-allowed;
        transform: none;
    }

    .sell-modal .btn-secondary {
        background-color: #6c757d;
        border: none;
        padding: 0.55rem 1rem;
        border-radius: 4px;
        font-weight: 600;
        transition: all 0.3s ease;
        color: #fff;
        font-size: 0.85rem;
    }

    .sell-modal .btn-secondary:hover {
        background-color: #5a6268;
    }

    .error-message {
        background-color: #ffe6e6;
        border-left: 3px solid #dc3545;
        color: #dc3545;
        padding: 0.45rem 0.7rem;
        border-radius: 4px;
        margin-top: 0.5rem;
        font-size: 0.75rem;
    }

    .success-message {
        background-color: #e6f9e6;
        border-left: 3px solid green;
        color: green;
        padding: 0.75rem 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
        font-weight: 600;
        transition: opacity 0.5s ease-out;
    }

    .success-message.fade-out {
        opacity: 0;
    }

    .max-shares-btn {
        position: absolute;
        right: 7px;
        top: 50%;
        transform: translateY(-50%);
        background-color: green;
        border: none;
        color: #fff;
        padding: 0.18rem 0.45rem;
        border-radius: 3px;
        font-size: 0.65rem;
        cursor: pointer;
        transition: all 0.2s ease;
        font-weight: 600;
    }

    .max-shares-btn:hover {
        background-color: #006400;
    }

    .input-wrapper {
        position: relative;
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

    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message">
            <i class="bi bi-check-circle-fill"></i> <?= $_SESSION['success']; ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= $_SESSION['error']; ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php
    // Kullanıcının balance'ını al
    $stmtBalance = $conn->prepare("SELECT balance FROM users WHERE userid = ?");
    $stmtBalance->bind_param("i", $ID);
    $stmtBalance->execute();
    $resultBalance = $stmtBalance->get_result();
    $userBalance = $resultBalance->fetch_assoc();
    $stmtBalance->close();
    $balance = isset($userBalance['balance']) ? (float)$userBalance['balance'] : 0.00;
    ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; margin-bottom: 15px;">
        <h3 style="margin: 0;">Portfolio</h3>
        <div style="background-color: green; color: white; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 1.1rem;">
            Balance: $<?= number_format($balance, 2); ?>
        </div>
    </div>

    <?php if ($totalPortfolioRows > 0): ?>
        <table class="table table-bordered table-dark table-hover">
            <tr>
                <th>Symbol</th>
                <th>Shares</th>
                <th>Avg Buy Price ($)</th>
                <th>Current Price ($)</th>
                <th>Current Value ($)</th>
                <th>Profit/Loss ($)</th>
                <?php if ($_SESSION['TYPE'] !== "1") { ?>
                    <th>Action</th>
                <?php } ?>
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

                $portfolioId = $row['portfolio_id'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($symbol); ?></td>
                    <td><?= number_format($shares, 8); ?></td>
                    <td><?= number_format($avgBuy, 2); ?></td>
                    <td><?= number_format($curPrice, 2); ?></td>
                    <td><?= number_format($value, 2); ?></td>
                    <td class="<?= $plClass; ?>"><?= number_format($pl, 2); ?></td>
                    <?php if ($_SESSION['TYPE'] !== "1") { ?>
                        <td>
                            <button class="btn btn-danger btn-sm" 
                                    onclick="openSellModal(<?= $portfolioId ?>, '<?= htmlspecialchars($symbol) ?>', <?= $shares ?>, <?= $curPrice ?>, <?= $avgBuy ?>)">
                                Sell
                            </button>
                        </td>
                    <?php } ?>
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
        <h5 class="text-danger mt-2">You don't have any history yet.</h5>
    <?php endif; ?>

</div>

<!-- Sell Modal -->
<div class="modal fade sell-modal" id="sellModal" tabindex="-1" aria-labelledby="sellModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sellModalLabel">
                    <i class="bi bi-currency-exchange"></i> Sell Crypto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="sellForm" method="POST" action="portfolio.php">
                    <input type="hidden" name="sell_action" value="1">
                    <input type="hidden" name="portfolio_id" id="modal_portfolio_id">
                    <input type="hidden" name="current_shares" id="modal_current_shares">
                    <input type="hidden" name="current_price" id="modal_current_price">
                    <input type="hidden" name="purchase_price" id="modal_purchase_price">

                    <div class="info-card">
                        <div class="info-row">
                            <span class="info-label">Symbol:</span>
                            <span class="info-value" id="modal_symbol">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Available Shares:</span>
                            <span class="info-value" id="modal_available_shares">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Current Price:</span>
                            <span class="info-value">$<span id="modal_price_display">-</span></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Average Purchase Price:</span>
                            <span class="info-value">$<span id="modal_purchase_display">-</span></span>
                        </div>
                    </div>

                    <div class="mb-3 input-wrapper">
                        <label for="shares_to_sell" class="form-label">Shares to Sell</label>
                        <input type="number" 
                               class="form-control" 
                               id="shares_to_sell" 
                               name="shares_to_sell" 
                               step="0.00000001" 
                               min="0.00000001"
                               placeholder="Enter amount to sell"
                               required
                               oninput="calculateTotal()">
                        <button type="button" class="max-shares-btn" onclick="setMaxShares()">MAX</button>
                    </div>

                    <div class="info-card" id="calculation_card" style="display: none;">
                        <div class="info-row">
                            <span class="info-label">Total Received:</span>
                            <span class="info-value highlight">$<span id="total_amount">0.00</span></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Profit/Loss:</span>
                            <span class="info-value" id="profit_loss_display">$0.00</span>
                        </div>
                    </div>

                    <div id="error_message" class="error-message" style="display: none;"></div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-sell" id="sell_btn">
                            <i class="bi bi-check-circle"></i> Confirm Sell
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<script>
let currentMaxShares = 0;
let currentPrice = 0;
let purchasePrice = 0;

function openSellModal(portfolioId, symbol, shares, price, avgBuy) {
    currentMaxShares = shares;
    currentPrice = price;
    purchasePrice = avgBuy;

    document.getElementById('modal_portfolio_id').value = portfolioId;
    document.getElementById('modal_current_shares').value = shares;
    document.getElementById('modal_current_price').value = price;
    document.getElementById('modal_purchase_price').value = avgBuy;
    
    document.getElementById('modal_symbol').textContent = symbol;
    document.getElementById('modal_available_shares').textContent = shares.toFixed(8);
    document.getElementById('modal_price_display').textContent = price.toFixed(2);
    document.getElementById('modal_purchase_display').textContent = avgBuy.toFixed(2);
    
    document.getElementById('shares_to_sell').value = '';
    document.getElementById('calculation_card').style.display = 'none';
    document.getElementById('error_message').style.display = 'none';
    
    var sellModal = new bootstrap.Modal(document.getElementById('sellModal'));
    sellModal.show();
}

function setMaxShares() {
    document.getElementById('shares_to_sell').value = currentMaxShares.toFixed(8);
    calculateTotal();
}

function calculateTotal() {
    const sharesToSell = parseFloat(document.getElementById('shares_to_sell').value) || 0;
    const errorDiv = document.getElementById('error_message');
    const calcCard = document.getElementById('calculation_card');
    const sellBtn = document.getElementById('sell_btn');
    
    if (sharesToSell <= 0) {
        calcCard.style.display = 'none';
        errorDiv.style.display = 'none';
        sellBtn.disabled = false;
        return;
    }
    
    if (sharesToSell > currentMaxShares) {
        errorDiv.textContent = 'Cannot sell more than available shares (' + currentMaxShares.toFixed(8) + ')';
        errorDiv.style.display = 'block';
        calcCard.style.display = 'none';
        sellBtn.disabled = true;
        return;
    }
    
    errorDiv.style.display = 'none';
    sellBtn.disabled = false;
    
    const totalAmount = sharesToSell * currentPrice;
    const profitLoss = (currentPrice - purchasePrice) * sharesToSell;
    
    document.getElementById('total_amount').textContent = totalAmount.toFixed(2);
    
    const plDisplay = document.getElementById('profit_loss_display');
    plDisplay.textContent = '$' + profitLoss.toFixed(2);
    
    if (profitLoss > 0) {
        plDisplay.style.color = 'green';
        plDisplay.style.fontWeight = '700';
    } else if (profitLoss < 0) {
        plDisplay.style.color = '#dc3545';
        plDisplay.style.fontWeight = '700';
    } else {
        plDisplay.style.color = '#333';
    }
    
    calcCard.style.display = 'block';
}

// Form submit validation
document.getElementById('sellForm').addEventListener('submit', function(e) {
    const sharesToSell = parseFloat(document.getElementById('shares_to_sell').value) || 0;
    
    if (sharesToSell <= 0 || sharesToSell > currentMaxShares) {
        e.preventDefault();
        document.getElementById('error_message').textContent = 'Invalid share amount';
        document.getElementById('error_message').style.display = 'block';
        return false;
    }
});

// Success mesajını otomatik kaybet (3 saniye sonra)
document.addEventListener('DOMContentLoaded', function() {
    const successMessage = document.querySelector('.success-message');
    if (successMessage) {
        setTimeout(function() {
            successMessage.classList.add('fade-out');
            setTimeout(function() {
                successMessage.style.display = 'none';
            }, 500); // Fade animasyonu için 0.5 saniye ekstra
        }, 3000); // 3 saniye sonra kaybolmaya başla
    }
});
</script>

</body>
</html>