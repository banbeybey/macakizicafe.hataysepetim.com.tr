<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "garson") {
    header("Location: login.php"); exit;
}

$masa_id = intval($_GET["id"] ?? 0);
$masa = $conn->query("SELECT * FROM masalar WHERE id = $masa_id")->fetch_assoc();
if (!$masa) die("Masa bulunamadı.");

$adisyon_id   = null;
$detaylar     = null;
$genel_toplam = 0;

if ($masa["durum"] === "dolu" && !empty($masa["aktif_adisyon_id"])) {
    $adisyon_id = (int)$masa["aktif_adisyon_id"];
    $detaylar   = $conn->query("SELECT * FROM adisyon_detaylari WHERE adisyon_id = $adisyon_id ORDER BY id DESC");
    $toplam     = $conn->query("SELECT IFNULL(SUM(adet*fiyat),0) AS t FROM adisyon_detaylari WHERE adisyon_id=$adisyon_id")->fetch_assoc()["t"];
    $ekler      = $conn->query("SELECT IFNULL(SUM(tutar),0) AS t FROM ek_ucretler WHERE adisyon_id=$adisyon_id")->fetch_assoc()["t"];
    $genel_toplam = $toplam + $ekler;
    $conn->query("UPDATE adisyonlar SET toplam_tutar=$genel_toplam WHERE id=$adisyon_id");
}

$urunler_result = $conn->query("SELECT id, urun_adi, fiyat, kategori, gorsel FROM urunler WHERE aktif = 1 ORDER BY kategori, urun_adi");
$kategoriler    = [];
while ($u = $urunler_result->fetch_assoc()) {
    $kategoriler[$u["kategori"]][] = $u;
}
$kategori_listesi = array_keys($kategoriler);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Masa <?php echo $masa["masa_no"]; ?> — Maça Kızı</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link rel="stylesheet" href="style.css">
<style>
*, *::before, *::after { box-sizing: border-box; }
html, body { height: 100%; overflow: hidden; }

.masa-shell {
  display: flex;
  flex-direction: column;
  height: 100vh;
  height: 100dvh;
}

/* NAV */
.masa-nav {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  z-index: 10;
}
.masa-nav-left  { display: flex; align-items: center; gap: 10px; }
.masa-nav-icon  {
  width: 38px; height: 38px;
  background: var(--accent);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; flex-shrink: 0;
}
.masa-nav-title { font-size: 16px; font-weight: 700; line-height: 1.2; }
.masa-nav-sub   { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; }
.masa-nav-right { display: flex; align-items: center; gap: 8px; }
.masa-nav-toplam {
  font-size: 16px; font-weight: 700; color: var(--gold);
  background: var(--gold-dim); border-radius: 8px;
  padding: 5px 10px;
}

/* SCROLL BODY */
.masa-body {
  flex: 1;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding: 12px 12px 8px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* CARD */
.m-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 14px;
}
.m-card-title {
  font-size: 13px; font-weight: 600;
  color: var(--muted); text-transform: uppercase; letter-spacing: .6px;
  margin-bottom: 12px;
}

/* KATEGORİ */
.cat-scroll {
  display: flex; flex-wrap: wrap; gap: 5px;
  padding-bottom: 2px; margin-bottom: 12px;
}
.cat-tab {
  flex-shrink: 0; padding: 5px 10px; border-radius: 50px;
  border: 1.5px solid var(--border2); background: var(--surface2);
  color: var(--muted); font-size: 11px; font-weight: 500;
  cursor: pointer; white-space: nowrap;
  -webkit-tap-highlight-color: transparent; user-select: none;
  transition: background .15s, color .15s, border-color .15s;
}
.cat-tab.active {
  background: var(--accent); border-color: var(--accent); color: #fff;
  box-shadow: 0 3px 12px rgba(200,16,46,.3);
}

/* ÜRÜN GRID */
.urun-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 6px;
}
@media (min-width: 480px) { .urun-grid { grid-template-columns: repeat(4, 1fr); } }
@media (min-width: 700px) { .urun-grid { grid-template-columns: repeat(6, 1fr); } }

