<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php"); exit;
}

$id   = intval($_GET["id"] ?? 0);
$urun = $conn->query("SELECT * FROM urunler WHERE id=$id")->fetch_assoc();
if (!$urun) die("Ürün bulunamadı.");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kategori = $conn->real_escape_string($_POST["kategori"]);
    $urun_adi = $conn->real_escape_string($_POST["urun_adi"]);
    $fiyat    = floatval($_POST["fiyat"]);
    $aktif    = intval($_POST["aktif"]);
    $conn->query("UPDATE urunler SET kategori='$kategori', urun_adi='$urun_adi', fiyat=$fiyat, aktif=$aktif WHERE id=$id");
    header("Location: urun_yonetimi.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Ürün Düzenle — Maça Kızı</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="app">

<nav class="topnav">
  <div class="brand">
    <div class="brand-icon">♠</div>
    <div>
      <div class="brand-name">Maça Kızı</div>
      <div class="brand-sub">Ürün Düzenle</div>
    </div>
  </div>
  <div class="nav-right">
    <a class="btn-ghost" href="urun_yonetimi.php">← Ürünler</a>
  </div>
</nav>

<div class="page">
  <div style="max-width:480px; margin:0 auto;">
    <div class="page-title" style="margin-bottom:24px;">Ürünü Düzenle</div>
    <div class="card">
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Kategori</label>
          <input class="form-control" type="text" name="kategori" value="<?php echo htmlspecialchars($urun["kategori"]); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Ürün Adı</label>
          <input class="form-control" type="text" name="urun_adi" value="<?php echo htmlspecialchars($urun["urun_adi"]); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Fiyat (₺)</label>
          <input class="form-control" type="number" step="0.01" name="fiyat" value="<?php echo $urun["fiyat"]; ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Durum</label>
          <select class="form-control" name="aktif">
            <option value="1" <?php if($urun["aktif"]==1) echo "selected"; ?>>Aktif</option>
            <option value="0" <?php if($urun["aktif"]==0) echo "selected"; ?>>Pasif</option>
          </select>
        </div>
        <button class="btn btn-primary" type="submit">Kaydet</button>
      </form>
    </div>
  </div>
</div>

</body>
</html>
