<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php"); exit;
}

$performans = $conn->query("
    SELECT k.id, k.ad, COUNT(a.id) AS masa_sayisi, IFNULL(SUM(a.toplam_tutar),0) AS toplam_satis
    FROM kullanicilar k
    LEFT JOIN adisyonlar a ON k.id = a.garson_id AND DATE(a.acilis_tarihi) = CURDATE()
    WHERE k.rol = 'garson'
    GROUP BY k.id, k.ad
    ORDER BY toplam_satis DESC
");

$rank = 0;
$medals = ["🥇","🥈","🥉"];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Garson Performansı — Maça Kızı</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.perf-card {
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:var(--radius-lg);
  padding:24px;
  margin-bottom:12px;
  display:flex;
  align-items:center;
  gap:20px;
  transition:border-color 0.2s;
}
.perf-card:hover { border-color:var(--border2); }
.perf-medal { font-size:32px; flex-shrink:0; width:44px; text-align:center; }
.perf-rank  { font-size:20px; color:var(--muted); flex-shrink:0; width:32px; text-align:center; }
.perf-name  { font-family:'Playfair Display',serif; font-size:20px; font-weight:600; }
.perf-meta  { font-size:13px; color:var(--muted); margin-top:4px; }
.perf-amount { margin-left:auto; text-align:right; flex-shrink:0; }
.perf-amount .amount { font-family:'Playfair Display',serif; font-size:24px; color:var(--gold); }
.perf-amount .count  { font-size:12px; color:var(--muted); }
</style>
</head>
<body class="app">

<nav class="topnav">
  <div class="brand">
    <div class="brand-icon">♠</div>
    <div>
      <div class="brand-name">Maça Kızı</div>
      <div class="brand-sub">Garson Performansı</div>
    </div>
  </div>
  <div class="nav-right">
    <a class="btn-ghost" href="admin.php">← Admin</a>
  </div>
</nav>

<div class="page">
  <div class="page-header">
    <div>
      <div class="page-title">Bugünkü Performans</div>
      <div class="page-subtitle"><?php echo date("d F Y"); ?> · Sıralama</div>
    </div>
  </div>

  <?php while($p = $performans->fetch_assoc()):
    $rank++;
    $medal = $medals[$rank-1] ?? "";
  ?>
    <div class="perf-card">
      <?php if($medal): ?>
        <div class="perf-medal"><?php echo $medal; ?></div>
      <?php else: ?>
        <div class="perf-rank"><?php echo $rank; ?></div>
      <?php endif; ?>
      <div>
        <div class="perf-name"><?php echo $p["ad"]; ?></div>
        <div class="perf-meta"><?php echo $p["masa_sayisi"]; ?> masa</div>
      </div>
      <div class="perf-amount">
        <div class="amount"><?php echo number_format($p["toplam_satis"],2,',','.'); ?> ₺</div>
        <div class="count">toplam satış</div>
      </div>
    </div>
  <?php endwhile; ?>
</div>

</body>
</html>
