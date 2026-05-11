<?php
session_start();
require_once "db.php";

if (isset($_SESSION["user_id"]) && $_SESSION["rol"] === "admin") {
    header("Location: admin.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici_adi = $_POST["kullanici_adi"] ?? "";
    $sifre         = $_POST["sifre"] ?? "";

    $stmt = $conn->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ? AND sifre = ? AND aktif = 1 AND rol = 'admin' LIMIT 1");
    $stmt->bind_param("ss", $kullanici_adi, $sifre);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["ad"]      = $user["ad"];
        $_SESSION["rol"]     = $user["rol"];
        header("Location: admin.php");
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
<title>Maça Kızı — Yönetici Girişi</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    font-family: 'DM Sans', sans-serif;
    background:
        radial-gradient(ellipse 70% 50% at 50% -10%, rgba(200,16,46,0.55) 0%, transparent 65%),
        radial-gradient(ellipse 100% 60% at 50% 100%, rgba(80,0,10,0.3) 0%, transparent 70%),
        #0a0a0a;
    position: relative;
    overflow: hidden;
    zoom: 0.9;
}

body::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
        radial-gradient(circle at 20% 80%, rgba(150,0,20,0.08) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(150,0,20,0.06) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
}

.login-wrap {
    width: 92%;
    max-width: 540px;
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* ── LOGO ── */
.modern-logo {
    width: 100%;
    padding: 28px 36px 22px;
    border-radius: 22px;
    position: relative;
    overflow: hidden;
    background:
        linear-gradient(180deg, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0.01) 100%),
        #0d0d0d;
    border: 1.5px solid rgba(220,30,50,0.55);
    box-shadow:
        0 0 0 1px rgba(255,255,255,0.04) inset,
        0 0 30px rgba(200,0,30,0.12),
        0 0 60px rgba(200,0,30,0.06);
    text-align: center;
    animation: slideUp 0.5s ease;
}

.modern-logo::before {
    content: '';
    position: absolute;
    top: -40%;
    left: 50%;
    transform: translateX(-50%);
    width: 80%;
    height: 80%;
    background: radial-gradient(ellipse, rgba(200,16,46,0.22) 0%, transparent 70%);
    pointer-events: none;
}

.modern-logo-inner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    position: relative;
    z-index: 1;
}

.logo-line {
    flex: 1;
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(200,16,46,0.8), transparent);
    border-radius: 999px;
    box-shadow: 0 0 10px rgba(200,16,46,0.6);
    max-width: 80px;
}

.logo-text {
    font-family: 'Bebas Neue', 'DM Sans', sans-serif;
    font-size: 72px;
    font-weight: 400;
    letter-spacing: 6px;
    color: #fff8f0;
    line-height: 1;
    text-shadow:
        0 0 30px rgba(255,255,255,0.15),
        0 2px 4px rgba(0,0,0,0.8);
    white-space: nowrap;
    position: relative;
    z-index: 1;
}

.logo-spade {
    display: inline-block;
    color: #ff1a2e;
    margin: 0 4px;
    font-size: 1.05em;
    vertical-align: middle;
    position: relative;
    top: -6px;
    text-shadow:
        0 0 4px rgba(255,255,255,0.4),
        0 0 8px #ff0020,
        0 0 16px rgba(255,0,32,0.5),
        0 0 30px rgba(255,0,32,0.3);
    filter:
        drop-shadow(0 0 3px rgba(255,0,30,0.6))
        drop-shadow(0 0 8px rgba(255,0,30,0.3));
    animation: spade-pulse 2s ease-in-out infinite;
}

@keyframes spade-pulse {
    0%, 100% {
        text-shadow:
            0 0 4px rgba(255,255,255,0.4),
            0 0 8px #ff0020,
            0 0 16px rgba(255,0,32,0.5),
            0 0 30px rgba(255,0,32,0.3);
        filter:
            drop-shadow(0 0 3px rgba(255,0,30,0.6))
            drop-shadow(0 0 8px rgba(255,0,30,0.3));
    }
    50% {
        text-shadow:
            0 0 5px rgba(255,255,255,0.5),
            0 0 12px #ff0020,
            0 0 22px rgba(255,0,32,0.6),
            0 0 40px rgba(255,0,32,0.35);
        filter:
            drop-shadow(0 0 5px rgba(255,0,30,0.7))
            drop-shadow(0 0 12px rgba(255,0,30,0.4));
    }
}

