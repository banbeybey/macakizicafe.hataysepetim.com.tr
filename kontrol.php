<?php
require_once "db.php";

echo "<h2>Maça Kızı Sistem Kontrol</h2>";

$tablolar = [
    "kullanicilar",
    "masalar",
    "urunler",
    "adisyonlar",
    "adisyon_detaylari",
    "ek_ucretler"
];

foreach ($tablolar as $tablo) {
    $sonuc = $conn->query("SHOW TABLES LIKE '$tablo'");
    echo $tablo . ": " . ($sonuc->num_rows > 0 ? "VAR" : "YOK") . "<br>";
}

echo "<hr>";

$masa = $conn->query("SELECT COUNT(*) AS toplam FROM masalar")->fetch_assoc();
$urun = $conn->query("SELECT COUNT(*) AS toplam FROM urunler")->fetch_assoc();
$admin = $conn->query("SELECT COUNT(*) AS toplam FROM kullanicilar WHERE rol='admin'")->fetch_assoc();

echo "Masa sayısı: " . $masa["toplam"] . "<br>";
echo "Ürün sayısı: " . $urun["toplam"] . "<br>";
echo "Admin sayısı: " . $admin["toplam"] . "<br>";

echo "<hr>";
echo "Türkçe karakter testi: ÇĞİÖŞÜ çğıöşü";
?>