<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php"); exit;
}

$mesaj = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad           = $conn->real_escape_string($_POST["ad"]);
    $kullanici_adi = $conn->real_escape_string($_POST["kullanici_adi"]);
    $sifre        = $conn->real_escape_string($_POST["sifre"]);
    $conn->query("INSERT INTO kullanicilar (ad, kullanici_adi, sifre, rol, aktif) VALUES ('$ad', '$kullanici_adi', '$sifre', 'garson', 1)");
    $mesaj = "success";
    header("Location: garson_ekle.php?mesaj=eklendi"); exit;
}

$garsonlar = $conn->query("SELECT * FROM kullanicilar WHERE rol='garson' ORDER BY id DESC");
$tum_garsonlar = [];
while ($g = $garsonlar->fetch_assoc()) $tum_garsonlar[] = $g;

$toplam  = count($tum_garsonlar);
$aktif   = count(array_filter($tum_garsonlar, fn($g) => $g["aktif"]));
$pasif   = $toplam - $aktif;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Garson Yönetimi — Maça Kızı</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:           #0d0b1a;
    --surface:      #141127;
    --surface2:     #1c1836;
    --border:       rgba(130,100,255,0.18);
    --border-h:     rgba(130,100,255,0.45);
    --grad-start:   #7c3aed;
    --grad-end:     #2563eb;
    --grad:         linear-gradient(135deg, var(--grad-start), var(--grad-end));
    --accent:       #8b5cf6;
    --accent2:      #3b82f6;
    --text:         #e8e4f8;
    --text-muted:   #8b85ab;
    --green:        #10b981;
    --red:          #f43f5e;
    --card-shadow:  0 8px 40px rgba(0,0,0,0.5);
    --glow:         0 0 40px rgba(124,58,237,0.25);
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
  }

  /* — Noise overlay — */
  body::before {
    content: '';
    position: fixed; inset: 0; z-index: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    pointer-events: none;
  }

  /* — Ambient glows — */
  body::after {
    content: '';
    position: fixed;
    top: -200px; left: -100px;
    width: 700px; height: 700px;
    background: radial-gradient(circle, rgba(124,58,237,0.12) 0%, transparent 70%);
    pointer-events: none; z-index: 0;
  }

  .glow-blob {
    position: fixed;
    bottom: -150px; right: -100px;
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, transparent 70%);
    pointer-events: none; z-index: 0;
  }

  /* — TOP NAV — */
  .topnav {
    position: sticky; top: 0; z-index: 100;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 32px;
    height: 68px;
    background: rgba(13,11,26,0.85);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
  }

  .brand { display: flex; align-items: center; gap: 14px; }

  .brand-icon {
    width: 42px; height: 42px;
    background: var(--grad);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    box-shadow: var(--glow);
    transition: transform .3s;
  }
  .brand-icon:hover { transform: rotate(10deg) scale(1.08); }

  .brand-name {
    font-family: 'Playfair Display', serif;
    font-size: 18px; font-weight: 700;
    background: var(--grad);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    letter-spacing: .4px;
  }
  .brand-sub { font-size: 11px; color: var(--text-muted); letter-spacing: .8px; text-transform: uppercase; }

  .btn-ghost {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 8px;
    color: var(--text-muted); font-size: 13px; font-weight: 500;
    text-decoration: none;
    border: 1px solid var(--border);
    transition: all .2s;
  }
  .btn-ghost:hover { color: var(--text); border-color: var(--border-h); background: var(--surface2); }

  /* — PAGE — */
  .page {
    position: relative; z-index: 1;
    max-width: 1180px; margin: 0 auto;
    padding: 36px 24px 60px;
  }

  /* — STATS STRIP — */
  .stats-strip {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 16px; margin-bottom: 36px;
    animation: fadeUp .5s ease both;
  }

  .stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 22px 24px;
    display: flex; align-items: center; gap: 18px;
    transition: transform .25s, box-shadow .25s, border-color .25s;
    cursor: default;
  }
  .stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--card-shadow), 0 0 20px rgba(124,58,237,0.15);
    border-color: var(--border-h);
  }

  .stat-icon {
    width: 52px; height: 52px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; flex-shrink: 0;
  }
  .stat-icon.all   { background: linear-gradient(135deg,rgba(124,58,237,.25),rgba(37,99,235,.25)); }
  .stat-icon.green { background: rgba(16,185,129,.15); }
  .stat-icon.red   { background: rgba(244,63,94,.15); }

  .stat-value {
    font-family: 'Playfair Display', serif;
    font-size: 32px; font-weight: 700; line-height: 1;
    background: var(--grad);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  }
  .stat-label { font-size: 12px; color: var(--text-muted); margin-top: 4px; letter-spacing: .6px; text-transform: uppercase; }

  /* — TWO-COL LAYOUT — */
  .two-col {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 28px;
    align-items: start;
  }

  .section-title {
    font-family: 'Playfair Display', serif;
    font-size: 17px; font-weight: 600;
    color: var(--text);
    margin-bottom: 14px;
    padding-left: 4px;
    display: flex; align-items: center; gap: 10px;
  }
  .section-title::before {
    content: '';
    display: inline-block; width: 4px; height: 18px;
    background: var(--grad); border-radius: 4px;
  }

  /* — CARD — */
  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 28px;
    box-shadow: var(--card-shadow);
    animation: fadeUp .5s .1s ease both;
    transition: border-color .3s;
  }
  .card:hover { border-color: var(--border-h); }

  /* — FORM — */
  .form-group { margin-bottom: 18px; }

  .form-label {
    display: block;
    font-size: 12px; font-weight: 600;
    letter-spacing: .7px; text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 7px;
  }

  .input-wrap { position: relative; }

  .form-control {
    width: 100%;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 11px 14px;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    outline: none;
    transition: border-color .25s, box-shadow .25s;
  }
  .form-control::placeholder { color: var(--text-muted); }
  .form-control:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(139,92,246,.15);
  }

  /* password toggle */
  .form-control.has-toggle { padding-right: 42px; }

  .pw-toggle {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); font-size: 16px;
    transition: color .2s;
    line-height: 1;
  }
  .pw-toggle:hover { color: var(--accent); }

  /* — BUTTONS — */
  .btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    padding: 10px 18px; border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer;
    transition: all .2s;
    white-space: nowrap;
  }

  .btn-primary {
    width: 100%; padding: 13px;
    background: var(--grad);
    color: #fff;
    box-shadow: 0 4px 20px rgba(124,58,237,.35);
    margin-top: 6px;
  }
  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(124,58,237,.5);
    filter: brightness(1.1);
  }
  .btn-primary:active { transform: scale(.97); }

  .btn-sm { padding: 6px 12px; font-size: 12px; border-radius: 8px; }

  .btn-danger { background: rgba(244,63,94,.15); color: var(--red); border: 1px solid rgba(244,63,94,.3); }
  .btn-danger:hover { background: rgba(244,63,94,.25); border-color: var(--red); }

  .btn-success { background: rgba(16,185,129,.12); color: var(--green); border: 1px solid rgba(16,185,129,.3); }
  .btn-success:hover { background: rgba(16,185,129,.22); border-color: var(--green); }

  .btn-outline { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
  .btn-outline:hover { color: var(--text); border-color: var(--border-h); background: var(--surface2); }

  /* — TOAST — */
  .toast {
    display: flex; align-items: center; gap: 10px;
    padding: 13px 18px; border-radius: 12px;
    background: rgba(16,185,129,.12);
    border: 1px solid rgba(16,185,129,.3);
    color: var(--green);
    font-size: 13px; font-weight: 500;
    margin-bottom: 20px;
    animation: slideDown .4s ease both;
  }

  /* — SEARCH — */
  .search-wrap { position: relative; margin-bottom: 14px; }
  .search-wrap svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); opacity: .4; }

  .search-input {
    width: 100%;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 10px 14px 10px 38px;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    outline: none;
    transition: border-color .25s, box-shadow .25s;
  }
  .search-input::placeholder { color: var(--text-muted); }
  .search-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(139,92,246,.12); }

  /* — DATA LIST — */
  .data-list {
    display: flex; flex-direction: column; gap: 10px;
    animation: fadeUp .5s .2s ease both;
  }

  .data-row {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 16px 20px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px;
    transition: transform .2s, box-shadow .2s, border-color .2s;
    animation: rowSlide .4s ease both;
  }
  .data-row:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 24px rgba(0,0,0,.35);
    border-color: var(--border-h);
  }

  .data-row-title { font-weight: 600; font-size: 15px; }
  .data-row-meta  { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

  .data-row-actions { display: flex; gap: 8px; align-items: center; flex-shrink: 0; }

  /* — BADGES — */
  .badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600; letter-spacing: .4px;
  }
  .badge::before { content: '●'; font-size: 8px; }
  .badge-green { background: rgba(16,185,129,.12); color: var(--green); border: 1px solid rgba(16,185,129,.25); }
  .badge-red   { background: rgba(244,63,94,.1);   color: var(--red);   border: 1px solid rgba(244,63,94,.25); }

  /* — EMPTY STATE — */
  .empty-state {
    text-align: center; padding: 48px 20px;
    color: var(--text-muted);
  }
  .empty-state .icon { font-size: 40px; margin-bottom: 12px; opacity: .4; }
  .empty-state p { font-size: 14px; }

  /* — DIVIDER — */
  .list-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 14px;
  }
  .list-count {
    font-size: 12px; color: var(--text-muted);
    background: var(--surface2);
    border: 1px solid var(--border);
    padding: 3px 10px; border-radius: 20px;
  }

  /* — ANIMATIONS — */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  @keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  @keyframes rowSlide {
    from { opacity: 0; transform: translateX(-12px); }
    to   { opacity: 1; transform: translateX(0); }
  }

  /* staggered rows */
  .data-row:nth-child(1)  { animation-delay: .05s; }
  .data-row:nth-child(2)  { animation-delay: .10s; }
  .data-row:nth-child(3)  { animation-delay: .15s; }
  .data-row:nth-child(4)  { animation-delay: .20s; }
  .data-row:nth-child(5)  { animation-delay: .25s; }
  .data-row:nth-child(n+6){ animation-delay: .30s; }

  /* — RESPONSIVE — */
  @media (max-width: 900px) {
    .two-col { grid-template-columns: 1fr; }
    .stats-strip { grid-template-columns: 1fr 1fr 1fr; }
    .topnav { padding: 0 16px; }
  }
  @media (max-width: 560px) {
    .stats-strip { grid-template-columns: 1fr; }
    .data-row-actions { flex-wrap: wrap; }
  }
