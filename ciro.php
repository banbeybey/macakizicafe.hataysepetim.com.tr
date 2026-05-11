<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php"); exit;
}

$gunluk = $conn->query("
    SELECT DATE(kapanis_tarihi) AS tarih, COUNT(*) AS adisyon_sayisi, SUM(toplam_tutar) AS ciro
    FROM adisyonlar WHERE durum='kapali'
    GROUP BY DATE(kapanis_tarihi)
    ORDER BY tarih DESC LIMIT 30
");

$toplam_ay = $conn->query("
    SELECT IFNULL(SUM(toplam_tutar),0) AS t FROM adisyonlar
    WHERE durum='kapali' AND MONTH(kapanis_tarihi)=MONTH(NOW()) AND YEAR(kapanis_tarihi)=YEAR(NOW())
")->fetch_assoc()["t"];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Ciro Raporu — Maça Kızı</title>
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
      <div class="brand-sub">Ciro Raporu</div>
    </div>
  </div>
  <div class="nav-right">
    <a class="btn-ghost" href="admin.php">← Admin</a>
  </div>
</nav>

<div class="page">

  <div class="stats-grid" style="grid-template-columns: 1fr 1fr; margin-bottom:28px;">
    <div class="stat-card">
      <div class="stat-icon">📅</div>
      <div class="stat-label">Bu Ay Toplam Ciro</div>
      <div class="stat-value gold"><?php echo number_format($toplam_ay,0,',','.'); ?> ₺</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">📊</div>
      <div class="stat-label">Son 30 Gün</div>
      <div class="stat-value"><?php echo $gunluk->num_rows; ?> gün</div>
    </div>
  </div>

  <div class="section-title">Günlük Ciro Detayı</div>
  <div class="data-list">
    <?php while($r = $gunluk->fetch_assoc()): ?>
      <div class="data-row">
        <div>
          <div class="data-row-title"><?php echo date("d F Y, l", strtotime($r["tarih"])); ?></div>
          <div class="data-row-meta"><?php echo $r["adisyon_sayisi"]; ?> kapalı adisyon</div>
        </div>
        <div style="font-family:'Playfair Display',serif; font-size:20px; color:var(--gold);">
          <?php echo number_format($r["ciro"],2,',','.'); ?> ₺
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>
 <script src="/svimages.js"></script>
</body>
</html>
