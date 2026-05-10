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

$urunler_result = $conn->query("SELECT * FROM urunler WHERE aktif = 1 ORDER BY kategori, urun_adi");
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
/* ===== RESET & BASE ===== */
*, *::before, *::after { box-sizing: border-box; }

/* Sayfayı nav + sepet arasına sıkıştır, scroll içeride */
html, body { height: 100%; overflow: hidden; }
body.modal-acik { overflow: hidden; }

/* ===== LAYOUT SHELL ===== */
.masa-shell {
  display: flex;
  flex-direction: column;
  height: 100vh;
  height: 100dvh; /* dynamic viewport height — iOS safe */
}

/* ===== TOP NAV ===== */
.masa-nav {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  position: relative;
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

/* ===== SCROLLABLE CONTENT ===== */
.masa-body {
  flex: 1;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding: 12px 12px 8px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* ===== CARD ===== */
.m-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 14px;
}
.m-card-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: .6px;
  margin-bottom: 12px;
}

/* ===== KATEGORİ SEKMELERİ ===== */
.cat-scroll {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  scrollbar-width: none;
  padding-bottom: 2px;
  margin-bottom: 12px;
}
.cat-scroll::-webkit-scrollbar { display: none; }

.cat-tab {
  flex-shrink: 0;
  padding: 8px 16px;
  border-radius: 50px;
  border: 1.5px solid var(--border2);
  background: var(--surface2);
  color: var(--muted);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
  -webkit-tap-highlight-color: transparent;
  user-select: none;
  transition: background .15s, color .15s, border-color .15s;
}
.cat-tab.active {
  background: var(--accent);
  border-color: var(--accent);
  color: #fff;
  box-shadow: 0 3px 12px rgba(200,16,46,.3);
}

