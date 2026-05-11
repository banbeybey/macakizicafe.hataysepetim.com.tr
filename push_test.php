<?php
require_once "db.php";
require_once "push_send.php";

echo "<pre style='font-family:monospace;font-size:14px;padding:16px'>";
echo "=== PUSH TEST DEBUG ===\n\n";

$subs = $conn->query("SELECT * FROM push_subscriptions");
$count = $subs->num_rows;
echo "Kayıtlı subscription sayısı: $count\n\n";

if ($count === 0) {
    echo "❌ Hiç subscription yok! nargileci.php'den tekrar kayıt ol.\n";
    echo "</pre>";
    exit;
}

while ($row = $subs->fetch_assoc()) {
    echo "--- Subscription ID: " . $row['id'] . " ---\n";
    echo "Endpoint: " . $row['endpoint'] . "\n";
    echo "Kayıt tarihi: " . $row['olusturma'] . "\n";

    $result = nargile_bildirim_gonder_debug(
        $conn,
        "TEST",
        ["Çift Elma x1", "Test Bildirimi"],
        $row
    );

    echo "HTTP Kodu : " . $result['http'] . "\n";
    echo "FCM Yanıtı: " . $result['response'] . "\n";

    $data = json_decode($result['response'], true);
    if (isset($data['name'])) {
        echo "✅ BAŞARILI! Message ID: " . $data['name'] . "\n";
    } elseif (isset($data['error'])) {
        echo "❌ HATA: " . $data['error']['message'] . "\n";
        echo "   Status: " . $data['error']['status'] . "\n";
    }
    echo "\n";
}

echo "=== TEST TAMAMLANDI ===\n";
echo "</pre>";
