<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php");
    exit;
}

$masa_id = intval($_POST["masa_id"] ?? 0);
$adisyon_id = intval($_POST["adisyon_id"] ?? 0);
$odeme_yontemi = trim($_POST["odeme_yontemi"] ?? $_POST["odeme_tipi"] ?? "Nakit");
$izinli_odemeler = ["Nakit", "Kredi Kartı", "IBAN"];
if (!in_array($odeme_yontemi, $izinli_odemeler, true)) { $odeme_yontemi = "Nakit"; }
$odeme_yontemi_sql = $conn->real_escape_string($odeme_yontemi);

$kontrol = $conn->query("SELECT id, aktif_adisyon_id FROM masalar WHERE id=$masa_id AND durum='dolu'")->fetch_assoc();
if (!$kontrol || intval($kontrol["aktif_adisyon_id"]) !== $adisyon_id) {
    header("Location: admin.php");
    exit;
}

$urun_toplam = $conn->query("SELECT IFNULL(SUM(adet * fiyat),0) AS toplam FROM adisyon_detaylari WHERE adisyon_id = $adisyon_id")->fetch_assoc()["toplam"];
$ek_toplam = $conn->query("SELECT IFNULL(SUM(tutar),0) AS toplam FROM ek_ucretler WHERE adisyon_id = $adisyon_id")->fetch_assoc()["toplam"];
$genel_toplam = (float)$urun_toplam + (float)$ek_toplam;

$kolonKontrol = $conn->query("SHOW COLUMNS FROM adisyonlar LIKE 'odeme_yontemi'");
if ($kolonKontrol && $kolonKontrol->num_rows == 0) {
    $conn->query("ALTER TABLE adisyonlar ADD COLUMN odeme_yontemi VARCHAR(30) NULL AFTER toplam_tutar");
}

$conn->query("UPDATE adisyonlar SET toplam_tutar = $genel_toplam, odeme_yontemi = '$odeme_yontemi_sql', durum = 'kapali', kapanis_tarihi = NOW() WHERE id = $adisyon_id");
$conn->query("UPDATE masalar SET durum = 'bos', baslangic_saati = NULL, aktif_adisyon_id = NULL WHERE id = $masa_id");

header("Location: admin.php");
exit;
?>
