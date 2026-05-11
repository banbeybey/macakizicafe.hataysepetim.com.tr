<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php"); exit;
}

$adisyon_id = intval($_GET["id"] ?? 0);
$adisyon = $conn->query("
    SELECT a.*, m.masa_no, k.ad AS garson_adi
    FROM adisyonlar a
    LEFT JOIN masalar m ON a.masa_id = m.id
    LEFT JOIN kullanicilar k ON a.garson_id = k.id
    WHERE a.id = $adisyon_id
")->fetch_assoc();

if (!$adisyon) die("Adisyon bulunamadı.");

$detaylar = $conn->query("
    SELECT ad.*, u.gorsel
    FROM adisyon_detaylari ad
    LEFT JOIN urunler u ON u.urun_adi = ad.urun_adi
    WHERE ad.adisyon_id = $adisyon_id
");
$ekler = $conn->query("SELECT * FROM ek_ucretler WHERE adisyon_id = $adisyon_id");

$det_arr = [];
while ($d = $detaylar->fetch_assoc()) $det_arr[] = $d;
$ek_arr = [];
while ($e = $ekler->fetch_assoc()) $ek_arr[] = $e;

$sure = "";
if (!empty($adisyon["acilis_tarihi"]) && !empty($adisyon["kapanis_tarihi"])) {
    $diff = strtotime($adisyon["kapanis_tarihi"]) - strtotime($adisyon["acilis_tarihi"]);
    $dk = floor($diff / 60);
    $sure = $dk >= 60 ? floor($dk/60)."s ".($dk%60)."dk" : $dk." dk";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Adisyon #<?php echo $adisyon_id; ?> — Maça Kızı</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#050810;--surface:rgba(15,22,38,.65);--surface2:rgba(20,28,48,.8);
  --border:rgba(255,255,255,.08);--border2:rgba(255,255,255,.14);
  --cyan:#10c8ee;--green:#24e88a;--red:#ff3b55;--gold:#f5b942;--purple:#a855f7;
  --text:#e2f0ff;--bright:#fff;--muted:rgba(180,210,255,.55);--muted2:rgba(180,210,255,.32);
}
body{
  font-family:'DM Sans',sans-serif;color:var(--text);min-height:100vh;
  background:var(--bg);overflow-x:hidden;
  background-image:
    radial-gradient(ellipse 80% 50% at 20% 0%,rgba(168,85,247,.18),transparent 50%),
    radial-gradient(ellipse 70% 60% at 80% 0%,rgba(16,200,238,.14),transparent 55%),
    radial-gradient(ellipse 60% 40% at 50% 100%,rgba(255,59,85,.10),transparent 60%);
  background-attachment:fixed;
}

/* ── Nav ─────────────────────────────────────────── */
.topnav{
  position:sticky;top:0;z-index:50;
  display:flex;align-items:center;justify-content:space-between;
  padding:14px 28px;
  background:rgba(5,8,16,.7);
  backdrop-filter:blur(24px);border-bottom:1px solid var(--border);
}
.brand{display:flex;align-items:center;gap:14px}
.brand-icon{
  width:44px;height:44px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;font-size:22px;
  background:linear-gradient(135deg,#ff1744,#a855f7);
  box-shadow:0 0 28px rgba(168,85,247,.4);
}
.brand-name{font-family:'Playfair Display',serif;font-size:20px;font-weight:900;color:var(--bright)}
.brand-sub{font-size:10px;color:var(--muted);font-weight:700;letter-spacing:1.2px;text-transform:uppercase;margin-top:3px}
.back-btn{
  display:flex;align-items:center;gap:8px;padding:10px 18px;
  border-radius:13px;font-size:13px;font-weight:700;
  background:rgba(255,255,255,.06);border:1px solid var(--border);
  color:var(--text);text-decoration:none;transition:.2s;
}
.back-btn:hover{background:rgba(255,255,255,.1);border-color:var(--border2);transform:translateX(-2px)}

/* ── Page ────────────────────────────────────────── */
.page{max-width:860px;margin:0 auto;padding:32px 24px 80px}

/* ── Hero tutar kartı ────────────────────────────── */
.hero-card{
  position:relative;overflow:hidden;
  background:linear-gradient(135deg,rgba(168,85,247,.2),rgba(16,200,238,.12));
  border:1px solid rgba(168,85,247,.3);border-radius:24px;
  padding:28px 32px;margin-bottom:20px;
  display:flex;align-items:center;justify-content:space-between;gap:24px;
  animation:fadeUp .5s ease;
}
.hero-card::before{
  content:"";position:absolute;inset:0;
  background:radial-gradient(circle at 80% 50%,rgba(16,200,238,.12),transparent 60%);
  pointer-events:none;
}
.hero-card::after{
  content:"#<?php echo $adisyon_id; ?>";
  position:absolute;right:32px;bottom:-20px;
  font-family:'Playfair Display',serif;font-size:120px;font-weight:900;
  color:rgba(255,255,255,.04);line-height:1;pointer-events:none;letter-spacing:-4px;
}
.hero-left{}
.hero-label{font-size:11px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
.hero-amount{
  font-family:'Space Grotesk',sans-serif;font-size:52px;font-weight:700;
  color:var(--bright);letter-spacing:-2px;line-height:1;
}
.hero-amount span{font-size:24px;color:var(--gold);margin-left:4px}
.hero-meta{display:flex;align-items:center;gap:10px;margin-top:12px;flex-wrap:wrap}
.hero-badge{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 14px;border-radius:100px;font-size:12px;font-weight:800;
}
.hero-badge.kapali{background:rgba(148,163,184,.12);color:#94a3b8;border:1px solid rgba(148,163,184,.2)}
.hero-badge.acik{background:rgba(36,232,138,.12);color:var(--green);border:1px solid rgba(36,232,138,.3)}
.hero-badge.acik::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 6px var(--green);animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
.hero-garson{font-size:13px;color:var(--muted);font-weight:600}
.hero-right{display:flex;flex-direction:column;gap:8px;align-items:flex-end;flex-shrink:0}

/* ── Info grid ───────────────────────────────────── */
.info-grid{
  display:grid;grid-template-columns:repeat(3,1fr);gap:12px;
  margin-bottom:20px;animation:fadeUp .6s ease;
}
.info-tile{
  background:var(--surface);border:1px solid var(--border);border-radius:16px;
  padding:16px 18px;backdrop-filter:blur(16px);
}
.info-tile-label{font-size:10px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:6px}
.info-tile-value{font-family:'Space Grotesk',sans-serif;font-size:15px;font-weight:600;color:var(--bright)}
.info-tile-icon{font-size:20px;margin-bottom:8px}

/* ── Bölüm başlığı ───────────────────────────────── */
.sec-header{
  display:flex;align-items:center;justify-content:space-between;
  margin:24px 0 12px;
}
.sec-title{
  font-family:'Playfair Display',serif;font-size:19px;font-weight:900;
  color:var(--bright);display:flex;align-items:center;gap:10px;
}
.sec-title::before{content:"";width:4px;height:18px;border-radius:99px;background:linear-gradient(180deg,var(--cyan),var(--purple));box-shadow:0 0 10px var(--cyan)}
.sec-count{font-size:12px;font-weight:700;color:var(--muted);background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:100px;padding:4px 12px}

/* ── Ürün kartları ───────────────────────────────── */
.item-list{display:flex;flex-direction:column;gap:8px;animation:fadeUp .7s ease}
.item-card{
  display:flex;align-items:center;gap:14px;
  background:var(--surface);border:1px solid var(--border);border-radius:16px;
  padding:12px 16px;transition:all .2s;position:relative;overflow:hidden;
}
.item-card::before{
  content:"";position:absolute;left:0;top:0;bottom:0;width:3px;border-radius:99px;
  background:linear-gradient(180deg,var(--cyan),var(--purple));
}
.item-card:hover{border-color:var(--border2);background:var(--surface2);transform:translateX(3px)}
.item-thumb{
  width:60px;height:60px;border-radius:12px;object-fit:cover;flex-shrink:0;
  border:1px solid var(--border);
}
.item-thumb-empty{
  width:60px;height:60px;border-radius:12px;flex-shrink:0;
  background:rgba(255,255,255,.04);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;font-size:24px;
}
.item-body{flex:1;min-width:0}
.item-name{font-size:14px;font-weight:700;color:var(--bright);margin-bottom:4px}
.item-meta{font-size:12px;color:var(--muted);font-weight:600}
.item-adet{
  display:inline-flex;align-items:center;justify-content:center;
  min-width:28px;height:22px;padding:0 8px;
  background:rgba(16,200,238,.12);border:1px solid rgba(16,200,238,.25);
  border-radius:7px;font-size:11px;font-weight:900;color:var(--cyan);margin-right:6px;
}
.item-price{
  font-family:'Space Grotesk',sans-serif;font-size:17px;font-weight:700;
  color:var(--gold);white-space:nowrap;flex-shrink:0;
  text-shadow:0 0 16px rgba(245,185,66,.25);
}

/* ── Ek ücret kartları ───────────────────────────── */
.ek-card{
  display:flex;align-items:center;gap:14px;
  background:rgba(255,59,85,.06);border:1px solid rgba(255,59,85,.15);border-radius:16px;
  padding:12px 16px;transition:.2s;
}
.ek-card:hover{background:rgba(255,59,85,.1);border-color:rgba(255,59,85,.25)}
.ek-icon{
  width:44px;height:44px;border-radius:12px;flex-shrink:0;
  background:rgba(255,59,85,.12);border:1px solid rgba(255,59,85,.2);
  display:flex;align-items:center;justify-content:center;font-size:20px;
}
.ek-name{flex:1;font-size:14px;font-weight:700;color:var(--bright)}
.ek-price{font-family:'Space Grotesk',sans-serif;font-size:17px;font-weight:700;color:#ff8798;white-space:nowrap}

/* ── Toplam özet ─────────────────────────────────── */
.total-box{
  margin-top:24px;
  background:linear-gradient(135deg,rgba(36,232,138,.1),rgba(16,200,238,.08));
  border:1px solid rgba(36,232,138,.25);border-radius:18px;
  padding:20px 24px;
  display:flex;align-items:center;justify-content:space-between;gap:16px;
  animation:fadeUp .8s ease;
}
.total-box-label{font-size:13px;font-weight:800;color:var(--green);letter-spacing:.5px;text-transform:uppercase}
.total-box-amount{font-family:'Space Grotesk',sans-serif;font-size:32px;font-weight:700;color:var(--bright);letter-spacing:-1px}

/* ── Ödeme pill ──────────────────────────────────── */
.odeme-pill{
  display:inline-flex;align-items:center;gap:6px;padding:6px 14px;
  border-radius:100px;font-size:12px;font-weight:800;
}
.odeme-nakit{background:rgba(36,232,138,.12);color:var(--green);border:1px solid rgba(36,232,138,.25)}
.odeme-kart{background:rgba(16,200,238,.12);color:var(--cyan);border:1px solid rgba(16,200,238,.25)}
.odeme-diger{background:rgba(255,255,255,.06);color:var(--muted);border:1px solid var(--border)}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

@media(max-width:600px){
  .topnav{padding:12px 16px}
  .page{padding:20px 14px 60px}
  .hero-card{padding:20px;flex-direction:column;align-items:flex-start}
  .hero-amount{font-size:38px}
  .hero-right{align-items:flex-start}
  .info-grid{grid-template-columns:1fr 1fr}
  .item-thumb,.item-thumb-empty{width:48px;height:48px}
  .total-box{flex-direction:column;align-items:flex-start;gap:6px}
  .total-box-amount{font-size:26px}
}
</style>
</head>
<body>

<nav class="topnav">
  <div class="brand">
    <div class="brand-icon">♠</div>
    <div>
      <div class="brand-name">Adisyon #<?php echo $adisyon_id; ?></div>
      <div class="brand-sub">Masa <?php echo $adisyon["masa_no"]; ?></div>
    </div>
  </div>
  <a class="back-btn" href="adisyon_gecmisi.php">← Geçmiş</a>
</nav>

<div class="page">

  <!-- ── Hero tutar ── -->
  <div class="hero-card">
    <div class="hero-left">
      <div class="hero-label">Toplam Tutar</div>
      <div class="hero-amount">
        <?php echo number_format($adisyon["toplam_tutar"],2,',','.'); ?>
        <span>₺</span>
      </div>
      <div class="hero-meta">
        <span class="hero-badge <?php echo $adisyon["durum"]; ?>">
          <?php echo $adisyon["durum"] === "kapali" ? "Kapalı" : "Açık"; ?>
        </span>
        <?php if (!empty($adisyon["odeme_yontemi"])): ?>
          <?php
            $oCls = "odeme-diger";
            if ($adisyon["odeme_yontemi"] === "Nakit") $oCls = "odeme-nakit";
            elseif ($adisyon["odeme_yontemi"] === "Kredi Kartı") $oCls = "odeme-kart";
          ?>
          <span class="odeme-pill <?php echo $oCls; ?>"><?php echo htmlspecialchars($adisyon["odeme_yontemi"]); ?></span>
        <?php endif; ?>
        <span class="hero-garson">👤 <?php echo htmlspecialchars($adisyon["garson_adi"] ?? "—"); ?></span>
      </div>
    </div>
  </div>

  <!-- ── Bilgi kutucukları ── -->
  <div class="info-grid">
    <div class="info-tile">
      <div class="info-tile-icon">🕐</div>
      <div class="info-tile-label">Açılış</div>
      <div class="info-tile-value"><?php echo date("d.m.Y H:i", strtotime($adisyon["acilis_tarihi"])); ?></div>
    </div>
    <div class="info-tile">
      <div class="info-tile-icon">🏁</div>
      <div class="info-tile-label">Kapanış</div>
      <div class="info-tile-value"><?php echo $adisyon["kapanis_tarihi"] ? date("d.m.Y H:i", strtotime($adisyon["kapanis_tarihi"])) : "—"; ?></div>
    </div>
    <div class="info-tile">
      <div class="info-tile-icon">⏱</div>
      <div class="info-tile-label">Süre</div>
      <div class="info-tile-value"><?php echo $sure ?: "—"; ?></div>
    </div>
  </div>

  <!-- ── Siparişler ── -->
  <div class="sec-header">
    <div class="sec-title">Siparişler</div>
    <span class="sec-count"><?php echo count($det_arr); ?> kalem</span>
  </div>
  <div class="item-list">
    <?php foreach ($det_arr as $d):
      $gorsel = !empty($d["gorsel"]) ? $d["gorsel"] : null;
      $gorselSrc = $gorsel
        ? $gorsel . "?v=" . (file_exists($_SERVER["DOCUMENT_ROOT"].$gorsel) ? filemtime($_SERVER["DOCUMENT_ROOT"].$gorsel) : 0)
        : null;
    ?>
    <div class="item-card">
      <?php if ($gorselSrc): ?>
        <img class="item-thumb" src="<?php echo htmlspecialchars($gorselSrc); ?>" alt="" loading="lazy">
      <?php else: ?>
        <div class="item-thumb-empty">🍽</div>
      <?php endif; ?>
      <div class="item-body">
        <div class="item-name"><?php echo htmlspecialchars($d["urun_adi"]); ?></div>
        <div class="item-meta">
          <span class="item-adet"><?php echo $d["adet"]; ?>×</span>
          <?php echo number_format($d["fiyat"],2,',','.'); ?> ₺ / adet
        </div>
      </div>
      <div class="item-price"><?php echo number_format($d["adet"]*$d["fiyat"],2,',','.'); ?> ₺</div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── Ek Ücretler ── -->
  <?php if (count($ek_arr) > 0): ?>
  <div class="sec-header" style="margin-top:28px">
    <div class="sec-title">Ek Ücretler</div>
    <span class="sec-count"><?php echo count($ek_arr); ?> kalem</span>
  </div>
  <div class="item-list">
    <?php foreach ($ek_arr as $e): ?>
    <div class="ek-card">
      <div class="ek-icon">➕</div>
      <div class="ek-name"><?php echo htmlspecialchars($e["aciklama"]); ?></div>
      <div class="ek-price"><?php echo number_format($e["tutar"],2,',','.'); ?> ₺</div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── Toplam özet ── -->
  <div class="total-box">
    <div class="total-box-label">💰 Genel Toplam</div>
    <div class="total-box-amount"><?php echo number_format($adisyon["toplam_tutar"],2,',','.'); ?> ₺</div>
  </div>

</div>

<script src="/svimages.js"></script>
</body>
</html>
