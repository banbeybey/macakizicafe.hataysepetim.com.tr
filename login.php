<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici_adi = $_POST["kullanici_adi"] ?? "";
    $sifre = $_POST["sifre"] ?? "";

    $stmt = $conn->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ? AND sifre = ? AND aktif = 1 LIMIT 1");
    $stmt->bind_param("ss", $kullanici_adi, $sifre);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["ad"] = $user["ad"];
        $_SESSION["rol"] = $user["rol"];
        header("Location: " . ($user["rol"] == "admin" ? "admin.php" : "garson.php"));
        exit;
    } else {
        $hata = "Kullanıcı adı veya şifre hatalı.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Maça Kızı — Giriş</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
body {
  display:flex; align-items:center; justify-content:center; min-height:100vh;
  background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(200,16,46,0.14) 0%, transparent 70%), var(--bg);
}
.login-wrap { width:92%; max-width:420px; position:relative; z-index:1; }
.login-logo { text-align:center; margin-bottom:32px; }
.logo-icon {
  width:72px; height:72px;
  background:linear-gradient(135deg, #7f0a1a, var(--accent2));
  border-radius:20px; display:flex; align-items:center; justify-content:center;
  font-size:34px; margin:0 auto 16px;
  box-shadow:0 8px 32px rgba(200,16,46,0.4), 0 0 0 1px rgba(255,255,255,0.06);
  animation: fadeIn 0.6s ease;
}
.logo-title { font-family:'Playfair Display',serif; font-size:30px; font-weight:700; }
.logo-sub { font-size:12px; color:var(--muted); margin-top:6px; text-transform:uppercase; letter-spacing:1.5px; }
.login-card {
  background:var(--surface); border:1px solid var(--border); border-radius:28px; padding:36px;
  box-shadow:0 32px 80px rgba(0,0,0,0.5);
  animation: slideUp 0.5s ease;
}
.login-card-title { font-size:14px; color:var(--muted); margin-bottom:24px; text-align:center; text-transform:uppercase; letter-spacing:1px; }
.footer-note { text-align:center; color:var(--muted); font-size:12px; margin-top:24px; opacity:0.5; }
body::after {
  content:'♠'; position:fixed; font-size:400px; color:var(--accent); opacity:0.025;
  top:50%; left:50%; transform:translate(-50%,-50%); pointer-events:none; z-index:0; line-height:1;
}
@keyframes fadeIn { from { opacity:0; transform:scale(0.8); } to { opacity:1; transform:scale(1); } }
@keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-logo">
    <div class="logo-icon">♠</div>
    <div class="logo-title">Maça Kızı</div>
    <div class="logo-sub">Cafe &amp; Oyun Salonu</div>
  </div>
  <div class="login-card">
    <div class="login-card-title">Yönetim Paneline Giriş</div>
    <?php if (!empty($hata)): ?>
      <div class="alert-error">⚠ <?php echo $hata; ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Kullanıcı Adı</label>
        <input class="form-control" type="text" name="kullanici_adi" placeholder="kullanıcı adınızı girin" required>
      </div>
      <div class="form-group">
        <label class="form-label">Şifre</label>
        <input class="form-control" type="password" name="sifre" placeholder="••••••••" required>
      </div>
      <button class="btn btn-primary" type="submit">Giriş Yap →</button>
    </form>
  </div>
  <div class="footer-note">Maça Kızı Adisyon Sistemi &copy; <?php echo date('Y'); ?></div>
</div>
</body>
</html>
