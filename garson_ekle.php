<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php"); exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad           = $conn->real_escape_string($_POST["ad"]);
    $kullanici_adi = $conn->real_escape_string($_POST["kullanici_adi"]);
    $sifre        = $conn->real_escape_string($_POST["sifre"]);
    $conn->query("INSERT INTO kullanicilar (ad, kullanici_adi, sifre, rol, aktif) VALUES ('$ad', '$kullanici_adi', '$sifre', 'garson', 1)");
    header("Location: garson_ekle.php"); exit;
}

$garsonlar = $conn->query("SELECT * FROM kullanicilar WHERE rol='garson' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Garson Yönetimi — Maça Kızı</title>
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
      <div class="brand-sub">Garson Yönetimi</div>
    </div>
  </div>
  <div class="nav-right">
    <a class="btn-ghost" href="admin.php">← Admin</a>
  </div>
</nav>

<div class="page">
  <div class="two-col">
    <div>
      <div class="section-title">Yeni Garson Ekle</div>
      <div class="card">
        <form method="POST">
          <div class="form-group">
            <label class="form-label">Ad Soyad</label>
            <input class="form-control" type="text" name="ad" placeholder="garson adı" required>
          </div>
          <div class="form-group">
            <label class="form-label">Kullanıcı Adı</label>
            <input class="form-control" type="text" name="kullanici_adi" placeholder="giriş için kullanıcı adı" required>
          </div>
          <div class="form-group">
            <label class="form-label">Şifre</label>
            <input class="form-control" type="text" name="sifre" placeholder="şifre" required>
          </div>
          <button class="btn btn-primary" type="submit">+ Garson Ekle</button>
        </form>
      </div>
    </div>

    <div>
      <div class="section-title">Garson Listesi</div>
      <div class="data-list">
        <?php while($g = $garsonlar->fetch_assoc()): ?>
          <div class="data-row">
            <div>
              <div class="data-row-title"><?php echo $g["ad"]; ?></div>
              <div class="data-row-meta">@<?php echo $g["kullanici_adi"]; ?></div>
            </div>
            <div style="display:flex; gap:8px; align-items:center; flex-shrink:0;">
              <span class="badge <?php echo $g["aktif"] ? 'badge-green' : 'badge-red'; ?>">
                <?php echo $g["aktif"] ? "Aktif" : "Pasif"; ?>
              </span>
              <?php if($g["aktif"]): ?>
                <a class="btn btn-danger btn-sm" href="garson_pasif.php?id=<?php echo $g["id"]; ?>">Pasif Yap</a>
              <?php else: ?>
                <a class="btn btn-success btn-sm" href="garson_aktif.php?id=<?php echo $g["id"]; ?>">Aktif Yap</a>
              <?php endif; ?>
              <a class="btn btn-outline btn-sm" href="garson_sil.php?id=<?php echo $g["id"]; ?>"
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
