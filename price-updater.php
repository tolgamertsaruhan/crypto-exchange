<?php
// price-updater.php
// Login sonrası: DB'de olmayan coinleri API'den çekip yazar

function updateCryptoPrices(mysqli $conn): void
{
    date_default_timezone_set("Europe/Istanbul");

    // Symbol => CoinGecko ID
    $coins = [
        "BTC"  => "bitcoin",
        "ETH"  => "ethereum",
        "BNB"  => "binancecoin",
        "SOL"  => "solana",
        "XRP"  => "ripple",
        "ADA"  => "cardano",
        "DOGE" => "dogecoin",
        "TON"  => "the-open-network",
        "DOT"  => "polkadot",
        "AVAX" => "avalanche-2",
    ];

    $today = date("Y-m-d");

    /**
     * 1️⃣ Bugün DB'de olan symbol'leri al
     */
    $stmt = $conn->prepare(
        "SELECT symbol FROM stocks WHERE price_date = ?"
    );
    if (!$stmt) return;

    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();

    $existing = [];
    while ($row = $result->fetch_assoc()) {
        $existing[] = $row['symbol'];
    }
    $stmt->close();

    /**
     * 2️⃣ Eksik coinleri bul
     */
    $missing = array_diff(array_keys($coins), $existing);
    if (empty($missing)) {
        // Hepsi DB'de → API çağırma
        return;
    }

    /**
     * 3️⃣ SADECE eksik coinler için API çağrısı
     */
    $ids = [];
    foreach ($missing as $symbol) {
        $ids[] = $coins[$symbol];
    }

    $url = "https://api.coingecko.com/api/v3/simple/price?ids=" .
           implode(",", $ids) . "&vs_currencies=usd";

    $ctx = stream_context_create([
        "http" => ["timeout" => 10, "method"  => "GET", "header"  => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n" .
                                                                    "Accept: application/json\r\n"],
        "ssl"  => ["verify_peer" => true, "verify_peer_name" => true]
    ]);

    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) return;

    $data = json_decode($json, true);
    if (!is_array($data)) return;

    /**
     * 4️⃣ Insert / Update
     */
    $insert = $conn->prepare("
        INSERT INTO stocks (symbol, price_date, price)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE price = VALUES(price)
    ");
    if (!$insert) return;

    foreach ($missing as $symbol) {
        $id = $coins[$symbol];
        if (!isset($data[$id]['usd'])) continue;

        $price = (float)$data[$id]['usd'];
        $insert->bind_param("ssd", $symbol, $today, $price);
        $insert->execute();
    }

    $insert->close();
}
