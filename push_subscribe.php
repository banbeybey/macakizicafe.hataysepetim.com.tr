<?php
require_once "db.php";
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['endpoint'])) {
    echo json_encode(['ok' => false, 'msg' => 'Veri eksik']);
    exit;
}

$endpoint = $conn->real_escape_string($data['endpoint']);
$p256dh   = $conn->real_escape_string($data['keys']['p256dh'] ?? '');
$auth     = $conn->real_escape_string($data['keys']['auth'] ?? '');

$conn->query("INSERT INTO push_subscriptions (endpoint, p256dh, auth)
              VALUES ('$endpoint', '$p256dh', '$auth')
              ON DUPLICATE KEY UPDATE p256dh='$p256dh', auth='$auth'");

echo json_encode(['ok' => true]);