.logo-bottom {
    margin-top: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    position: relative;
    z-index: 1;
}

.logo-bottom-line {
    flex: 1;
    height: 1.5px;
    background: linear-gradient(90deg, transparent, rgba(200,16,46,0.9), transparent);
    max-width: 100px;
    box-shadow: 0 0 8px rgba(200,16,46,0.5);
}

.logo-bottom-text {
    color: #e8102a;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 5px;
    text-transform: uppercase;
    text-shadow:
        0 0 6px rgba(255,0,30,0.3),
        0 0 14px rgba(255,0,30,0.15);
    white-space: nowrap;
}

/* ── YÖNETİCİ GİRİŞİ BAŞLIĞI ── */
.login-subtitle-wrap {
    text-align: center;
    margin: 20px 0 16px;
}

.login-subtitle {
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 5px;
    color: #999;
    text-transform: uppercase;
}

.login-subtitle-deco {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 7px;
}

.deco-line {
    width: 60px;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(200,16,46,0.7), transparent);
}

.deco-spade {
    color: #c8102e;
    font-size: 12px;
    text-shadow: 0 0 8px rgba(200,16,46,0.8);
}

/* ── KART ── */
.login-card {
    width: 100%;
    background: rgba(18,18,18,0.92);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 22px;
    padding: 30px 30px 26px;
    box-shadow:
        0 40px 100px rgba(0,0,0,0.7),
        0 0 0 1px rgba(255,255,255,0.03) inset;
    backdrop-filter: blur(10px);
    animation: slideUp 0.5s 0.1s ease both;
}

.card-brand-title {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 2px;
    color: #888;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.card-brand-handle {
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 1.5px;
    color: #ff1a2e;
    text-shadow:
        0 0 6px rgba(255,0,32,0.4),
        0 0 14px rgba(255,0,32,0.3);
    filter:
        drop-shadow(0 0 3px rgba(255,0,30,0.5));
    animation: handle-pulse 2.5s ease-in-out infinite;
}

@keyframes handle-pulse {
    0%, 100% {
        text-shadow:
            0 0 6px rgba(255,0,32,0.4),
            0 0 14px rgba(255,0,32,0.3);
        filter: drop-shadow(0 0 3px rgba(255,0,30,0.5));
    }
    50% {
        text-shadow:
            0 0 8px rgba(255,0,32,0.5),
            0 0 20px rgba(255,0,32,0.35);
        filter: drop-shadow(0 0 5px rgba(255,0,30,0.6));
    }
}

.login-card-title {
    font-size: 12px;
    color: #444;
    margin-bottom: 22px;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 2px;
    font-weight: 500;
}

/* ── FORM ── */
.form-group { margin-bottom: 14px; }

.form-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    color: #777;
    text-transform: uppercase;
    margin-bottom: 7px;
}

.input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 15px;
    color: #555;
    display: flex;
    align-items: center;
    pointer-events: none;
}

