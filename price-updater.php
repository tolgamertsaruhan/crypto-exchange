<?php
// price-updater.php
// Login sonrası: DB'de olmayan coinleri API'den çekip yazar

function updateCryptoPrices(mysqli $conn): void
{
    date_default_timezone_set("Europe/Istanbul");

    // Symbol => CoinGecko ID (100 coin)
    $coins = [
        // İlk 10 (mevcut)
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
        
        // Yeni 90 coin
        "LINK" => "chainlink",
        "MATIC" => "matic-network",
        "UNI" => "uniswap",
        "LTC" => "litecoin",
        "ATOM" => "cosmos",
        "XLM" => "stellar",
        "ALGO" => "algorand",
        "VET" => "vechain",
        "ICP" => "internet-computer",
        "FIL" => "filecoin",
        
        "HBAR" => "hedera-hashgraph",
        "APT" => "aptos",
        "QNT" => "quant-network",
        "NEAR" => "near",
        "GRT" => "the-graph",
        "SAND" => "the-sandbox",
        "MANA" => "decentraland",
        "AXS" => "axie-infinity",
        "THETA" => "theta-token",
        "RUNE" => "thorchain",
        
        "EOS" => "eos",
        "AAVE" => "aave",
        "MKR" => "maker",
        "SNX" => "havven",
        "COMP" => "compound-governance-token",
        "CRV" => "curve-dao-token",
        "YFI" => "yearn-finance",
        "SUSHI" => "sushi",
        "BAL" => "balancer",
        "REN" => "republic-protocol",
        
        "ZRX" => "0x",
        "OMG" => "omisego",
        "KNC" => "kyber-network-crystal",
        "LRC" => "loopring",
        "BAND" => "band-protocol",
        "ANKR" => "ankr",
        "CHZ" => "chiliz",
        "ENJ" => "enjincoin",
        "BAT" => "basic-attention-token",
        "ZIL" => "zilliqa",
        
        "CELO" => "celo",
        "WAVES" => "waves",
        "ICX" => "icon",
        "ONT" => "ontology",
        "ZEC" => "zcash",
        "DASH" => "dash",
        "XTZ" => "tezos",
        "ETC" => "ethereum-classic",
        "NEO" => "neo",
        "QTUM" => "qtum",
        
        "DCR" => "decred",
        "LSK" => "lisk",
        "SC" => "siacoin",
        "DGB" => "digibyte",
        "RVN" => "ravencoin",
        "BTG" => "bitcoin-gold",
        "STEEM" => "steem",
        "STRAT" => "stratis",
        "ARK" => "ark",
        "KMD" => "komodo",
        
        "NXT" => "nxt",
        "XEM" => "nem",
        "ARDR" => "ardor",
        "BURST" => "burst",
        "SYS" => "syscoin",
        "VIA" => "viacoin",
        "BLOCK" => "blocknet",
        "NAV" => "nav-coin",
        "PIVX" => "pivx",
        "POT" => "potcoin",
        
        "MONA" => "monacoin",
        "NMC" => "namecoin",
        "PPC" => "peercoin",
        "RDD" => "reddcoin",
        "VTC" => "vertcoin",
        "BLK" => "blackcoin",
        "BAY" => "bitbay",
        "CLOAK" => "cloakcoin",
        "PINK" => "pinkcoin",
        "XMY" => "myriad",
        
        "EMC2" => "einsteinium",
        "FAIR" => "faircoin",
        "START" => "startcoin",
        "KORE" => "kore",
        "XST" => "stealth",
        "IOC" => "iocoin",
        "SWIFT" => "swiftcash",
        "DMD" => "diamond",
        "GRS" => "groestlcoin",
        "MLN" => "melon",
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
     * 3️⃣ API rate limit için batch'lere böl (CoinGecko max ~250 coin/request)
     * Güvenli olması için 50'şer coin çekelim
     */
    $batches = array_chunk($missing, 50, true);

    $insert = $conn->prepare("
        INSERT INTO stocks (symbol, price_date, price)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE price = VALUES(price)
    ");
    if (!$insert) return;

    foreach ($batches as $batch) {
        $ids = [];
        foreach ($batch as $symbol) {
            $ids[] = $coins[$symbol];
        }

        $url = "https://api.coingecko.com/api/v3/simple/price?ids=" .
               implode(",", $ids) . "&vs_currencies=usd";

        $ctx = stream_context_create([
            "http" => [
                "timeout" => 10,
                "method"  => "GET",
                "header"  => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n" .
                            "Accept: application/json\r\n"
            ],
            "ssl"  => [
                "verify_peer" => true,
                "verify_peer_name" => true
            ]
        ]);

        $json = @file_get_contents($url, false, $ctx);
        if ($json === false) continue; // Bu batch başarısız, diğerine geç

        $data = json_decode($json, true);
        if (!is_array($data)) continue;

        /**
         * 4️⃣ Insert / Update
         */
        foreach ($batch as $symbol) {
            $id = $coins[$symbol];
            if (!isset($data[$id]['usd'])) continue;

            $price = (float)$data[$id]['usd'];
            $insert->bind_param("ssd", $symbol, $today, $price);
            $insert->execute();
        }

        // Rate limit koruması: batch'ler arası kısa bekleme
        if (count($batches) > 1) {
            sleep(1); // 1 saniye bekle
        }
    }

    $insert->close();
}