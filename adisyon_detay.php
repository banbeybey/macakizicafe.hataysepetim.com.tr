<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php"); exit;
}

$adisyon_id = intval($_GET["id"] ?? 0);
$adisyon = $conn->query("
    SELECT a.*, m.masa_no, k.ad AS garson_adi
    FROM adisyonlar a
    LEFT JOIN masalar m ON a.masa_id = m.id
    LEFT JOIN kullanicilar k ON a.garson_id = k.id
    WHERE a.id = $adisyon_id
")->fetch_assoc();

if (!$adisyon) die("Adisyon bulunamadı.");

$detaylar = $conn->query("SELECT * FROM adisyon_detaylari WHERE adisyon_id = $adisyon_id");
$ekler    = $conn->query("SELECT * FROM ek_ucretler WHERE adisyon_id = $adisyon_id");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Adisyon Detayı — Maça Kızı</title>
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
      <div class="brand-name">Adisyon #<?php echo $adisyon_id; ?></div>
      <div class="brand-sub">Masa <?php echo $adisyon["masa_no"]; ?></div>
    </div>
  </div>
  <div class="nav-right">
    <a class="btn-ghost" href="adisyon_gecmisi.php">← Geçmiş</a>
  </div>
</nav>

<div class="page">

  <!-- Summary -->
  <div class="total-display" style="margin-bottom:28px;">
    <div>
      <div class="total-label">Toplam Tutar</div>
      <div style="font-size:13px; color:var(--muted); margin-top:4px;">
        Garson: <?php echo $adisyon["garson_adi"]; ?> ·
        <span class="badge <?php echo $adisyon["durum"]=='kapali' ? 'badge-gray' : 'badge-green'; ?>">
          <?php echo $adisyon["durum"] == "kapali" ? "Kapalı" : "Açık"; ?>
        </span>
      </div>
    </div>
    <div class="total-amount"><?php echo number_format($adisyon["toplam_tutar"],2,',','.'); ?> ₺</div>
  </div>

  <!-- Info -->
  <div class="card-sm" style="margin-bottom:20px; display:flex; gap:24px; flex-wrap:wrap;">
    <div>
      <div class="form-label">Açılış</div>
      <div><?php echo date("d.m.Y H:i", strtotime($adisyon["acilis_tarihi"])); ?></div>
    </div>
    <div>
      <div class="form-label">Kapanış</div>
      <div><?php echo $adisyon["kapanis_tarihi"] ? date("d.m.Y H:i", strtotime($adisyon["kapanis_tarihi"])) : "—"; ?></div>
    </div>
    <div>
      <div class="form-label">Ödeme Yöntemi</div>
      <div><?php echo htmlspecialchars($adisyon["odeme_yontemi"] ?? "—"); ?></div>
    </div>
  </div>

  <!-- Ürünler -->
  <div class="section-title">Siparişler</div>
  <?php while($d = $detaylar->fetch_assoc()): ?>
    <div class="item-row">
      <div>
        <div class="item-row-name"><?php echo $d["urun_adi"]; ?></div>
        <div class="item-row-meta"><?php echo $d["adet"]; ?> adet × <?php echo number_format($d["fiyat"],2); ?> ₺</div>
      </div>
      <div class="item-row-price"><?php echo number_format($d["adet"]*$d["fiyat"],2); ?> ₺</div>
    </div>
  <?php endwhile; ?>

  <!-- Ek Ücretler -->
  <?php
  $ek_arr = [];
  while($e = $ekler->fetch_assoc()) $ek_arr[] = $e;
  if(count($ek_arr) > 0):
  ?>
    <div class="section-title" style="margin-top:24px;">Ek Ücretler</div>
    <?php foreach($ek_arr as $e): ?>
      <div class="item-row">
        <div class="item-row-name"><?php echo $e["aciklama"]; ?></div>
        <div class="item-row-price"><?php echo number_format($e["tutar"],2); ?> ₺</div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>
</body>
</html>
