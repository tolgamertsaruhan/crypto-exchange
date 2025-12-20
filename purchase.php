<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <?php include "contact.php"; ?>
    <title>Purchase</title>
    <style>
        /* Küçük compact dokunuş: alert gelince taşmasın */
        .compact-card .card-body { padding: 0.9rem; }
        .compact-card h5 { margin-bottom: .6rem; }
        .tight-line { margin-bottom: .35rem; }
        .mini-muted { color: #9aa0a6; font-size: .875rem; }
        .mini-tip { font-size: .85rem; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <?php
    $symbol = $_GET["Symbol"] ?? "";
    $symbol = strtoupper(trim($symbol));

    // Current price (latest)
    $currentPrice = null;
    if ($symbol !== "") {
        $stmt = $conn->prepare("SELECT price, price_date FROM stocks WHERE symbol = ? ORDER BY price_date DESC LIMIT 1");
        $stmt->bind_param("s", $symbol);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if ($row) $currentPrice = (float)$row["price"];
        $stmt->close();
    }

    // User balance
    $userBalance = 0.0;
    if (isset($_SESSION["ID"])) {
        $uid = (int)$_SESSION["ID"];
        $stmt = $conn->prepare("SELECT balance FROM users WHERE userid = ? LIMIT 1");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $urow = $res->fetch_assoc();
        if ($urow && $urow["balance"] !== null) $userBalance = (float)$urow["balance"];
        $stmt->close();
    }

    // Owned shares + avg cost
    $ownedShares = 0.0;
    $avgCost = 0.0;

    if (isset($_SESSION["ID"]) && $symbol !== "") {
        $uid = (int)$_SESSION["ID"];
        $stmt = $conn->prepare("SELECT shares, purchase_price FROM stock_portfolio WHERE userid = ? AND symbol = ? LIMIT 1");
        $stmt->bind_param("is", $uid, $symbol);
        $stmt->execute();
        $res = $stmt->get_result();
        $prow = $res->fetch_assoc();
        if ($prow) {
            $ownedShares = (float)$prow["shares"];
            $avgCost = (float)$prow["purchase_price"];
        }
        $stmt->close();
    }

    // Form values (server-side initial)
    $qtyInput = $_POST["quantity"] ?? "";
    $qty = 0.0;
    if ($qtyInput !== "" && is_numeric($qtyInput)) $qty = (float)$qtyInput;

    $totalUsd = 0.0;
    if ($currentPrice !== null && $qty > 0) $totalUsd = $qty * $currentPrice;

    // Owned totals (preview)
    $ownedTotalCost = 0.0;   // ownedShares * avgCost
    $ownedTotalValue = 0.0;  // ownedShares * currentPrice

    if ($ownedShares > 0 && $avgCost > 0) {
        $ownedTotalCost = $ownedShares * $avgCost;
    }
    if ($ownedShares > 0 && $currentPrice !== null) {
        $ownedTotalValue = $ownedShares * $currentPrice;
    }

    // P&L (unrealized) for owned
    $unrealPnl = null;
    $unrealPnlPct = null;
    if ($ownedShares > 0 && $avgCost > 0 && $currentPrice !== null) {
        $unrealPnl = ($currentPrice - $avgCost) * $ownedShares;
        $unrealPnlPct = (($currentPrice - $avgCost) / $avgCost) * 100.0;
    }

    $msg = "";
    $msgType = "danger";

    // BUY
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["buy"])) {
        if (!isset($_SESSION["ID"])) {
            $msg = "You must be logged in.";
        } elseif ($symbol === "" || $currentPrice === null) {
            $msg = "Coin price is not available.";
        } elseif (!is_numeric($qtyInput) || (float)$qtyInput <= 0) {
            $msg = "Please enter a valid quantity.";
        } else {
            $qtyBuy = (float)$qtyInput;
            $totalBuy = $qtyBuy * $currentPrice;

            if ($totalBuy <= 0) {
                $msg = "Total must be greater than 0.";
            } elseif ($userBalance < $totalBuy) {
                $msg = "Insufficient balance.";
            } else {
                $uid = (int)$_SESSION["ID"];
                $conn->begin_transaction();
                try {
                    // update balance
                    $newBalance = $userBalance - $totalBuy;
                    $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE userid = ?");
                    $stmt->bind_param("di", $newBalance, $uid);
                    $stmt->execute();
                    $stmt->close();

                    // ✅ transaction_history insert (BUY)
                    $purchaseDate = date("Y-m-d H:i:s");
                    $stmt = $conn->prepare("
                        INSERT INTO transaction_history (userid, symbol, shares, purchase_price, purchase_date)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("isdds", $uid, $symbol, $qtyBuy, $currentPrice, $purchaseDate);
                    $stmt->execute();
                    $stmt->close();

                    // portfolio upsert (weighted average)
                    $stmt = $conn->prepare("SELECT shares, purchase_price FROM stock_portfolio WHERE userid = ? AND symbol = ? LIMIT 1");
                    $stmt->bind_param("is", $uid, $symbol);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $existing = $res->fetch_assoc();
                    $stmt->close();

                    if ($existing) {
                        $oldShares = (float)$existing["shares"];
                        $oldAvg = (float)$existing["purchase_price"];
                        $newShares = $oldShares + $qtyBuy;
                        $newAvg = ($newShares > 0)
                            ? (($oldShares * $oldAvg) + ($qtyBuy * $currentPrice)) / $newShares
                            : $currentPrice;

                        $stmt = $conn->prepare("UPDATE stock_portfolio SET shares = ?, purchase_price = ? WHERE userid = ? AND symbol = ?");
                        $stmt->bind_param("ddis", $newShares, $newAvg, $uid, $symbol);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        $stmt = $conn->prepare("INSERT INTO stock_portfolio (userid, symbol, shares, purchase_price) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("isdd", $uid, $symbol, $qtyBuy, $currentPrice);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $conn->commit();
                    header("Location: purchase.php?Symbol=" . urlencode($symbol) . "&success=1");
                    exit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $msg = "An error occurred during purchase.";
                }
            }
        }
    }

    if (isset($_GET["success"]) && $_GET["success"] == "1") {
        $msgType = "success";
        $msg = "Purchase completed successfully.";

        // refresh balance/portfolio
        if (isset($_SESSION["ID"])) {
            $uid = (int)$_SESSION["ID"];

            $stmt = $conn->prepare("SELECT balance FROM users WHERE userid = ? LIMIT 1");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $res = $stmt->get_result();
            $urow = $res->fetch_assoc();
            if ($urow && $urow["balance"] !== null) $userBalance = (float)$urow["balance"];
            $stmt->close();

            $stmt = $conn->prepare("SELECT shares, purchase_price FROM stock_portfolio WHERE userid = ? AND symbol = ? LIMIT 1");
            $stmt->bind_param("is", $uid, $symbol);
            $stmt->execute();
            $res = $stmt->get_result();
            $prow = $res->fetch_assoc();
            if ($prow) {
                $ownedShares = (float)$prow["shares"];
                $avgCost = (float)$prow["purchase_price"];
            } else {
                $ownedShares = 0.0;
                $avgCost = 0.0;
            }
            $stmt->close();

            // recalc preview totals
            $ownedTotalCost = 0.0;
            $ownedTotalValue = 0.0;

            if ($ownedShares > 0 && $avgCost > 0) $ownedTotalCost = $ownedShares * $avgCost;
            if ($ownedShares > 0 && $currentPrice !== null) $ownedTotalValue = $ownedShares * $currentPrice;

            if ($ownedShares > 0 && $avgCost > 0 && $currentPrice !== null) {
                $unrealPnl = ($currentPrice - $avgCost) * $ownedShares;
                $unrealPnlPct = (($currentPrice - $avgCost) / $avgCost) * 100.0;
            } else {
                $unrealPnl = null;
                $unrealPnlPct = null;
            }
        }
    }

    function fmt8($v) {
        // 8 decimals, trailing zeros sil
        $s = number_format((float)$v, 8, ".", "");
        $s = rtrim(rtrim($s, "0"), ".");
        return $s === "" ? "0" : $s;
    }
    ?>

    <div class="container mt-3 mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="mb-1"><?= htmlspecialchars($symbol) ?> Purchase</h1>
                <?php if ($currentPrice !== null) { ?>
                    <div class="fs-5 tight-line">Current Price: <strong>$<?= number_format($currentPrice, 2) ?></strong></div>
                <?php } else { ?>
                    <div class="text-danger">Price not available.</div>
                <?php } ?>
            </div>

            <div class="text-end">
                <div class="mini-muted">Your Balance</div>
                <div class="fs-3 fw-bold text-success">$<?= number_format($userBalance, 2) ?></div>
            </div>
        </div>

        <?php if ($msg !== "") { ?>
            <div class="alert alert-<?= $msgType ?> mt-2 mb-2 py-2"><?= htmlspecialchars($msg) ?></div>
        <?php } ?>

        <div class="row mt-2 g-3">
            <!-- FORM -->
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm compact-card">
                    <div class="card-body">
                        <form method="post" action="purchase.php?Symbol=<?= urlencode($symbol) ?>">
                            <div class="mb-2">
                                <label for="qty" class="form-label fw-semibold mb-1">Quantity</label>

                                <input
                                    type="number"
                                    class="form-control form-control-lg"
                                    id="qty"
                                    name="quantity"
                                    value="<?= htmlspecialchars($qtyInput) ?>"
                                    step="any"
                                    min="0"
                                    inputmode="decimal"
                                    placeholder="e.g. 0.5"
                                    required
                                >

                                <div class="mini-tip text-muted mt-1">
                                    You can type small values like <strong>0.0002</strong>. Only numbers and dot are allowed.
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-semibold mb-1">Total (USD)</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="totalUsd"
                                    value="<?= number_format($totalUsd, 2) ?>"
                                    readonly
                                >
                            </div>

                            <div class="d-flex gap-2 mt-2">
                                <button type="submit" name="buy" class="btn btn-success px-4">Buy</button>
                                <a href="user-main.php" class="btn btn-danger px-4">Back</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- PREVIEW -->
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm compact-card">
                    <div class="card-body">
                        <h5 class="fw-bold">Preview</h5>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-muted">You currently hold</td>
                                        <td class="fw-semibold"><?= fmt8($ownedShares) ?> <?= htmlspecialchars($symbol) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Your average buy price</td>
                                        <td class="fw-semibold">$<?= number_format($avgCost, 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Latest market price</td>
                                        <td class="fw-semibold">$<?= $currentPrice !== null ? number_format($currentPrice, 2) : "0.00" ?></td>
                                    </tr>

                                    <tr>
                                        <td class="text-muted">Your total cost (holdings × avg cost)</td>
                                        <td class="fw-semibold">$<?= number_format($ownedTotalCost, 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Current value (holdings × latest price)</td>
                                        <td class="fw-semibold">$<?= number_format($ownedTotalValue, 2) ?></td>
                                    </tr>

                                    <tr>
                                        <td class="text-muted">Profit / Loss on your holdings</td>
                                        <td class="fw-semibold">
                                            <?php if ($unrealPnl === null) { ?>
                                                -
                                            <?php } else { ?>
                                                <span class="<?= $unrealPnl >= 0 ? "text-success" : "text-danger" ?>">
                                                    $<?= number_format($unrealPnl, 2) ?> (<?= number_format($unrealPnlPct, 2) ?>%)
                                                </span>
                                            <?php } ?>
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const qtyEl = document.getElementById("qty");
            const totalEl = document.getElementById("totalUsd");

            const currentPrice = <?= $currentPrice !== null ? json_encode((float)$currentPrice) : "null" ?>;

            function toNumberSafe(v) {
                if (v === null || v === undefined) return 0;
                const s = String(v).trim();
                if (s === "" || s === ".") return 0;
                const n = Number(s);
                return Number.isFinite(n) ? n : 0;
            }

            function updateLive() {
                const qtyValRaw = qtyEl.value;
                const qty = toNumberSafe(qtyValRaw);

                const total = (currentPrice !== null ? qty * currentPrice : 0);
                totalEl.value = (currentPrice !== null ? (Math.round((total + Number.EPSILON) * 100) / 100).toFixed(2) : "0.00");
            }

            // --- Only numbers + dot restriction ---
            qtyEl.addEventListener("keydown", function (e) {
                const allowedKeys = [
                    "Backspace", "Delete", "Tab", "Enter", "Escape",
                    "ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown",
                    "Home", "End"
                ];
                if (allowedKeys.includes(e.key)) return;

                if ((e.ctrlKey || e.metaKey) && ["a", "c", "v", "x"].includes(e.key.toLowerCase())) return;

                if (e.key >= "0" && e.key <= "9") return;

                if (e.key === ".") {
                    if (this.value.includes(".")) e.preventDefault();
                    return;
                }

                e.preventDefault();
            });

            qtyEl.addEventListener("paste", function (e) {
                const text = (e.clipboardData || window.clipboardData).getData("text");
                if (!text) return;
                if (!/^[0-9]*\.?[0-9]*$/.test(text.trim())) e.preventDefault();
            });

            qtyEl.addEventListener("input", updateLive);
            qtyEl.addEventListener("change", updateLive);

            updateLive();
        })();
    </script>

    <!- $_GET['purchaseID'] ile satın alınan coin id ulaşılabilir. ->
    <!- $_SESSION['ID'] ile satın alımı yapan kullanıcı id ulaşılabilir. Kullanıcının diğer değerlerine database üzerinden çekip ulaşmak yerine login de tanımlanan session lar ile de erişilebilir. ->
</body>
</html>