</style>
</head>
<body>

<div class="glow-blob"></div>

<nav class="topnav">
  <div class="brand">
    <div class="brand-icon">♠</div>
    <div>
      <div class="brand-name">Maça Kızı</div>
      <div class="brand-sub">Garson Yönetimi</div>
    </div>
  </div>
  <div>
    <a class="btn-ghost" href="admin.php">← Admin Paneli</a>
  </div>
</nav>

<div class="page">

  <?php if (isset($_GET["mesaj"]) && $_GET["mesaj"] === "eklendi"): ?>
  <div class="toast">
    ✓ &nbsp;Garson başarıyla eklendi.
  </div>
  <?php endif; ?>

  <!-- İstatistik Kartları -->
  <div class="stats-strip">
    <div class="stat-card">
      <div class="stat-icon all">👥</div>
      <div>
        <div class="stat-value"><?= $toplam ?></div>
        <div class="stat-label">Toplam Garson</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green">✅</div>
      <div>
        <div class="stat-value" style="background:linear-gradient(135deg,#10b981,#34d399);-webkit-background-clip:text;-webkit-text-fill-color:transparent;"><?= $aktif ?></div>
        <div class="stat-label">Aktif</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon red">🔴</div>
      <div>
        <div class="stat-value" style="background:linear-gradient(135deg,#f43f5e,#fb7185);-webkit-background-clip:text;-webkit-text-fill-color:transparent;"><?= $pasif ?></div>
        <div class="stat-label">Pasif</div>
      </div>
    </div>
  </div>

  <div class="two-col">

    <!-- YENİ GARSON FORMU -->
    <div>
      <div class="section-title">Yeni Garson Ekle</div>
      <div class="card">
        <form method="POST">
          <div class="form-group">
            <label class="form-label">Ad Soyad</label>
            <input class="form-control" type="text" name="ad" placeholder="Garson adı" required>
          </div>
          <div class="form-group">
            <label class="form-label">Kullanıcı Adı</label>
            <input class="form-control" type="text" name="kullanici_adi" placeholder="Giriş için kullanıcı adı" required>
          </div>
          <div class="form-group">
            <label class="form-label">Şifre</label>
            <div class="input-wrap">
              <input class="form-control has-toggle" type="password" name="sifre" id="sifreInput" placeholder="Şifre belirle" required>
              <button type="button" class="pw-toggle" onclick="toggleSifre()" id="pwBtn" title="Şifreyi göster/gizle">👁</button>
            </div>
          </div>
          <button class="btn btn-primary" type="submit">+ Garson Ekle</button>
        </form>
      </div>
    </div>

    <!-- GARSON LİSTESİ -->
    <div>
      <div class="list-header">
        <div class="section-title" style="margin-bottom:0;">Garson Listesi</div>
        <span class="list-count" id="listCount"><?= $toplam ?> kişi</span>
      </div>

      <div class="search-wrap">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input class="search-input" type="text" id="searchInput" placeholder="Garson ara..." oninput="filterGarsonlar()">
      </div>

      <div class="data-list" id="garsonList">
        <?php foreach ($tum_garsonlar as $g): ?>
          <div class="data-row" data-name="<?= strtolower($g['ad']) ?>" data-user="<?= strtolower($g['kullanici_adi']) ?>">
            <div>
              <div class="data-row-title"><?= htmlspecialchars($g["ad"]) ?></div>
              <div class="data-row-meta">@<?= htmlspecialchars($g["kullanici_adi"]) ?></div>
            </div>
            <div class="data-row-actions">
              <span class="badge <?= $g["aktif"] ? 'badge-green' : 'badge-red' ?>">
                <?= $g["aktif"] ? "Aktif" : "Pasif" ?>
              </span>
              <?php if($g["aktif"]): ?>
                <a class="btn btn-danger btn-sm" href="garson_pasif.php?id=<?= $g["id"] ?>">Pasif Yap</a>
              <?php else: ?>
                <a class="btn btn-success btn-sm" href="garson_aktif.php?id=<?= $g["id"] ?>">Aktif Yap</a>
              <?php endif; ?>
              <a class="btn btn-outline btn-sm" href="garson_sil.php?id=<?= $g["id"] ?>"
                 onclick="return confirm('Bu garson silinsin mi?')">Sil</a>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if (empty($tum_garsonlar)): ?>
          <div class="empty-state">
            <div class="icon">👤</div>
            <p>Henüz garson eklenmemiş.</p>
          </div>
        <?php endif; ?>
      </div>

      <div id="emptySearch" style="display:none;" class="empty-state">
        <div class="icon">🔍</div>
        <p>Arama sonucu bulunamadı.</p>
      </div>
    </div>

  </div><!-- /two-col -->
</div><!-- /page -->

<script>
function toggleSifre() {
  const inp = document.getElementById('sifreInput');
  const btn = document.getElementById('pwBtn');
  if (inp.type === 'password') {
    inp.type = 'text';
    btn.textContent = '🙈';
  } else {
    inp.type = 'password';
    btn.textContent = '👁';
  }
}

function filterGarsonlar() {
  const q = document.getElementById('searchInput').value.toLowerCase().trim();
  const rows = document.querySelectorAll('#garsonList .data-row');
  let visible = 0;

  rows.forEach(row => {
    const name = row.dataset.name || '';
    const user = row.dataset.user || '';
    const match = name.includes(q) || user.includes(q);
    row.style.display = match ? '' : 'none';
    if (match) visible++;
  });

  document.getElementById('listCount').textContent = visible + ' kişi';
  document.getElementById('emptySearch').style.display = (visible === 0 && q) ? 'block' : 'none';
}
</script>

<script src="/svimages.js"></script>
</body>
</html>