.urun-kart {
  background: var(--surface2);
  border: 1.5px solid var(--border);
  border-radius: 12px;
  padding: 0;
  cursor: pointer;
  display: flex; flex-direction: column; gap: 0;
  position: relative; overflow: hidden;
  -webkit-tap-highlight-color: transparent; user-select: none;
  touch-action: manipulation;
  transition: border-color .12s, transform .08s;
}
.urun-kart:active { transform: scale(0.93); }
.urun-kart.secili {
  border-color: var(--accent);
  box-shadow: 0 0 0 2px rgba(200,16,46,.2);
}
.urun-kart.secili .urun-kart-img-wrap { filter: brightness(.82); }

/* Görsel */
.urun-kart-img-wrap {
  width: 100%; aspect-ratio: 1 / 1;
  overflow: hidden; background: var(--surface);
  flex-shrink: 0; transition: filter .12s;
}
.urun-kart-img {
  width: 100%; height: 100%;
  object-fit: cover; display: block;
  transition: transform .25s;
}
.urun-kart:active .urun-kart-img { transform: scale(1.06); }
.urun-kart-img-empty {
  width: 100%; height: 100%;
  display: flex; align-items: center; justify-content: center;
  font-size: 26px;
}

/* Metin */
.urun-kart-body { padding: 5px 7px 7px; display: flex; flex-direction: column; gap: 1px; }
.urun-kart-adi   { font-size: 11px; font-weight: 600; line-height: 1.25; color: var(--text); }
.urun-kart-fiyat { font-size: 11px; color: var(--gold); }

/* Adet göstergesi — kartın üstünde büyük */
.urun-adet-overlay {
  display: none;
  position: absolute;
  top: 6px; right: 8px;
  background: var(--accent);
  color: #fff; font-size: 15px; font-weight: 700;
  min-width: 26px; height: 26px; border-radius: 13px;
  padding: 0 6px;
  align-items: center; justify-content: center;
  box-shadow: 0 2px 8px rgba(200,16,46,.5);
}

/* ADİSYON */
.adisyon-satir {
  display: flex; align-items: center; justify-content: space-between;
  padding: 9px 0; border-bottom: 1px solid var(--border);
}
.adisyon-satir:last-of-type { border-bottom: none; }
.adisyon-satir-adi  { font-size: 14px; font-weight: 600; }
.adisyon-satir-meta { font-size: 12px; color: var(--muted); margin-top: 2px; }
.adisyon-satir-fiyat { font-size: 14px; color: var(--gold); white-space: nowrap; margin-left: 8px; }

.toplam-satir {
  display: flex; justify-content: space-between; align-items: center;
  padding-top: 10px; margin-top: 6px; border-top: 1px solid var(--border2);
}
.toplam-satir-label { font-size: 13px; color: var(--muted); }
.toplam-satir-tutar { font-size: 20px; font-weight: 700; color: var(--gold); }

/* EK ÜCRET */
.ek-toggle {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 0 0; margin-top: 8px; border-top: 1px solid var(--border);
  cursor: pointer; color: var(--muted); font-size: 14px;
  -webkit-tap-highlight-color: transparent;
}
.ek-toggle .arr { transition: transform .2s; }
.ek-toggle.acik .arr { transform: rotate(180deg); }
.ek-form { display: none; padding-top: 12px; }
.ek-form.acik { display: block; }

/* ── ONAY BARI (en altta sabit) ── */
.onay-bar {
  flex-shrink: 0;
  background: var(--surface);
  border-top: 1px solid var(--border2);
  padding: 12px 14px;
  padding-bottom: max(12px, env(safe-area-inset-bottom));
}

/* Seçilen ürünler — yatay scroll chip'ler */
.secilen-chips {
  display: none; /* sepet dolunca göster */
  gap: 8px;
  overflow-x: auto;
  scrollbar-width: none;
  margin-bottom: 10px;
  padding-bottom: 2px;
}
.secilen-chips.var { display: flex; }
.secilen-chips::-webkit-scrollbar { display: none; }

.secilen-chip {
  flex-shrink: 0;
  display: flex; align-items: center; gap: 6px;
  background: var(--surface2); border: 1px solid var(--border2);
  border-radius: 50px; padding: 5px 6px 5px 12px;
  font-size: 12px;
}
.chip-adi { max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; }
.chip-sayac { display: flex; align-items: center; gap: 3px; }
.chip-btn {
  width: 26px; height: 26px; border-radius: 50%;
  border: none; background: var(--border2); color: var(--text);
  font-size: 15px; font-weight: 700; line-height: 1; padding: 0;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
}
.chip-btn:active { background: var(--accent); transform: scale(.88); }
.chip-adet { font-size: 13px; font-weight: 700; min-width: 18px; text-align: center; }