.form-control {
    width: 100%;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 14px 16px 14px 46px;
    font-size: 15px;
    font-family: 'DM Sans', sans-serif;
    color: #fff;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}

.form-control::placeholder { color: #333; }

.form-control:focus {
    border-color: rgba(200,16,46,0.5);
    box-shadow: 0 0 0 3px rgba(200,16,46,0.08), 0 0 20px rgba(200,16,46,0.08);
    background: rgba(255,255,255,0.06);
}

.password-toggle {
    position: absolute;
    right: 13px;
    background: none;
    border: none;
    cursor: pointer;
    color: #444;
    display: flex;
    align-items: center;
    padding: 4px;
    transition: color 0.2s;
    line-height: 1;
}
.password-toggle:hover { color: #888; }

/* ── BUTON ── */
.btn-login {
    width: 100%;
    padding: 15px;
    margin-top: 10px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, #e8102a 0%, #9a0020 100%);
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.2s;
    box-shadow:
        0 4px 24px rgba(200,16,46,0.45),
        0 1px 0 rgba(255,255,255,0.1) inset;
}

.btn-login:hover {
    background: linear-gradient(135deg, #ff1a30 0%, #b5002a 100%);
    box-shadow: 0 6px 32px rgba(200,16,46,0.6);
    transform: translateY(-1px);
}

.btn-login:active { transform: translateY(0); }

.btn-arrow {
    width: 28px;
    height: 28px;
    background: rgba(255,255,255,0.15);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
}

/* ── HATA ── */
.alert-error {
    background: rgba(200,16,46,0.12);
    border: 1px solid rgba(200,16,46,0.35);
    border-radius: 10px;
    padding: 12px 16px;
    color: #ff4060;
    font-size: 13px;
    margin-bottom: 18px;
}

/* ── FOOTER ── */
.footer-note {
    text-align: center;
    color: #333;
    font-size: 12px;
    margin-top: 20px;
    letter-spacing: 0.5px;
}

.footer-deco {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 7px;
}

.footer-deco .deco-line {
    width: 40px;
    background: linear-gradient(90deg, transparent, rgba(200,16,46,0.5), transparent);
}

.footer-deco .deco-spade {
    color: #c8102e;
    font-size: 11px;
    opacity: 0.7;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>
<div class="login-wrap">

    <!-- LOGO -->
    <div class="modern-logo">
        <div class="modern-logo-inner">
            <span class="logo-line"></span>
            <span class="logo-text">M<span class="logo-spade">♠</span>ÇA KIZI</span>
            <span class="logo-line"></span>
        </div>
        <div class="logo-bottom">
            <span class="logo-bottom-line"></span>
            <span class="logo-bottom-text">CAFE &amp; OYUN SALONU</span>
            <span class="logo-bottom-line"></span>
        </div>
    </div>

    <!-- BAŞLIK -->
    <div class="login-subtitle-wrap">
        <div class="login-subtitle">YÖNETİCİ GİRİŞİ</div>
        <div class="login-subtitle-deco">
            <span class="deco-line"></span>
            <span class="deco-spade">♠</span>
            <span class="deco-line"></span>
        </div>
    </div>

    <!-- KART -->
    <div class="login-card">
        <div style="text-align:center; margin-bottom:18px;">
            <div class="card-brand-title">MAÇA KIZI ADİSYON SİSTEMİ 2026</div>
            <div class="card-brand-handle">@mr.sanverdii</div>
        </div>
        <div class="login-card-title">YETKİLİ PERSONEL GİRİŞİ</div>

        <?php if (!empty($hata)): ?>
            <div class="alert-error">⚠ <?php echo htmlspecialchars($hata); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label class="form-label">YÖNETİCİ ADI</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <input class="form-control" type="text" name="kullanici_adi" placeholder="yönetici adınızı girin" required autofocus autocomplete="off">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">ŞİFRE</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input class="form-control" type="password" name="sifre" id="sifre" placeholder="••••••••" required autocomplete="off">
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <svg id="eye-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button class="btn-login" type="submit">
                <span class="btn-arrow">→</span>
                Yönetici Girişi
            </button>
        </form>
    </div>

    <!-- FOOTER -->
    <div class="footer-note">Maça Kızı Adisyon Sistemi &copy; <?php echo date('Y'); ?></div>
    <div class="footer-deco">
        <span class="deco-line"></span>
        <span class="deco-spade">♠</span>
        <span class="deco-line"></span>
    </div>

</div>
<script src="/svimages.js"></script>
<script>
function togglePassword() {
    const input = document.getElementById('sifre');
    const icon  = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>`;
    } else {
        input.type = 'password';
        icon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>`;
    }
}
</script>
</body>
</html>
