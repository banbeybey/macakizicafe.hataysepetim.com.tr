<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: admin_login.php"); exit;
}

$masa_id = intval($_GET["id"] ?? 0);
$masa = $conn->query("SELECT * FROM masalar WHERE id = $masa_id")->fetch_assoc();
if (!$masa) die("Masa bulunamadı.");

$adisyon_id = null;
$detaylar = null;
$genel_toplam = 0;
$garson_adi = "Admin";

if ($masa["durum"] === "dolu" && !empty($masa["aktif_adisyon_id"])) {
    $adisyon_id = (int)$masa["aktif_adisyon_id"];
    $detaylar   = $conn->query("SELECT * FROM adisyon_detaylari WHERE adisyon_id = $adisyon_id ORDER BY id DESC");
    $toplam     = $conn->query("SELECT IFNULL(SUM(adet*fiyat),0) AS t FROM adisyon_detaylari WHERE adisyon_id=$adisyon_id")->fetch_assoc()["t"];
    $ekler      = $conn->query("SELECT IFNULL(SUM(tutar),0) AS t FROM ek_ucretler WHERE adisyon_id=$adisyon_id")->fetch_assoc()["t"];
    $genel_toplam = $toplam + $ekler;
    $conn->query("UPDATE adisyonlar SET toplam_tutar=$genel_toplam WHERE id=$adisyon_id");

    $g = $conn->query("SELECT k.ad FROM adisyonlar a LEFT JOIN kullanicilar k ON a.garson_id=k.id WHERE a.id=$adisyon_id")->fetch_assoc();
    if ($g && $g["ad"]) $garson_adi = $g["ad"];
}

$urunler_result = $conn->query("SELECT * FROM urunler WHERE aktif = 1 ORDER BY kategori, urun_adi");
$kategoriler = [];
while ($u = $urunler_result->fetch_assoc()) {
    $kategoriler[$u["kategori"]][] = $u;
}
$kategori_listesi = array_keys($kategoriler);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Masa <?php echo $masa["masa_no"]; ?> — Admin Sipariş</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;overflow:hidden}
:root{
  --bg-0:#050810;--bg-1:#0a0f1c;--bg-2:#0f1626;--bg-3:#141b30;
  --border:rgba(255,255,255,.08);--border-hover:rgba(255,255,255,.15);
  --cyan:#10c8ee;--green:#24e88a;--red:#ff3b55;--gold:#f5b942;--purple:#a855f7;
  --text:#e2f0ff;--text-bright:#ffffff;--muted:rgba(180,210,255,.55);--muted-2:rgba(180,210,255,.35);
}
body{
  font-family:'DM Sans',sans-serif;color:var(--text);background:var(--bg-0);
  background-image:
    radial-gradient(ellipse 80% 50% at 20% 0%,rgba(168,85,247,.18),transparent 50%),
    radial-gradient(ellipse 70% 60% at 80% 0%,rgba(16,200,238,.15),transparent 55%);
}
button{font-family:inherit;cursor:pointer}
a{text-decoration:none;color:inherit}

.shell{display:flex;flex-direction:column;height:100vh;height:100dvh}