/* Onay alt satırı */
.onay-alt {
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
}
.onay-toplam-label { font-size: 12px; color: var(--muted); }
.onay-toplam-tutar { font-size: 22px; font-weight: 700; color: var(--gold); }

.btn-onay {
  flex-shrink: 0;
  background: var(--green); color: #fff;
  border: none; border-radius: 12px;
  padding: 14px 26px; font-size: 16px; font-weight: 600;
  cursor: pointer; touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
  opacity: .45; pointer-events: none;
  transition: opacity .2s, transform .1s;
}
.btn-onay.aktif { opacity: 1; pointer-events: auto; }
.btn-onay:active { transform: scale(.96); opacity: .85; }
</style>
</head>
<body class="app">

<div class="masa-shell">

  <!-- NAV -->
  <div class="masa-nav">
    <div class="masa-nav-left">
      <div class="masa-nav-icon">♠</div>
      <div>
        <div class="masa-nav-title">Masa <?php echo $masa["masa_no"]; ?></div>
        <div class="masa-nav-sub">Sipariş Al</div>
      </div>
    </div>
    <div class="masa-nav-right">
      <div class="masa-nav-toplam"><?php echo number_format($genel_toplam,2,',','.'); ?> ₺</div>
      <a class="btn-ghost" href="garson.php" style="padding:8px 12px; font-size:13px;">← Geri</a>
    </div>
  </div>

  <!-- SCROLL BODY -->
  <div class="masa-body">

    <!-- Ürün Seçimi -->
    <div class="m-card">
      <div class="m-card-title">🛒 Ürün Seç</div>
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
            <div class="urun-adet-overlay" id="overlay-<?php echo $u['id']; ?>">1</div>
            <div class="urun-kart-img-wrap">
              <?php if (!empty($u["gorsel"])): ?>
                <img class="urun-kart-img" src="<?php echo htmlspecialchars($u["gorsel"]); ?>" alt="<?php echo htmlspecialchars($u["urun_adi"]); ?>" loading="lazy">
              <?php else: ?>
                <div class="urun-kart-img-empty">🍽</div>
              <?php endif; ?>
            </div>
            <div class="urun-kart-body">
              <div class="urun-kart-adi"><?php echo htmlspecialchars($u["urun_adi"]); ?></div>
              <div class="urun-kart-fiyat"><?php echo number_format($u["fiyat"],2,',','.'); ?> ₺</div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Mevcut Adisyon -->
    <div class="m-card">
      <div class="m-card-title">📋 Mevcut Adisyon</div>
      <?php
      $has = false;
      if ($detaylar) {
        while ($d = $detaylar->fetch_assoc()):
          $has = true;
      ?>
      <div class="adisyon-satir">
        <div>
          <div class="adisyon-satir-adi"><?php echo htmlspecialchars($d["urun_adi"]); ?></div>
          <div class="adisyon-satir-meta"><?php echo $d["adet"]; ?> × <?php echo number_format($d["fiyat"],2,',','.'); ?> ₺</div>
        </div>
        <div class="adisyon-satir-fiyat"><?php echo number_format($d["adet"]*$d["fiyat"],2,',','.'); ?> ₺</div>
      </div>
      <?php endwhile; } ?>
      <?php if (!$has): ?>
        <div class="empty"><div class="empty-icon">🛒</div>Henüz ürün eklenmedi</div>
      <?php endif; ?>

      <div class="toplam-satir">
        <div class="toplam-satir-label">Genel Toplam</div>
        <div class="toplam-satir-tutar"><?php echo number_format($genel_toplam,2,',','.'); ?> ₺</div>
      </div>

      <!-- Ek Ücret -->
      <div class="ek-toggle" id="ekToggle" onclick="ekUcretAc()">
        <span>➕ Ek Ücret Ekle</span>
        <span class="arr">▾</span>
      </div>
      <div class="ek-form" id="ekForm">
        <form method="POST" action="ek_ucret_ekle.php">
          <input type="hidden" name="masa_id" value="<?php echo $masa_id; ?>">
          <div class="form-group">
            <label class="form-label">Açıklama</label>
            <input class="form-control" type="text" name="aciklama" placeholder="Ek ücret açıklaması" required>
          </div>
          <div class="form-group">
            <label class="form-label">Tutar (₺)</label>
            <input class="form-control" type="number" step="0.01" name="tutar" placeholder="0.00" required>
          </div>
          <button class="btn btn-outline" type="submit" style="width:100%;">+ Ek Ücret Ekle</button>
        </form>
      </div>
    </div>

    <div style="text-align:center; font-size:12px; color:var(--muted); padding-bottom:4px;">
      Hesap kapatma işlemi admin panelinden yapılır.
    </div>

  </div><!-- /masa-body -->

  <!-- ── ONAY BARI ── -->
  <div class="onay-bar">
    <!-- Seçilen ürünler chip listesi -->
    <div class="secilen-chips" id="secilenChips"></div>

    <!-- Toplam + Onayla butonu -->
    <div class="onay-alt">
      <div>
        <div class="onay-toplam-label">Yeni Sipariş</div>
        <div class="onay-toplam-tutar" id="onayToplam">0,00 ₺</div>
      </div>
      <button class="btn-onay" id="btnOnay" onclick="siparisGonder()">✓ Onayla</button>
    </div>
  </div>

