<?php
require_once "db.php";
$urunler = $conn->query("SELECT * FROM urunler WHERE aktif=1 ORDER BY kategori, urun_adi");

$kategoriler = [];
while ($u = $urunler->fetch_assoc()) {
    $kategoriler[$u["kategori"]][] = $u;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Menü — Maça Kızı</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#f2f2f2;font-family:'DM Sans',sans-serif;min-height:100vh}

/* ── Banner ─────────────────────────────────────────── */
.mh{
  width:100%;padding:14px 0 6px;
  display:flex;justify-content:center;align-items:center;
  position:relative;overflow:hidden;
  background:
    radial-gradient(circle at 20% 30%,rgba(255,0,70,.22),transparent 33%),
    radial-gradient(circle at 82% 35%,rgba(0,190,255,.18),transparent 34%),
    linear-gradient(180deg,#061016,#0b1118);
  border-bottom:1px solid rgba(255,255,255,.18);
}
.mh:after{
  content:"";position:absolute;left:0;right:0;bottom:0;height:3px;
  background:linear-gradient(90deg,transparent,rgba(255,42,86,.95),rgba(255,255,255,.85),rgba(16,200,238,.95),transparent);
}
.banner-box{
  width:100%;max-width:1050px;margin:0 14px;
  background:#000;border-radius:12px;
  box-shadow:0 0 22px rgba(255,45,86,.25),0 0 34px rgba(255,190,70,.14);
}
.banner-box img{
  width:100%;height:auto;max-height:210px;display:block;
  object-fit:contain;border-radius:12px;
  filter:brightness(1.08) contrast(1.05);
}

/* ── Sticky kategori nav ─────────────────────────────── */
.kat-nav{
  position:sticky;top:0;z-index:100;
  background:rgba(245,244,240,.96);
  backdrop-filter:blur(10px);
  border-bottom:1px solid rgba(15,23,42,.1);
}
.kat-nav-inner{
  display:flex;gap:4px;overflow-x:auto;scrollbar-width:none;
  padding:10px 16px;
}
.kat-nav-inner::-webkit-scrollbar{display:none}
.kat-btn{
  flex-shrink:0;font-size:12px;font-weight:600;
  color:#64748b;background:transparent;
  border:1px solid transparent;border-radius:100px;
  padding:6px 14px;cursor:pointer;transition:.15s;
  white-space:nowrap;text-decoration:none;
}
.kat-btn:hover{color:#0f172a;background:rgba(15,23,42,.06)}
.kat-btn.active{color:#c8102e;background:rgba(200,16,46,.08);border-color:rgba(200,16,46,.22)}

/* ── Ana içerik ──────────────────────────────────────── */
.menu-page{max-width:1280px;margin:0 auto;padding:28px 16px 64px}

/* ── Kategori başlığı ────────────────────────────────── */
.kat-section{margin-bottom:36px;scroll-margin-top:60px}
.kat-header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.kat-header-title{
  font-family:'Playfair Display',serif;
  font-size:20px;font-weight:700;color:#1a1008;white-space:nowrap;
}
.kat-header-sep{flex:1;height:1px;background:linear-gradient(90deg,rgba(180,140,60,.3),transparent)}
.kat-header-count{
  font-size:11px;font-weight:600;color:#a08040;
  background:rgba(212,168,83,.12);border-radius:100px;padding:3px 9px;
}

/* ── Grid: masaüstü 6, mobil 4 sütun ────────────────── */
.urun-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}
@media(max-width:600px){.urun-grid{grid-template-columns:repeat(4,1fr)}}

/* ── Kart ────────────────────────────────────────────── */
.urun-kart{
  background:#fff;
  border-radius:16px;
  overflow:hidden;
  display:flex;flex-direction:column;
  box-shadow:0 2px 12px rgba(15,23,42,.09);
  transition:box-shadow .2s,transform .2s;
}
.urun-kart:hover{transform:translateY(-3px);box-shadow:0 8px 28px rgba(15,23,42,.14)}

.urun-img-wrap{
  width:100%;aspect-ratio:1/1;
  overflow:hidden;background:#f7f3ee;flex-shrink:0;
}
.urun-img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s}
.urun-kart:hover .urun-img{transform:scale(1.05)}
.urun-img-empty{
  width:100%;height:100%;display:flex;align-items:center;justify-content:center;
  font-size:28px;background:#f7f3ee;
}

.urun-body{padding:10px 12px 13px;display:flex;flex-direction:column;gap:2px}
.urun-cat{
  font-size:11px;font-weight:500;color:#94a3b8;
  text-transform:uppercase;letter-spacing:.5px;
}
.urun-name{
  font-size:13px;font-weight:700;color:#0f172a;line-height:1.3;
}
.urun-price{
  font-family:'Playfair Display',serif;
  font-size:15px;font-weight:700;color:#c8102e;margin-top:4px;
}

/* ── Footer ──────────────────────────────────────────── */
.menu-footer{
  text-align:center;padding:24px 16px;
  border-top:1px solid rgba(15,23,42,.07);
  font-size:12px;color:#94a3b8;
}
.menu-footer b{color:#b8892e;font-weight:600}

/* ── Mobil ───────────────────────────────────────────── */
@media(max-width:600px){
  .mh{padding:10px 0 5px}
  .banner-box{border-radius:8px;margin:0 8px}
  .banner-box img{max-height:90px}
  .menu-page{padding:16px 10px 48px}
  .urun-grid{gap:7px}
  .urun-body{padding:7px 8px 9px}
  .urun-name{font-size:11px}
  .urun-price{font-size:13px}
  .kat-header-title{font-size:16px}
  .kat-section{margin-bottom:24px}
  .kat-header{margin-bottom:10px}
}
</style>
</head>
<body>

<div class="mh">
  <div class="banner-box">
    <img src="/uploads/masalar/macabanner.png?v=<?php echo file_exists($_SERVER["DOCUMENT_ROOT"]."/uploads/masalar/macabanner.png") ? filemtime($_SERVER["DOCUMENT_ROOT"]."/uploads/masalar/macabanner.png") : time(); ?>" alt="Maça Kızı">
  </div>
</div>

<nav class="kat-nav">
  <div class="kat-nav-inner">
    <a class="kat-btn active" href="#" data-kat="__all">Tümü</a>
    <?php foreach ($kategoriler as $kat => $items): ?>
    <a class="kat-btn" href="#kat-<?php echo urlencode($kat); ?>" data-kat="<?php echo htmlspecialchars($kat); ?>">
      <?php echo htmlspecialchars($kat); ?>
    </a>
    <?php endforeach; ?>
  </div>
</nav>

<main class="menu-page">
  <?php foreach ($kategoriler as $kat => $items): ?>
  <section class="kat-section" id="kat-<?php echo urlencode($kat); ?>" data-kat="<?php echo htmlspecialchars($kat); ?>">
    <div class="kat-header">
      <div class="kat-header-title"><?php echo htmlspecialchars($kat); ?></div>
      <div class="kat-header-sep"></div>
      <span class="kat-header-count"><?php echo count($items); ?></span>
    </div>
    <div class="urun-grid">
      <?php foreach ($items as $u): ?>
      <div class="urun-kart">
        <div class="urun-img-wrap">
          <?php if (!empty($u["gorsel"])): ?>
            <img class="urun-img" src="<?php echo htmlspecialchars($u["gorsel"]); ?>" alt="<?php echo htmlspecialchars($u["urun_adi"]); ?>" loading="lazy">
          <?php else: ?>
            <div class="urun-img-empty">🍽</div>
          <?php endif; ?>
        </div>
        <div class="urun-body">
          <div class="urun-name"><?php echo htmlspecialchars($u["urun_adi"]); ?></div>
          <div class="urun-price"><?php echo number_format($u["fiyat"],2,',','.'); ?> ₺</div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endforeach; ?>
</main>

<div class="menu-footer">
  <b>Maça Kızı</b> — Cafe &amp; Oyun Salonu · Fiyatlara KDV dahildir.
</div>

<script>
const sections = document.querySelectorAll(".kat-section");
const btns = document.querySelectorAll(".kat-btn[data-kat]");

const io = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      const kat = e.target.dataset.kat;
      btns.forEach(b => b.classList.toggle("active", b.dataset.kat === kat));
      document.querySelector(`.kat-btn[data-kat="${CSS.escape(kat)}"]`)
        ?.scrollIntoView({block:"nearest",inline:"center",behavior:"smooth"});
    }
  });
}, {rootMargin:"-20% 0px -70% 0px"});

sections.forEach(s => io.observe(s));

document.querySelector('.kat-btn[data-kat="__all"]').addEventListener("click", e => {
  e.preventDefault();
  window.scrollTo({top:0,behavior:"smooth"});
  btns.forEach(b => b.classList.remove("active"));
  e.target.classList.add("active");
});
</script>
<script src="/svimages.js"></script>
</body>
</html>
