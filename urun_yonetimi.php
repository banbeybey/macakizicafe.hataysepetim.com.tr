<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php"); exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kategori = $conn->real_escape_string($_POST["kategori"]);
    $urun_adi = $conn->real_escape_string($_POST["urun_adi"]);
    $fiyat    = floatval($_POST["fiyat"]);
    $conn->query("INSERT INTO urunler (kategori, urun_adi, fiyat, aktif) VALUES ('$kategori', '$urun_adi', $fiyat, 1)");
    header("Location: urun_yonetimi.php"); exit;
}

$urunler = $conn->query("SELECT * FROM urunler ORDER BY kategori, urun_adi");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Ürün Yönetimi — Maça Kızı</title>
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
      <div class="brand-sub">Ürün Yönetimi</div>
    </div>
  </div>
  <div class="nav-right">
    <a class="btn-ghost" href="admin.php">← Admin</a>
  </div>
</nav>

<div class="page">
  <div class="two-col">
    <div>
      <div class="section-title">Yeni Ürün Ekle</div>
      <div class="card">
        <form method="POST">
          <div class="form-group">
            <label class="form-label">Kategori</label>
            <input class="form-control" type="text" name="kategori" placeholder="örn. İçecekler" required>
          </div>
          <div class="form-group">
            <label class="form-label">Ürün Adı</label>
            <input class="form-control" type="text" name="urun_adi" placeholder="ürün adı" required>
          </div>
          <div class="form-group">
            <label class="form-label">Fiyat (₺)</label>
            <input class="form-control" type="number" step="0.01" name="fiyat" placeholder="0.00" required>
          </div>
          <button class="btn btn-primary" type="submit">+ Ürün Ekle</button>
        </form>
      </div>
    </div>

    <div>
      <div class="section-title">Ürün Listesi</div>
      <div class="data-list">
        <?php while($u = $urunler->fetch_assoc()): ?>
          <div class="data-row">
            <div>
              <div class="data-row-title"><?php echo $u["urun_adi"]; ?></div>
              <div class="data-row-meta">
                <?php echo $u["kategori"]; ?> ·
                <span style="color:var(--gold);"><?php echo number_format($u["fiyat"],2); ?> ₺</span>
              </div>
            </div>
            <div style="display:flex; gap:8px; align-items:center; flex-shrink:0;">
              <span class="badge <?php echo $u["aktif"] ? 'badge-green' : 'badge-gray'; ?>">
                <?php echo $u["aktif"] ? "Aktif" : "Pasif"; ?>
              </span>
              <a class="btn btn-outline btn-sm" href="urun_duzenle.php?id=<?php echo $u["id"]; ?>">Düzenle</a>
              <a class="btn btn-danger btn-sm" href="urun_sil.php?id=<?php echo $u["id"]; ?>"
                 onclick="return confirm('Silinsin mi?')">Sil</a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
</div>

</body>
</html>
