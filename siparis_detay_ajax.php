<?php
session_start();
require_once "db.php";
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    http_response_code(403);
    echo json_encode(["ok" => false, "message" => "Yetkisiz işlem"]);
    exit;
}

$masa_id = intval($_GET["masa_id"] ?? 0);

$masa = $conn->query("SELECT * FROM masalar WHERE id = $masa_id")->fetch_assoc();
if (!$masa || $masa["durum"] !== "dolu" || empty($masa["aktif_adisyon_id"])) {
    echo json_encode(["ok" => false, "message" => "Bu masa şu anda dolu değil."]);
    exit;
}

$adisyon_id = intval($masa["aktif_adisyon_id"]);
$adisyon = $conn->query("SELECT a.*, k.ad AS garson_adi FROM adisyonlar a LEFT JOIN kullanicilar k ON a.garson_id = k.id WHERE a.id = $adisyon_id")->fetch_assoc();

$urunler = [];
$urun_toplam = 0;
$q = $conn->query("SELECT urun_adi, adet, fiyat, (adet*fiyat) AS ara_toplam FROM adisyon_detaylari WHERE adisyon_id = $adisyon_id ORDER BY id ASC");
while($r = $q->fetch_assoc()){
    $r["adet"] = (int)$r["adet"];
    $r["fiyat"] = (float)$r["fiyat"];
    $r["ara_toplam"] = (float)$r["ara_toplam"];
    $urun_toplam += $r["ara_toplam"];
    $urunler[] = $r;
}

$ekler = [];
$ek_toplam = 0;
$e = $conn->query("SELECT aciklama, tutar FROM ek_ucretler WHERE adisyon_id = $adisyon_id ORDER BY id ASC");
while($r = $e->fetch_assoc()){
    $r["tutar"] = (float)$r["tutar"];
    $ek_toplam += $r["tutar"];
    $ekler[] = $r;
}

$genel_toplam = $urun_toplam + $ek_toplam;
$masa_no_yazi = 'MASA ' . str_pad((int)$masa["masa_no"], 2, "0", STR_PAD_LEFT);
$masa_no_int = (int)$masa["masa_no"];

function tl2($n){ return number_format((float)$n, 2, ',', '.') . ' ₺'; }

echo json_encode([
    "ok"           => true,
    "masa_no"      => $masa_no_yazi,
    "masa_no_int"  => $masa_no_int,
    "garson"       => $adisyon["garson_adi"] ?? "-",
    "acilis"       => date('d.m.Y H:i', strtotime($adisyon["acilis_tarihi"])),
    "urunler"      => $urunler,
    "ekler"        => $ekler,
    "urun_toplam"  => $urun_toplam,
    "ek_toplam"    => $ek_toplam,
    "genel_toplam" => $genel_toplam,
    "genel_toplam_yazi" => tl2($genel_toplam),
]);
