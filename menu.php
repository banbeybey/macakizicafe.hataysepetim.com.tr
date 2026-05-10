<?php
require_once "db.php";
$urunler = $conn->query("SELECT * FROM urunler WHERE aktif=1 ORDER BY kategori, urun_adi");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Menü — Maça Kızı</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.hero {
  text-align:center;
  padding:52px 24px 36px;
  background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(200,16,46,0.1) 0%, transparent 70%);
  border-bottom: 1px solid var(--border);
}
.hero-icon { font-size:52px; margin-bottom:12px; display:block; }
.hero-title { font-size:40px; margin-bottom:8px; }
.hero-sub { font-size:14px; color:var(--muted); letter-spacing:1px; text-transform:uppercase; }
.kategori-title {
  font-family:'Playfair Display',serif;
  font-size:22px;
  color:var(--gold);
  margin:32px 0 16px;
  display:flex; align-items:center; gap:12px;
}
.kategori-title::after { content:''; flex:1; height:1px; background:rgba(212,168,83,0.2); }
.menu-item {
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:16px 20px;
  margin-bottom:8px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:20px;
  transition: border-color 0.2s;
}
.menu-item:hover { border-color:var(--border2); }
.menu-item-name { font-size:15px; font-weight:500; }
.menu-item-price {
  font-family:'Playfair Display',serif;
  font-size:18px;
  color:var(--gold);
  white-space:nowrap;
}
</style>
</head>
<body>
<div class="hero">
  <span class="hero-icon">♠</span>
  <div class="hero-title">Maça Kızı</div>
  <div class="hero-sub">Cafe &amp; Oyun Salonu Menüsü</div>
</div>

<div class="page">
<?php
$son = "";
while($u = $urunler->fetch_assoc()):
  if($son != $u["kategori"]):
    $son = $u["kategori"];
?>
    <div class="kategori-title"><?php echo $son; ?></div>
<?php endif; ?>
  <div class="menu-item">
    <span class="menu-item-name"><?php echo $u["urun_adi"]; ?></span>
    <span class="menu-item-price"><?php echo number_format($u["fiyat"],2,',','.'); ?> ₺</span>
  </div>
<?php endwhile; ?>
</div>
</body>
</html>
