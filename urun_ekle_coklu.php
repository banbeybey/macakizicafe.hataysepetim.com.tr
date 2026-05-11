<?php
session_start();
require_once "db.php";
require_once "push_send.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "garson") {
    header("Location: login.php"); exit;
}

$masa_id  = intval($_POST["masa_id"] ?? 0);
$urun_ids = $_POST["urun_ids"] ?? [];
$adetler  = $_POST["adetler"] ?? [];

if (!$masa_id || empty($urun_ids)) {
    header("Location: masa.php?id=$masa_id"); exit;
}

$masa = $conn->query("SELECT * FROM masalar WHERE id = $masa_id")->fetch_assoc();
if (!$masa) die("Masa bulunamadı.");

if ($masa["durum"] === "bos" || empty($masa["aktif_adisyon_id"])) {
    $garson_id = intval($_SESSION["user_id"]);
    $conn->query("INSERT INTO adisyonlar (masa_id, garson_id, durum, acilis_tarihi) VALUES ($masa_id, $garson_id, 'acik', NOW())");
    $adisyon_id = $conn->insert_id;
    $conn->query("UPDATE masalar SET durum='dolu', baslangic_saati=NOW(), aktif_adisyon_id=$adisyon_id WHERE id=$masa_id");
} else {
    $adisyon_id = intval($masa["aktif_adisyon_id"]);
}

$nargile_siparisler = [];

foreach ($urun_ids as $i => $urun_id) {
    $urun_id = intval($urun_id);
    $adet    = intval($adetler[$i] ?? 1);
    if ($adet < 1) continue;

    $urun = $conn->query("SELECT * FROM urunler WHERE id = $urun_id AND aktif = 1")->fetch_assoc();
    if (!$urun) continue;

    $urun_adi = $conn->real_escape_string($urun["urun_adi"]);
    $fiyat    = floatval($urun["fiyat"]);

    $conn->query("INSERT INTO adisyon_detaylari (adisyon_id, urun_id, urun_adi, adet, fiyat)
                  VALUES ($adisyon_id, $urun_id, '$urun_adi', $adet, $fiyat)");

    if ($urun["kategori"] === "Nargileler") {
        $nargile_siparisler[] = $urun["urun_adi"] . " x" . $adet;
    }
}

if (!empty($nargile_siparisler)) {
    nargile_bildirim_gonder($conn, $masa["masa_no"], $nargile_siparisler);
}

header("Location: masa.php?id=$masa_id");
exit;
