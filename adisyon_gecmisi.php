<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php"); exit;
}

$adisyonlar = $conn->query("
    SELECT a.*, m.masa_no, k.ad AS garson_adi
    FROM adisyonlar a
    LEFT JOIN masalar m ON a.masa_id = m.id
    LEFT JOIN kullanicilar k ON a.garson_id = k.id
    ORDER BY a.id DESC LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Adisyon Geçmişi — Maça Kızı</title>
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
      <div class="brand-sub">Adisyon Geçmişi</div>
    </div>
  </div>
  <div class="nav-right">
    <a class="btn-ghost" href="admin.php">← Admin</a>
  </div>
</nav>

<div class="page">
  <div class="data-list">
    <?php while($a = $adisyonlar->fetch_assoc()): ?>
      <div class="data-row">
        <div>
          <div class="data-row-title">Masa <?php echo $a["masa_no"]; ?> · <?php echo $a["garson_adi"]; ?></div>
          <div class="data-row-meta">
            Açılış: <?php echo date("d.m.Y H:i", strtotime($a["acilis_tarihi"])); ?>
            <?php if($a["kapanis_tarihi"]): ?>
              · Kapanış: <?php echo date("d.m.Y H:i", strtotime($a["kapanis_tarihi"])); ?>
            <?php endif; ?>
            · Ödeme: <strong><?php echo htmlspecialchars($a["odeme_yontemi"] ?? "—"); ?></strong>
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:12px; flex-shrink:0;">
          <span class="badge <?php echo $a["durum"] == 'kapali' ? 'badge-gray' : 'badge-green'; ?>">
            <?php echo $a["durum"] == "kapali" ? "Kapalı" : "Açık"; ?>
          </span>
          <span style="font-family:'Playfair Display',serif; font-size:18px; color:var(--gold);">
            <?php echo number_format($a["toplam_tutar"],2,',','.'); ?> ₺
          </span>
          <a class="btn btn-outline btn-sm" href="adisyon_detay.php?id=<?php echo $a["id"]; ?>">Detay</a>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

</body>
</html>