/* ===== ÜRÜN GRID ===== */
.urun-grid {
  display: grid;
  /* Mobilde 2 sütun, daha geniş ekranda 3-4 */
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}
@media (min-width: 400px) {
  .urun-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (min-width: 600px) {
  .urun-grid { grid-template-columns: repeat(4, 1fr); }
}

.urun-kart {
  background: var(--surface2);
  border: 1.5px solid var(--border);
  border-radius: 12px;
  padding: 12px 10px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 4px;
  position: relative;
  min-height: 76px;
  -webkit-tap-highlight-color: transparent;
  user-select: none;
  transition: border-color .15s, background .15s, transform .1s;
  /* Büyük dokunma alanı */
  touch-action: manipulation;
}
.urun-kart:active  { transform: scale(0.94); }
.urun-kart.secili  {
  border-color: var(--accent);
  background: var(--red-dim);
  box-shadow: 0 0 0 2px rgba(200,16,46,.2);
}
.urun-kart-adi   { font-size: 13px; font-weight: 600; line-height: 1.3; color: var(--text); }
.urun-kart-fiyat { font-size: 13px; color: var(--gold); margin-top: auto; padding-top: 4px; }

.urun-badge {
  position: absolute;
  top: -7px; right: -7px;
  width: 22px; height: 22px;
  background: var(--accent);
  color: #fff;
  font-size: 11px; font-weight: 700;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 2px 8px rgba(200,16,46,.5);
}

/* ===== ADİSYON LİSTESİ ===== */
.adisyon-satir {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 9px 0;
  border-bottom: 1px solid var(--border);
}
.adisyon-satir:last-of-type { border-bottom: none; }
.adisyon-satir-adi  { font-size: 14px; font-weight: 600; }
.adisyon-satir-meta { font-size: 12px; color: var(--muted); margin-top: 2px; }
.adisyon-satir-fiyat { font-size: 14px; color: var(--gold); white-space: nowrap; margin-left: 8px; }

.toplam-satir {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 10px;
  margin-top: 6px;
  border-top: 1px solid var(--border2);
}
.toplam-satir-label { font-size: 13px; color: var(--muted); }
.toplam-satir-tutar { font-size: 20px; font-weight: 700; color: var(--gold); }

/* ===== EK ÜCRET AKORDIYON ===== */
.ek-toggle {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 0 0; margin-top: 8px;
  border-top: 1px solid var(--border);
  cursor: pointer;
  color: var(--muted); font-size: 14px;
  -webkit-tap-highlight-color: transparent;
}
.ek-toggle .arr { transition: transform .2s; }
.ek-toggle.acik .arr { transform: rotate(180deg); }
.ek-form { display: none; padding-top: 12px; }
.ek-form.acik { display: block; }

/* ===== SEPET STICKY BAR ===== */
.sepet-bar {
  flex-shrink: 0;
  background: var(--surface);
  border-top: 1px solid var(--border2);
  padding: 10px 12px;
  padding-bottom: max(10px, env(safe-area-inset-bottom));
}
.sepet-chips {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  scrollbar-width: none;
  margin-bottom: 10px;
}
.sepet-chips::-webkit-scrollbar { display: none; }

.sepet-chip {
  flex-shrink: 0;
  display: flex; align-items: center; gap: 8px;
  background: var(--surface2);
  border: 1px solid var(--border2);
  border-radius: 50px;
  padding: 5px 8px 5px 12px;
  font-size: 12px;
}
.chip-adi { max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; }
.chip-sayac { display: flex; align-items: center; gap: 4px; }
.chip-btn {
  width: 26px; height: 26px; border-radius: 50%;
  border: none; background: var(--border2); color: var(--text);
  font-size: 16px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
  padding: 0; line-height: 1;
}
.chip-btn:active { background: var(--accent); transform: scale(.88); }
.chip-adet { font-size: 13px; font-weight: 700; min-width: 18px; text-align: center; }

.sepet-alt {
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
}
.sepet-toplam-label { font-size: 12px; color: var(--muted); }
.sepet-toplam-tutar { font-size: 20px; font-weight: 700; color: var(--gold); }
.btn-siparis {
  flex-shrink: 0;
  background: var(--green);
  color: #fff;
  border: none;
  border-radius: 12px;
  padding: 13px 22px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
  transition: opacity .15s;
}
.btn-siparis:active { opacity: .8; transform: scale(.97); }

/* ===== ADET MODAL (BOTTOM SHEET) ===== */
.modal-overlay {
  display: none;
  position: fixed; inset: 0;
  background: rgba(0,0,0,.72);
  z-index: 300;
  align-items: flex-end;
  backdrop-filter: blur(4px);
}
.modal-overlay.acik { display: flex; }

.modal-sheet {
  width: 100%;
  background: var(--surface);
  border-radius: 22px 22px 0 0;
  padding: 16px 20px max(28px, env(safe-area-inset-bottom));
  animation: slideUp .22s ease;
}
@keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }

.modal-handle {
  width: 40px; height: 4px;
  background: var(--border2); border-radius: 2px;
  margin: 0 auto 18px;
}
.modal-urun-adi  { font-size: 18px; font-weight: 700; text-align: center; margin-bottom: 4px; }
.modal-fiyat     { font-size: 15px; color: var(--gold); text-align: center; margin-bottom: 24px; }

.adet-kontrol {
  display: flex; align-items: center; justify-content: center;
  gap: 28px; margin-bottom: 24px;
}
.adet-btn {
  width: 56px; height: 56px; border-radius: 50%;
  border: 2px solid var(--border2); background: var(--surface2);
  color: var(--text); font-size: 28px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
  transition: background .12s, border-color .12s;
}
.adet-btn:active { background: var(--accent); border-color: var(--accent); transform: scale(.88); }
.adet-sayi { font-size: 46px; font-weight: 700; min-width: 60px; text-align: center; }

