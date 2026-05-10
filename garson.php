<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "garson") {
    header("Location: login.php"); exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (isset($_GET["ajax"])) {
    header("Content-Type: application/json; charset=utf-8");
    $q = $conn->query("SELECT id, masa_no, durum FROM masalar ORDER BY masa_no ASC");
    $data = [];
    while ($r = $q->fetch_assoc()) {
        $masaNo = (int)$r["masa_no"];
        $durum  = $r["durum"];
        $gorsel = in_array($masaNo, [1,2,3,4])
            ? "/uploads/masalar/locamasa.png"
            : "/uploads/masalar/normalmasa.png";
        $gorselDosya    = $_SERVER["DOCUMENT_ROOT"] . $gorsel;
        $gorselVersiyon = file_exists($gorselDosya) ? filemtime($gorselDosya) : time();
        $data[] = [
            "id"     => (int)$r["id"],
            "masa_no"=> $masaNo,
            "durum"  => $durum,
            "badge"  => $durum === "bos" ? "BOŞ" : "DOLU",
            "text"   => $durum === "bos" ? "Kullanıma hazır" : "Şu anda dolu",
            "gorsel" => $gorsel . "?v=" . $gorselVersiyon,
        ];
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$masalar = $conn->query("SELECT id, masa_no, durum FROM masalar ORDER BY masa_no ASC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Garson Paneli — Maça Kızı</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}
:root{
    --cyan:#10c8ee;--cyan2:#36e4ff;--green:#24e88a;--red:#ff3b55;
    --text:#1f2937;--muted:#64748b;--line:rgba(51,65,85,.13);
}
body{
    margin:0;font-family:'DM Sans',Arial,sans-serif;color:var(--text);min-height:100vh;
    background:radial-gradient(circle at 12% 6%,rgba(255,44,92,.20),transparent 28%),
               radial-gradient(circle at 88% 2%,rgba(28,200,238,.25),transparent 30%),
               linear-gradient(180deg,#f5fbff 0%,#edf7ff 46%,#f8fbff 100%);
}
a{text-decoration:none;color:inherit}
.topnav{
    position:sticky;top:0;z-index:50;min-height:70px;
    display:flex;align-items:center;justify-content:space-between;gap:18px;padding:12px 22px;
    background:rgba(255,255,255,.88);backdrop-filter:blur(14px);
    border-bottom:1px solid rgba(51,65,85,.12);box-shadow:0 10px 24px rgba(15,23,42,.08);
}
.brand{display:flex;align-items:center;gap:12px}
.brand-icon{width:44px;height:44px;border-radius:15px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:25px;background:linear-gradient(135deg,#ff1744,#0f172a);box-shadow:0 10px 22px rgba(255,23,68,.25)}
.brand-name{font-family:'Playfair Display',serif;font-size:23px;font-weight:700;color:#111827;line-height:1}
.brand-sub{font-size:12px;color:var(--muted);font-weight:800;letter-spacing:.7px;margin-top:4px;text-transform:uppercase}
.nav-right{display:flex;align-items:center;gap:10px}
.nav-user{font-size:13px;font-weight:800;color:#334155;background:#fff;border:1px solid var(--line);padding:10px 13px;border-radius:14px}
.btn-ghost{font-size:13px;font-weight:900;color:#fff;background:linear-gradient(135deg,#ff3b55,#b40016);padding:10px 14px;border-radius:14px;box-shadow:0 10px 20px rgba(255,59,85,.22)}
.page{width:100%;max-width:1450px;margin:0 auto;padding:18px 18px 40px}
.section-title{font-size:24px;font-weight:950;color:#0f172a;margin:6px 0 4px;letter-spacing:.2px}
.section-sub{font-size:13px;color:var(--muted);font-weight:600;margin-bottom:16px}
.masalar-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(195px,1fr));gap:18px}
.masa-card{
    position:relative;min-height:272px;overflow:hidden;border-radius:26px;display:block;
    background:linear-gradient(180deg,rgba(255,255,255,.94),rgba(245,251,255,.84)),
               radial-gradient(circle at 22% 15%,rgba(54,228,255,.22),transparent 40%),
               radial-gradient(circle at 88% 18%,rgba(255,59,85,.12),transparent 38%);
    border:1px solid rgba(51,65,85,.14);
    box-shadow:0 16px 32px rgba(15,23,42,.13),0 2px 0 rgba(255,255,255,.85) inset;
    transition:.22s ease;isolation:isolate;
}
.masa-card:before{content:"";position:absolute;inset:0;border-radius:26px;background:linear-gradient(135deg,rgba(255,255,255,.70),transparent 38%,rgba(16,200,238,.10));pointer-events:none;z-index:1}
.masa-card:after{content:"";position:absolute;left:18px;right:18px;bottom:13px;height:4px;border-radius:999px;background:linear-gradient(90deg,transparent,var(--green),var(--cyan2),transparent);box-shadow:0 0 14px rgba(36,232,138,.38);z-index:5}
.masa-card.dolu:after{background:linear-gradient(90deg,transparent,var(--red),#ff9aa8,transparent);box-shadow:0 0 14px rgba(255,59,85,.32)}
.masa-card:hover{transform:translateY(-4px);border-color:rgba(16,200,238,.42);box-shadow:0 20px 42px rgba(15,23,42,.18),0 0 24px rgba(16,200,238,.12)}
.card-top{position:relative;z-index:3;padding:14px 14px 0;display:flex;justify-content:space-between;align-items:flex-start;gap:8px}
.masa-label{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:14px;background:rgba(255,255,255,.72);border:1px solid rgba(51,65,85,.12);font-size:11px;color:#334155;letter-spacing:.9px;font-weight:950;line-height:1.05;text-transform:uppercase;box-shadow:0 8px 18px rgba(15,23,42,.08)}
.masa-label span{width:7px;height:7px;border-radius:50%;background:var(--cyan);box-shadow:0 0 10px var(--cyan)}
.dolu .masa-label span{background:var(--red);box-shadow:0 0 10px var(--red)}
.masa-badge{min-width:58px;text-align:center;padding:8px 10px;border-radius:15px;font-size:12px;font-weight:950;letter-spacing:.5px;border:1px solid rgba(255,255,255,.55);box-shadow:0 8px 18px rgba(15,23,42,.12)}
.bos .masa-badge{color:#06351e;background:linear-gradient(135deg,#c7ffe0,#38f49a)}
.dolu .masa-badge{color:#fff;background:linear-gradient(135deg,#ff8798,var(--red))}
.table-wrapper{position:relative;z-index:3;height:148px;display:flex;align-items:center;justify-content:center;padding:4px 10px 0}
.table-wrapper:before{content:"";position:absolute;width:72%;height:26px;bottom:12px;left:14%;border-radius:50%;background:rgba(30,41,59,.18);filter:blur(12px);z-index:-1}
.table-image{width:100%;max-width:178px;height:142px;object-fit:contain;filter:drop-shadow(0 12px 12px rgba(15,23,42,.20)) brightness(1.08) contrast(1.04)}
.masa-info{position:relative;z-index:3;padding:0 14px 18px;text-align:center}
.masa-no{display:inline-flex;align-items:center;justify-content:center;min-width:126px;height:48px;padding:0 15px;border-radius:17px;font-size:21px;font-weight:950;letter-spacing:1px;color:#0f172a;background:linear-gradient(180deg,#ffffff,#e7f7ff);border:1px solid rgba(16,200,238,.62);box-shadow:inset 0 0 18px rgba(255,255,255,.65),0 8px 20px rgba(16,200,238,.14)}
.bos .masa-no{color:#fff;background:linear-gradient(135deg,#22c55e,#0f8f3f);border-color:rgba(255,255,255,.70);text-shadow:0 2px 8px rgba(0,0,0,.30);box-shadow:inset 0 0 18px rgba(255,255,255,.18),0 0 22px rgba(34,197,94,.46),0 10px 24px rgba(15,143,63,.25)}
.dolu .masa-no{color:#fff;background:linear-gradient(135deg,#ff1744,#b40016);border-color:rgba(255,255,255,.70);text-shadow:0 2px 8px rgba(0,0,0,.35);box-shadow:inset 0 0 18px rgba(255,255,255,.18),0 0 22px rgba(255,23,68,.48),0 10px 24px rgba(180,0,22,.28)}
.masa-text{margin-top:9px;color:var(--muted);font-size:13px;font-weight:800}
@media(max-width:768px){
    body{background:linear-gradient(180deg,#eef8ff 0%,#f8fbff 100%)}
    .topnav{position:relative;padding:10px 12px;min-height:auto}
    .brand-icon{width:38px;height:38px;border-radius:13px}.brand-name{font-size:19px}.brand-sub{font-size:10px}
    .nav-user{display:none}.btn-ghost{padding:9px 11px;font-size:12px}
    .page{padding:10px 8px 24px}
    .section-title{font-size:18px;margin:12px 2px 4px}.section-sub{font-size:11px;margin-bottom:12px}
    .masalar-grid{grid-template-columns:repeat(3,1fr);gap:8px}
    .masa-card{min-height:172px;border-radius:18px}.masa-card:before{border-radius:18px}
    .masa-card:after{left:14px;right:14px;bottom:7px;height:3px}
    .card-top{padding:7px 7px 0}
    .masa-label{font-size:8px;gap:4px;padding:5px 6px;border-radius:10px;letter-spacing:.5px}.masa-label span{width:5px;height:5px}
    .masa-badge{min-width:auto;font-size:9px;padding:5px 7px;border-radius:10px}
    .table-wrapper{height:86px;padding:2px 4px 0}.table-image{max-width:104px;height:82px}
    .masa-info{padding:0 6px 12px}
    .masa-no{min-width:76px;height:32px;border-radius:11px;font-size:13px;padding:0 7px;letter-spacing:.4px}
    .masa-text{font-size:9px;margin-top:5px}
}
@media(max-width:390px){.masalar-grid{grid-template-columns:repeat(3,1fr);gap:7px}.masa-card{min-height:164px}.table-image{max-width:96px;height:78px}}
</style>
</head>
<body>

<nav class="topnav">
  <div class="brand">
    <div class="brand-icon">&#9824;</div>
    <div>
      <div class="brand-name">Ma&#231;a K&#305;z&#305;</div>
      <div class="brand-sub">Garson Paneli</div>
    </div>
  </div>
  <div class="nav-right">
    <span class="nav-user">&#128075; <?php echo htmlspecialchars($_SESSION["ad"]); ?></span>
    <a class="btn-ghost" href="logout.php">&#199;&#305;k&#305;&#351;</a>
  </div>
</nav>

<div class="page">
  <div class="section-title">Masalar</div>
  <div class="section-sub">Masa se&#231;erek adisyon a&#231;&#305;n veya devam edin</div>

  <div class="masalar-grid" id="masalarGrid">
    <?php while($m = $masalar->fetch_assoc()):
      $durum   = $m["durum"];
      $masaNo  = (int)$m["masa_no"];
      $gorsel  = in_array($masaNo, [1,2,3,4]) ? "/uploads/masalar/locamasa.png" : "/uploads/masalar/normalmasa.png";
      $gorselDosya    = $_SERVER["DOCUMENT_ROOT"] . $gorsel;
      $gorselVersiyon = file_exists($gorselDosya) ? filemtime($gorselDosya) : time();
      $gorselSrc      = $gorsel . "?v=" . $gorselVersiyon;
    ?>
    <a class="masa-card <?php echo htmlspecialchars($durum); ?>"
       href="masa.php?id=<?php echo (int)$m['id']; ?>"
       data-masa-no="<?php echo $masaNo; ?>">
      <div class="card-top">
        <div class="masa-label"><span></span> Canl&#305; Durum</div>
        <div class="masa-badge"><?php echo $durum == "bos" ? "BO&#350;" : "DOLU"; ?></div>
      </div>
      <div class="table-wrapper">
        <img class="table-image" src="<?php echo $gorselSrc; ?>" alt="Masa <?php echo $masaNo; ?>">
      </div>
      <div class="masa-info">
        <div class="masa-no">MASA <?php echo str_pad($masaNo, 2, "0", STR_PAD_LEFT); ?></div>
        <div class="masa-text"><?php echo $durum == "bos" ? "Kullan&#305;ma haz&#305;r" : "&#350;u anda dolu"; ?></div>
      </div>
    </a>
    <?php endwhile; ?>
  </div>
</div>

<script>
(function(){
    const endpoint = window.location.pathname + '?ajax=1';
    function setCard(card, item){
        const old = card.classList.contains('dolu') ? 'dolu' : 'bos';
        if(old !== item.durum){ card.classList.remove('bos','dolu'); card.classList.add(item.durum); }
        const badge = card.querySelector('.masa-badge');
        const text  = card.querySelector('.masa-text');
        const img   = card.querySelector('.table-image');
        if(badge && badge.textContent.trim() !== item.badge) badge.textContent = item.badge;
        if(text  && text.textContent.trim()  !== item.text)  text.textContent  = item.text;
        if(img   && item.gorsel && img.getAttribute('src') !== item.gorsel) img.setAttribute('src', item.gorsel);
    }
    async function guncelle(){
        try{
            const res  = await fetch(endpoint + '&_=' + Date.now(), {cache:'no-store'});
            if(!res.ok) return;
            const data = await res.json();
            data.forEach(item => {
                const card = document.querySelector('.masa-card[data-masa-no="' + item.masa_no + '"]');
                if(card) setCard(card, item);
            });
        }catch(e){}
    }
    guncelle();
    setInterval(guncelle, 2000);
})();
</script>

</body>
</html>
