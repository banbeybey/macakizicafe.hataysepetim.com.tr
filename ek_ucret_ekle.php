<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "garson") {
    header("Location: login.php");
    exit;
}

$masa_id  = intval($_POST["masa_id"]);
$aciklama = $conn->real_escape_string($_POST["aciklama"]);
$tutar    = floatval($_POST["tutar"]);

$masa = $conn->query("SELECT * FROM masalar WHERE id = $masa_id")->fetch_assoc();
if (!$masa) die("Masa bulunamadı.");

// Masa boşsa adisyonu şimdi oluştur
if ($masa["durum"] === "bos" || empty($masa["aktif_adisyon_id"])) {
    $garson_id = intval($_SESSION["user_id"]);
    $conn->query("INSERT INTO adisyonlar (masa_id, garson_id, durum, acilis_tarihi) VALUES ($masa_id, $garson_id, 'acik', NOW())");
    $adisyon_id = $conn->insert_id;
    $conn->query("UPDATE masalar SET durum='dolu', baslangic_saati=NOW(), aktif_adisyon_id=$adisyon_id WHERE id=$masa_id");
} else {
    $adisyon_id = intval($masa["aktif_adisyon_id"]);
}

$conn->query("
    INSERT INTO ek_ucretler (adisyon_id, aciklama, tutar)
    VALUES ($adisyon_id, '$aciklama', $tutar)
");

header("Location: masa.php?id=$masa_id");
exit;