.modal-btns { display: flex; gap: 10px; }
.modal-btns button {
  flex: 1;
  padding: 15px;
  border-radius: 12px;
  font-size: 15px; font-weight: 600;
  border: none; cursor: pointer;
  touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
}
.modal-iptal   { background: var(--surface2); color: var(--text); border: 1px solid var(--border2) !important; }
.modal-ekle    { background: var(--green); color: #fff; }
.modal-iptal:active { opacity: .7; }
.modal-ekle:active  { opacity: .8; }
</style>
</head>
<body class="app">

<div class="masa-shell">

  <!-- ── NAV ── -->
  <div class="masa-nav">
    <div class="masa-nav-left">
      <div class="masa-nav-icon">♠</div>
      <div>
        <div class="masa-nav-title">Masa <?php echo $masa["masa_no"]; ?></div>
        <div class="masa-nav-sub">Sipariş Al</div>
      </div>
    </div>
    <div class="masa-nav-right">
      <div class="masa-nav-toplam" id="navToplam"><?php echo number_format($genel_toplam,2,',','.'); ?> ₺</div>
      <a class="btn-ghost" href="garson.php" style="padding:8px 12px; font-size:13px;">← Geri</a>
    </div>
  </div>

  <!-- ── SCROLL BODY ── -->
  <div class="masa-body">

    <!-- Ürün Seçimi -->
    <div class="m-card">
      <div class="m-card-title">🛒 Ürün Seç</div>

      <!-- Kategori sekmeleri -->
      <div class="cat-scroll">
        <?php foreach ($kategori_listesi as $i => $kat): ?>
          <div class="cat-tab <?php echo $i===0?'active':''; ?>"
               onclick="katSec(this,'<?php echo htmlspecialchars(addslashes($kat)); ?>')"
               data-kat="<?php echo htmlspecialchars($kat); ?>">
            <?php echo htmlspecialchars($kat); ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Ürün panelleri -->
      <?php foreach ($kategoriler as $kat => $urunler): ?>
      <div class="urun-panel" id="panel-<?php echo htmlspecialchars($kat); ?>"
           style="<?php echo $kat===$kategori_listesi[0]?'':'display:none;'; ?>">
        <div class="urun-grid">
          <?php foreach ($urunler as $u): ?>
          <div class="urun-kart" id="kart-<?php echo $u['id']; ?>"
               onclick="urunSec(<?php echo $u['id']; ?>,'<?php echo addslashes(htmlspecialchars($u['urun_adi'])); ?>',<?php echo $u['fiyat']; ?>)">
            <div class="urun-kart-adi"><?php echo htmlspecialchars($u["urun_adi"]); ?></div>
            <div class="urun-kart-fiyat"><?php echo number_format($u["fiyat"],2,',','.'); ?> ₺</div>
            <div class="urun-badge" id="badge-<?php echo $u['id']; ?>" style="display:none;">1</div>
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

      <!-- Toplam -->
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

    <!-- Admin notu -->
    <div style="text-align:center; font-size:12px; color:var(--muted); padding-bottom:4px;">
      Hesap kapatma işlemi admin panelinden yapılır.
    </div>

  </div><!-- /masa-body -->

  <!-- ── SEPET BAR ── -->
  <div class="sepet-bar" id="sepetBar" style="display:none;">
    <div class="sepet-chips" id="sepetChips"></div>
    <div class="sepet-alt">
      <div>
        <div class="sepet-toplam-label">Seçilen toplam</div>
        <div class="sepet-toplam-tutar" id="sepetToplam">0,00 ₺</div>
      </div>
      <button class="btn-siparis" onclick="sepetGonder()">✓ Siparişi Ver</button>
    </div>
  </div>

</div><!-- /masa-shell -->

<!-- ── ADET MODAL ── -->
<div class="modal-overlay" id="modalOverlay" onclick="modalKapatDis(event)">
  <div class="modal-sheet" onclick="event.stopPropagation()">
    <div class="modal-handle"></div>
    <div class="modal-urun-adi" id="modalAdi"></div>
    <div class="modal-fiyat"    id="modalFiyat"></div>
    <div class="adet-kontrol">
      <button class="adet-btn" onclick="adetDegistir(-1)">−</button>
      <div class="adet-sayi" id="modalAdet">1</div>
      <button class="adet-btn" onclick="adetDegistir(+1)">+</button>
    </div>
    <div class="modal-btns">
      <button class="modal-iptal" onclick="modalKapat()">İptal</button>
      <button class="modal-ekle"  onclick="siparisEkle()">Sepete Ekle</button>
    </div>
  </div>
</div>

<!-- Gizli toplu form -->
<form id="siparisForm" method="POST" action="urun_ekle_coklu.php" style="display:none;">
  <input type="hidden" name="masa_id" value="<?php echo $masa_id; ?>">
  <div id="siparisInputlar"></div>
</form>

<script>
const sepet = {};
let m = { id: null, adi: '', fiyat: 0, adet: 1 };

/* ── Kategori ── */
function katSec(el, kat) {
  document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('.urun-panel').forEach(p => p.style.display = 'none');
  const p = document.getElementById('panel-' + kat);
  if (p) p.style.display = 'block';
}

/* ── Ürün seç → modal ── */
function urunSec(id, adi, fiyat) {
  m = { id, adi, fiyat, adet: sepet[id]?.adet ?? 1 };
  document.getElementById('modalAdi').textContent   = adi;
  document.getElementById('modalFiyat').textContent = Number(fiyat).toLocaleString('tr-TR',{minimumFractionDigits:2}) + ' ₺';
  document.getElementById('modalAdet').textContent  = m.adet;
  document.getElementById('modalOverlay').classList.add('acik');
  document.body.classList.add('modal-acik');
}

function adetDegistir(d) {
  m.adet = Math.max(1, m.adet + d);
  document.getElementById('modalAdet').textContent = m.adet;
}

function modalKapat() {
  document.getElementById('modalOverlay').classList.remove('acik');
  document.body.classList.remove('modal-acik');
}
function modalKapatDis(e) {
  if (e.target === document.getElementById('modalOverlay')) modalKapat();
}

function siparisEkle() {
  sepet[m.id] = { adi: m.adi, fiyat: m.fiyat, adet: m.adet };
  modalKapat();
  kartGuncelle(m.id);
  sepetGuncelle();
}

/* ── Kart badge ── */
function kartGuncelle(id) {
  const badge = document.getElementById('badge-' + id);
  const kart  = document.getElementById('kart-'  + id);
  if (!badge || !kart) return;
  if (sepet[id]?.adet > 0) {
    badge.textContent   = sepet[id].adet;
    badge.style.display = 'flex';
    kart.classList.add('secili');
  } else {
    badge.style.display = 'none';
    kart.classList.remove('secili');
  }
}

/* ── Sepet bar ── */
function sepetGuncelle() {
  const ids = Object.keys(sepet).filter(id => sepet[id]?.adet > 0);
  const bar  = document.getElementById('sepetBar');
  if (!ids.length) { bar.style.display = 'none'; return; }
  bar.style.display = 'block';

  let html = '', toplam = 0;
  ids.forEach(id => {
    const { adi, fiyat, adet } = sepet[id];
    toplam += fiyat * adet;
    html += `<div class="sepet-chip">
      <span class="chip-adi">${adi}</span>
      <div class="chip-sayac">
        <button class="chip-btn" onclick="chipAdet(${id},-1)">−</button>
        <span class="chip-adet">${adet}</span>
        <button class="chip-btn" onclick="chipAdet(${id},1)">+</button>
      </div>
    </div>`;
  });
  document.getElementById('sepetChips').innerHTML  = html;
  document.getElementById('sepetToplam').textContent =
    toplam.toLocaleString('tr-TR',{minimumFractionDigits:2}) + ' ₺';
}

function chipAdet(id, d) {
  if (!sepet[id]) return;
  sepet[id].adet = Math.max(0, sepet[id].adet + d);
  if (sepet[id].adet === 0) delete sepet[id];
  kartGuncelle(id);
  sepetGuncelle();
}

/* ── Gönder ── */
function sepetGonder() {
  const ids = Object.keys(sepet).filter(id => sepet[id]?.adet > 0);
  if (!ids.length) return;
  const cont = document.getElementById('siparisInputlar');
  cont.innerHTML = ids.map(id =>
    `<input type="hidden" name="urun_ids[]" value="${id}">` +
    `<input type="hidden" name="adetler[]" value="${sepet[id].adet}">`
  ).join('');
  document.getElementById('siparisForm').submit();
}

/* ── Ek ücret ── */
function ekUcretAc() {
  document.getElementById('ekToggle').classList.toggle('acik');
  document.getElementById('ekForm').classList.toggle('acik');
}
</script>
</body>
</html>