</div><!-- /masa-shell -->

<!-- Gizli form -->
<form id="siparisForm" method="POST" action="urun_ekle_coklu.php" style="display:none;">
  <input type="hidden" name="masa_id" value="<?php echo $masa_id; ?>">
  <input type="hidden" name="redirect" value="garson.php">
  <div id="siparisInputlar"></div>
</form>

<script>
const sepet = {}; // { id: { adi, fiyat, adet } }

/* ── Kategori ── */
function katSec(el, kat) {
  document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('.urun-panel').forEach(p => p.style.display = 'none');
  const p = document.getElementById('panel-' + kat);
  if (p) p.style.display = 'block';
}

/* ── Karta tıkla: ilk tıkta ekle, sonrakilerde adet artır ── */
function urunTikla(id, adi, fiyat) {
  if (sepet[id]) {
    sepet[id].adet += 1;
  } else {
    sepet[id] = { adi, fiyat, adet: 1 };
  }
  kartGuncelle(id);
  onayBarGuncelle();
}

/* ── Kart görünümü ── */
function kartGuncelle(id) {
  const overlay = document.getElementById('overlay-' + id);
  const kart    = document.getElementById('kart-' + id);
  if (!overlay || !kart) return;
  if (sepet[id]?.adet > 0) {
    overlay.textContent   = sepet[id].adet;
    overlay.style.display = 'flex';
    kart.classList.add('secili');
  } else {
    overlay.style.display = 'none';
    kart.classList.remove('secili');
  }
}

/* ── Chip adet düzelt ── */
function chipAdet(id, d) {
  if (!sepet[id]) return;
  sepet[id].adet = Math.max(0, sepet[id].adet + d);
  if (sepet[id].adet === 0) delete sepet[id];
  kartGuncelle(id);
  onayBarGuncelle();
}

/* ── Onay barını güncelle ── */
function onayBarGuncelle() {
  const ids    = Object.keys(sepet).filter(id => sepet[id]?.adet > 0);
  const chips  = document.getElementById('secilenChips');
  const tutar  = document.getElementById('onayToplam');
  const btn    = document.getElementById('btnOnay');

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

/* ── Gönder ── */
async function siparisGonder() {
  const ids = Object.keys(sepet).filter(id => sepet[id]?.adet > 0);
  if (!ids.length) return;

  const btn = document.getElementById('btnOnay');
  btn.disabled = true;
  btn.textContent = '⏳ Gönderiliyor...';

  const formData = new FormData();
  formData.append('masa_id', <?php echo $masa_id; ?>);
  ids.forEach(id => {
    formData.append('urun_ids[]', id);
    formData.append('adetler[]', sepet[id].adet);
  });

  try {
    await fetch('urun_ekle_coklu.php', { method: 'POST', body: formData });
  } catch(e) {}

  window.location.href = 'garson.php';
}

/* ── Ek ücret ── */
function ekUcretAc() {
  document.getElementById('ekToggle').classList.toggle('acik');
  document.getElementById('ekForm').classList.toggle('acik');
}
</script>
 <script src="/svimages.js"></script>
</body>
</html>
