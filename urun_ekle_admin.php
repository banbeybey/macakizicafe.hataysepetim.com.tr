<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: admin_login.php"); exit;
}

$masa_id  = intval($_POST["masa_id"] ?? 0);
$urun_ids = $_POST["urun_ids"] ?? [];
$adetler  = $_POST["adetler"] ?? [];

if (!$masa_id || empty($urun_ids)) {
    header("Location: admin_masa.php?id=$masa_id"); exit;
}

$masa = $conn->query("SELECT * FROM masalar WHERE id = $masa_id")->fetch_assoc();
if (!$masa) die("Masa bulunamadı.");

// Masa boşsa yeni adisyon aç — admin kendisi açıyor, garson_id olarak admin'in id'sini ata
if ($masa["durum"] === "bos" || empty($masa["aktif_adisyon_id"])) {
    $garson_id = intval($_SESSION["user_id"]);
    $conn->query("INSERT INTO adisyonlar (masa_id, garson_id, durum, acilis_tarihi) VALUES ($masa_id, $garson_id, 'acik', NOW())");
    $adisyon_id = $conn->insert_id;
    $conn->query("UPDATE masalar SET durum='dolu', baslangic_saati=NOW(), aktif_adisyon_id=$adisyon_id WHERE id=$masa_id");
} else {
    $adisyon_id = intval($masa["aktif_adisyon_id"]);
}

// Her ürünü ekle
foreach ($urun_ids as $i => $urun_id) {
    $urun_id = intval($urun_id);
    $adet = intval($adetler[$i] ?? 1);
    if ($adet < 1) continue;
    $urun = $conn->query("SELECT * FROM urunler WHERE id = $urun_id AND aktif = 1")->fetch_assoc();
    if (!$urun) continue;
    $urun_adi = $conn->real_escape_string($urun["urun_adi"]);
    $fiyat    = floatval($urun["fiyat"]);
    $conn->query("INSERT INTO adisyon_detaylari (adisyon_id, urun_id, urun_adi, adet, fiyat)
                  VALUES ($adisyon_id, $urun_id, '$urun_adi', $adet, $fiyat)");
}

// Toplam tutarı güncelle
$toplam = $conn->query("SELECT IFNULL(SUM(adet*fiyat),0) AS t FROM adisyon_detaylari WHERE adisyon_id=$adisyon_id")->fetch_assoc()["t"];
$ekler  = $conn->query("SELECT IFNULL(SUM(tutar),0) AS t FROM ek_ucretler WHERE adisyon_id=$adisyon_id")->fetch_assoc()["t"];
$genel = $toplam + $ekler;
$conn->query("UPDATE adisyonlar SET toplam_tutar=$genel WHERE id=$adisyon_id");

header("Location: admin_masa.php?id=$masa_id&eklendi=1");
exit;