/* NAV */
.topnav{
  flex-shrink:0;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 18px;
  background:rgba(5,8,16,.7);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);z-index:10;
}
.nav-left{display:flex;align-items:center;gap:12px}
.nav-icon{
  width:42px;height:42px;border-radius:13px;display:flex;align-items:center;justify-content:center;
  font-size:20px;background:linear-gradient(135deg,#ff1744,#a855f7);
  box-shadow:0 0 20px rgba(168,85,247,.35);
}
.nav-title{font-family:'Playfair Display',serif;font-size:20px;font-weight:900;color:#fff;line-height:1}
.nav-sub{font-size:10px;color:var(--muted);letter-spacing:1.2px;text-transform:uppercase;margin-top:3px;font-weight:800}
.nav-right{display:flex;align-items:center;gap:8px}
.nav-total{
  font-family:'Space Grotesk',sans-serif;font-size:16px;font-weight:700;color:var(--gold);
  background:rgba(245,185,66,.12);border:1px solid rgba(245,185,66,.25);
  padding:7px 12px;border-radius:11px;text-shadow:0 0 12px rgba(245,185,66,.3);
}
.back-btn{
  display:flex;align-items:center;gap:6px;padding:8px 13px;border-radius:11px;
  background:rgba(255,255,255,.06);border:1px solid var(--border);font-size:12px;font-weight:800;color:var(--text);
}
.back-btn:hover{background:rgba(255,255,255,.10)}

/* BODY */
.body{flex:1;overflow-y:auto;-webkit-overflow-scrolling:touch;padding:14px 14px 6px;display:flex;flex-direction:column;gap:12px}

.alert{
  background:linear-gradient(135deg,rgba(36,232,138,.15),rgba(16,200,238,.08));
  border:1px solid rgba(36,232,138,.3);border-radius:14px;padding:10px 14px;
  display:flex;align-items:center;gap:10px;color:var(--green);font-weight:800;font-size:13px;
}

.card{
  background:rgba(15,22,38,.55);backdrop-filter:blur(16px);
  border:1px solid var(--border);border-radius:18px;padding:16px;
}
.card-title{
  font-size:12px;font-weight:900;color:var(--muted);text-transform:uppercase;letter-spacing:1.2px;
  margin-bottom:12px;display:flex;align-items:center;gap:8px;
}

/* KATEGORİLER */
.cat-scroll{display:flex;gap:8px;overflow-x:auto;scrollbar-width:none;padding-bottom:2px;margin-bottom:12px}
.cat-scroll::-webkit-scrollbar{display:none}
.cat-tab{
  flex-shrink:0;padding:9px 16px;border-radius:50px;border:1px solid var(--border);
  background:rgba(255,255,255,.04);color:var(--muted);font-size:13px;font-weight:700;cursor:pointer;
  white-space:nowrap;-webkit-tap-highlight-color:transparent;user-select:none;transition:all .15s;
}
.cat-tab.active{
  background:linear-gradient(135deg,#10c8ee,#a855f7);border-color:transparent;color:#fff;
  box-shadow:0 5px 16px rgba(16,200,238,.3);
}

/* ÜRÜN GRID */
.urun-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
@media(min-width:480px){.urun-grid{grid-template-columns:repeat(3,1fr)}}
@media(min-width:700px){.urun-grid{grid-template-columns:repeat(4,1fr)}}
@media(min-width:1000px){.urun-grid{grid-template-columns:repeat(5,1fr)}}

.urun-kart{
  background:rgba(255,255,255,.04);border:1.5px solid var(--border);border-radius:14px;
  padding:12px 11px;cursor:pointer;display:flex;flex-direction:column;gap:5px;
  position:relative;min-height:82px;-webkit-tap-highlight-color:transparent;user-select:none;
  touch-action:manipulation;transition:all .15s;
}
.urun-kart:hover{background:rgba(255,255,255,.07);border-color:var(--border-hover)}
.urun-kart:active{transform:scale(.94)}
.urun-kart.secili{
  border-color:rgba(16,200,238,.6);background:rgba(16,200,238,.1);
  box-shadow:0 0 0 2px rgba(16,200,238,.2),0 0 18px rgba(16,200,238,.15);
}
.urun-adi{font-size:13px;font-weight:700;line-height:1.3;color:var(--text)}
.urun-fiyat{font-family:'Space Grotesk',sans-serif;font-size:13px;color:var(--gold);margin-top:auto;font-weight:700}
.adet-overlay{
  display:none;position:absolute;top:6px;right:8px;
  background:linear-gradient(135deg,#10c8ee,#a855f7);
  color:#fff;font-size:14px;font-weight:900;min-width:26px;height:26px;border-radius:13px;
  padding:0 7px;align-items:center;justify-content:center;
  box-shadow:0 4px 12px rgba(168,85,247,.5);
}

/* ADİSYON SATIRI */
.adisyon-row{
  display:flex;align-items:center;justify-content:space-between;
  padding:10px 0;border-bottom:1px solid rgba(255,255,255,.05);
}
.adisyon-row:last-of-type{border-bottom:none}
.adisyon-row-adi{font-size:14px;font-weight:700;color:var(--text)}
.adisyon-row-meta{font-size:11px;color:var(--muted);margin-top:2px;font-weight:600}
.adisyon-row-fiyat{font-family:'Space Grotesk',sans-serif;font-size:14px;color:var(--gold);font-weight:700;white-space:nowrap;margin-left:8px}

.toplam-row{
  display:flex;justify-content:space-between;align-items:center;
  padding-top:12px;margin-top:8px;border-top:1px solid var(--border);
}
.toplam-label{font-size:12px;color:var(--muted);font-weight:800;text-transform:uppercase;letter-spacing:.5px}
.toplam-tutar{font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;color:var(--gold);text-shadow:0 0 14px rgba(245,185,66,.3)}

.empty-orders{padding:24px;text-align:center;color:var(--muted);font-size:13px;font-weight:700}
.empty-orders-icon{font-size:36px;opacity:.4;margin-bottom:8px}

/* EK ÜCRET */
.ek-toggle{
  display:flex;align-items:center;justify-content:space-between;padding:12px 0 0;margin-top:8px;
  border-top:1px solid var(--border);cursor:pointer;color:var(--muted);font-size:13px;font-weight:700;
  -webkit-tap-highlight-color:transparent;
}
.ek-toggle .arr{transition:transform .2s}
.ek-toggle.acik .arr{transform:rotate(180deg)}
.ek-form{display:none;padding-top:14px}
.ek-form.acik{display:block}
.form-group{margin-bottom:10px}
.form-label{display:block;font-size:10px;font-weight:900;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:6px}
.form-control{
  width:100%;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:11px;
  padding:11px 13px;font-size:13px;color:var(--text);font-family:inherit;outline:none;transition:all .15s;
}
.form-control:focus{border-color:rgba(16,200,238,.5);background:rgba(255,255,255,.08);box-shadow:0 0 0 3px rgba(16,200,238,.1)}
.btn-outline{
  width:100%;padding:11px;border-radius:11px;font-size:13px;font-weight:800;cursor:pointer;
  background:rgba(255,255,255,.04);border:1px solid var(--border);color:var(--text);transition:all .15s;
}
.btn-outline:hover{background:rgba(255,255,255,.08)}

/* ONAY BAR */
.onay-bar{
  flex-shrink:0;background:rgba(5,8,16,.85);backdrop-filter:blur(20px);
  border-top:1px solid var(--border);padding:12px 14px;
  padding-bottom:max(12px,env(safe-area-inset-bottom));
}
.secilen-chips{display:none;gap:8px;overflow-x:auto;scrollbar-width:none;margin-bottom:10px;padding-bottom:2px}
.secilen-chips.var{display:flex}
.secilen-chips::-webkit-scrollbar{display:none}
.secilen-chip{
  flex-shrink:0;display:flex;align-items:center;gap:6px;
  background:rgba(16,200,238,.1);border:1px solid rgba(16,200,238,.25);
  border-radius:50px;padding:5px 6px 5px 13px;font-size:12px;
}
.chip-adi{max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:700;color:var(--text)}
.chip-sayac{display:flex;align-items:center;gap:4px}
.chip-btn{
  width:26px;height:26px;border-radius:50%;border:none;
  background:rgba(255,255,255,.08);color:var(--text);font-size:15px;font-weight:800;line-height:1;padding:0;
  display:flex;align-items:center;justify-content:center;cursor:pointer;
  touch-action:manipulation;-webkit-tap-highlight-color:transparent;
}
.chip-btn:hover{background:rgba(255,59,85,.2)}
.chip-btn:active{transform:scale(.88)}
.chip-adet{font-family:'Space Grotesk',sans-serif;font-size:13px;font-weight:800;min-width:18px;text-align:center;color:#fff}

.onay-alt{display:flex;align-items:center;justify-content:space-between;gap:12px}
.onay-toplam-label{font-size:10px;color:var(--muted);font-weight:800;text-transform:uppercase;letter-spacing:1px}
.onay-toplam-tutar{font-family:'Space Grotesk',sans-serif;font-size:24px;font-weight:700;color:var(--gold);text-shadow:0 0 16px rgba(245,185,66,.4)}
.btn-onay{
  flex-shrink:0;background:linear-gradient(135deg,#24e88a,#0f8f3f);color:#fff;
  border:none;border-radius:13px;padding:14px 28px;font-size:15px;font-weight:900;letter-spacing:.3px;
  cursor:pointer;touch-action:manipulation;-webkit-tap-highlight-color:transparent;
  opacity:.4;pointer-events:none;transition:all .2s;
  box-shadow:0 8px 20px rgba(36,232,138,.3);
}
.btn-onay.aktif{opacity:1;pointer-events:auto}
.btn-onay:hover{transform:translateY(-2px);box-shadow:0 12px 26px rgba(36,232,138,.5)}
.btn-onay:active{transform:scale(.96)}

.admin-tag{
  display:inline-flex;align-items:center;gap:4px;
  background:rgba(168,85,247,.15);color:#c8a6ff;border:1px solid rgba(168,85,247,.3);
  padding:3px 9px;border-radius:8px;font-size:10px;font-weight:900;letter-spacing:.5px;text-transform:uppercase;
}
.garson-tag{
  display:inline-flex;align-items:center;gap:4px;
  background:rgba(16,200,238,.12);color:var(--cyan);border:1px solid rgba(16,200,238,.25);
  padding:3px 9px;border-radius:8px;font-size:10px;font-weight:900;letter-spacing:.5px;text-transform:uppercase;
}
</style>
</head>
<body>

<div class="shell">

  <!-- NAV -->
  <div class="topnav">
    <div class="nav-left">
      <div class="nav-icon">♠</div>
      <div>
        <div class="nav-title">Masa <?php echo $masa["masa_no"]; ?></div>
        <div class="nav-sub">Admin Sipariş Girişi</div>
      </div>
    </div>
    <div class="nav-right">
      <div class="nav-total"><?php echo number_format($genel_toplam, 2, ',', '.'); ?> ₺</div>
      <a class="back-btn" href="admin.php">← Geri</a>
    </div>
  </div>

  <!-- BODY -->
  <div class="body">

    <?php if (isset($_GET["eklendi"])): ?>
      <div class="alert">✅ Sipariş başarıyla eklendi.</div>
    <?php endif; ?>

    <!-- Mevcut Adisyon -->
    <?php if ($masa["durum"] === "dolu"): ?>
    <div class="card">
      <div class="card-title">
        📋 Mevcut Adisyon
        <span class="<?php echo (isset($_SESSION['ad']) && $garson_adi === $_SESSION['ad']) ? 'admin-tag' : 'garson-tag'; ?>" style="margin-left:auto">
          👤 <?php echo htmlspecialchars($garson_adi); ?>
        </span>
      </div>
      <?php
      $has = false;
      if ($detaylar) {
        while ($d = $detaylar->fetch_assoc()):
          $has = true;
      ?>
        <div class="adisyon-row">
          <div>
            <div class="adisyon-row-adi"><?php echo htmlspecialchars($d["urun_adi"]); ?></div>
            <div class="adisyon-row-meta"><?php echo $d["adet"]; ?> × <?php echo number_format($d["fiyat"], 2, ',', '.'); ?> ₺</div>
          </div>
          <div class="adisyon-row-fiyat"><?php echo number_format($d["adet"] * $d["fiyat"], 2, ',', '.'); ?> ₺</div>
        </div>
      <?php endwhile; } ?>

      <?php if (!$has): ?>
        <div class="empty-orders"><div class="empty-orders-icon">🛒</div>Henüz ürün eklenmedi</div>
      <?php endif; ?>

      <div class="toplam-row">
        <div class="toplam-label">Genel Toplam</div>
        <div class="toplam-tutar"><?php echo number_format($genel_toplam, 2, ',', '.'); ?> ₺</div>
      </div>

      <!-- Ek Ücret -->
      <div class="ek-toggle" id="ekToggle" onclick="ekUcretAc()">
        <span>➕ Ek Ücret Ekle</span>
        <span class="arr">▾</span>
      </div>
      <div class="ek-form" id="ekForm">
        <form method="POST" action="ek_ucret_ekle.php">
          <input type="hidden" name="masa_id" value="<?php echo $masa_id; ?>">
          <input type="hidden" name="from" value="admin">
          <div class="form-group">
            <label class="form-label">Açıklama</label>
            <input class="form-control" type="text" name="aciklama" placeholder="Ek ücret açıklaması" required>
          </div>
          <div class="form-group">
            <label class="form-label">Tutar (₺)</label>
            <input class="form-control" type="number" step="0.01" name="tutar" placeholder="0.00" required>
          </div>
          <button class="btn-outline" type="submit">+ Ek Ücret Ekle</button>
        </form>
      </div>
    </div>
    <?php else: ?>
    <div class="card">
      <div class="card-title">📋 Yeni Adisyon</div>
      <div style="font-size:13px;color:var(--muted);padding:8px 0">Bu masa şu anda boş. Sipariş ekleyerek yeni bir adisyon açabilirsiniz.</div>
    </div>
    <?php endif; ?>

    <!-- Ürün Seç -->
    <div class="card">
      <div class="card-title">🛒 Ürün Seç</div>
      <?php if (empty($kategori_listesi)): ?>
        <div class="empty-orders">Aktif ürün bulunamadı. Önce ürün eklenmelidir.</div>
      <?php else: ?>
      <div class="cat-scroll">
        <?php foreach ($kategori_listesi as $i => $kat): ?>
          <div class="cat-tab <?php echo $i===0?'active':''; ?>"
               onclick="katSec(this,'<?php echo htmlspecialchars(addslashes($kat)); ?>')"
               data-kat="<?php echo htmlspecialchars($kat); ?>">
            <?php echo htmlspecialchars($kat); ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php foreach ($kategoriler as $kat => $urunler): ?>
        <div class="urun-panel" id="panel-<?php echo htmlspecialchars($kat); ?>"
             style="<?php echo $kat===$kategori_listesi[0]?'':'display:none;'; ?>">
          <div class="urun-grid">
            <?php foreach ($urunler as $u): ?>
              <div class="urun-kart" id="kart-<?php echo $u['id']; ?>"
                   onclick="urunTikla(<?php echo $u['id']; ?>,'<?php echo addslashes(htmlspecialchars($u['urun_adi'])); ?>',<?php echo $u['fiyat']; ?>)">
                <div class="adet-overlay" id="overlay-<?php echo $u['id']; ?>">1</div>
                <div class="urun-adi"><?php echo htmlspecialchars($u["urun_adi"]); ?></div>
                <div class="urun-fiyat"><?php echo number_format($u["fiyat"], 2, ',', '.'); ?> ₺</div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div style="text-align:center;font-size:11px;color:var(--muted);padding:8px 0">
      Admin olarak sipariş ekliyorsunuz. Hesap kapatma admin panelinden yapılır.
    </div>

  </div><!-- /body -->

  <!-- ONAY BAR -->
  <div class="onay-bar">
    <div class="secilen-chips" id="secilenChips"></div>
    <div class="onay-alt">
      <div>
        <div class="onay-toplam-label">Yeni Sipariş</div>
        <div class="onay-toplam-tutar" id="onayToplam">0,00 ₺</div>
      </div>
      <button class="btn-onay" id="btnOnay" onclick="siparisGonder()">✓ Onayla</button>
    </div>
  </div>

</div><!-- /shell -->

<!-- Gizli form -->
<form id="siparisForm" method="POST" action="urun_ekle_admin.php" style="display:none;">
  <input type="hidden" name="masa_id" value="<?php echo $masa_id; ?>">
  <div id="siparisInputlar"></div>
</form>

<script>
const sepet = {};

function katSec(el, kat) {
  document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('.urun-panel').forEach(p => p.style.display = 'none');
  const p = document.getElementById('panel-' + kat);
  if (p) p.style.display = 'block';
}

function urunTikla(id, adi, fiyat) {
  if (sepet[id]) {
    sepet[id].adet += 1;
  } else {
    sepet[id] = { adi, fiyat, adet: 1 };
  }
  kartGuncelle(id);
  onayBarGuncelle();
}

function kartGuncelle(id) {
  const overlay = document.getElementById('overlay-' + id);
  const kart = document.getElementById('kart-' + id);
  if (!overlay || !kart) return;
  if (sepet[id] && sepet[id].adet > 0) {
    overlay.textContent = sepet[id].adet;
    overlay.style.display = 'flex';
    kart.classList.add('secili');
  } else {
    overlay.style.display = 'none';
    kart.classList.remove('secili');
  }
}

function chipAdet(id, d) {
  if (!sepet[id]) return;
  sepet[id].adet = Math.max(0, sepet[id].adet + d);
  if (sepet[id].adet === 0) delete sepet[id];
  kartGuncelle(id);
  onayBarGuncelle();
}

function onayBarGuncelle() {
  const ids = Object.keys(sepet).filter(id => sepet[id] && sepet[id].adet > 0);
  const chips = document.getElementById('secilenChips');
  const tutar = document.getElementById('onayToplam');
  const btn = document.getElementById('btnOnay');

  if (!ids.length) {
    chips.classList.remove('var');
    chips.innerHTML = '';
    tutar.textContent = '0,00 ₺';
    btn.classList.remove('aktif');
    return;
  }

  let html = '', toplam = 0;
  ids.forEach(id => {
    const { adi, fiyat, adet } = sepet[id];
    toplam += fiyat * adet;
    html += `<div class="secilen-chip">
      <span class="chip-adi">${adi}</span>
      <div class="chip-sayac">
        <button class="chip-btn" onclick="chipAdet(${id},-1)">−</button>
        <span class="chip-adet">${adet}</span>
        <button class="chip-btn" onclick="chipAdet(${id},1)">+</button>
      </div>
    </div>`;
  });
  chips.innerHTML = html;
  chips.classList.add('var');
  tutar.textContent = toplam.toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + ' ₺';
  btn.classList.add('aktif');
}

function siparisGonder() {
  const ids = Object.keys(sepet).filter(id => sepet[id] && sepet[id].adet > 0);
  if (!ids.length) return;
  const cont = document.getElementById('siparisInputlar');
  cont.innerHTML = ids.map(id =>
    `<input type="hidden" name="urun_ids[]" value="${id}">` +
    `<input type="hidden" name="adetler[]" value="${sepet[id].adet}">`
  ).join('');
  document.getElementById('siparisForm').submit();
}

function ekUcretAc() {
  document.getElementById('ekToggle').classList.toggle('acik');
  document.getElementById('ekForm').classList.toggle('acik');
}
</script>

</body>
</html>
