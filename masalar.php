<?php
require_once "db.php";

if (isset($_GET["ajax"])) {
    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

    $q = $conn->query("SELECT masa_no, durum FROM masalar ORDER BY masa_no ASC");
    $data = [];

    while ($r = $q->fetch_assoc()) {
        $masaNo = (int)$r["masa_no"];
        $durum = $r["durum"];
        if (in_array($masaNo, [1,2,3,4])) {
            $gorsel = ($durum === "dolu")
                ? "/uploads/masalar/dolulocamasa.png"
                : "/uploads/masalar/locamasa.png";
        } else {
            $gorsel = ($durum === "dolu")
                ? "/uploads/masalar/dolunormalmasa.png"
                : "/uploads/masalar/normalmasa.png";
        }

        $gorselDosya = $_SERVER["DOCUMENT_ROOT"] . $gorsel;
        $gorselVersiyon = file_exists($gorselDosya) ? filemtime($gorselDosya) : time();

        $data[] = [
            "masa_no" => $masaNo,
            "durum" => $durum,
            "badge" => $durum === "bos" ? "BOŞ" : "DOLU",
            "text" => $durum === "bos" ? "Kullanıma hazır" : "Şu anda dolu",
            "gorsel" => $gorsel . "?v=" . $gorselVersiyon
        ];
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$masalar = $conn->query("SELECT masa_no, durum FROM masalar ORDER BY masa_no ASC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Maça Kızı Masa Durumu</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{box-sizing:border-box}

:root{
    --bg1:#eef7ff;
    --bg2:#f7fbff;
    --card:#ffffff;
    --glass:rgba(255,255,255,.78);
    --line:rgba(51,65,85,.13);
    --cyan:#10c8ee;
    --cyan2:#36e4ff;
    --green:#24e88a;
    --red:#ff3b55;
    --text:#1f2937;
    --muted:#64748b;
}

body{
    margin:0;
    font-family:Arial, Helvetica, sans-serif;
    color:var(--text);
    background:
        radial-gradient(circle at 12% 6%, rgba(255,44,92,.20), transparent 28%),
        radial-gradient(circle at 88% 2%, rgba(28,200,238,.25), transparent 30%),
        linear-gradient(180deg,#f5fbff 0%,#edf7ff 46%,#f8fbff 100%);
    min-height:100vh;
}

.header{
    width:100%;
    padding:18px 0 8px;
    display:flex;
    justify-content:center;
    align-items:center;
    position:relative;
    overflow:hidden;
    background:
        radial-gradient(circle at 20% 30%, rgba(255,0,70,.22), transparent 33%),
        radial-gradient(circle at 82% 35%, rgba(0,190,255,.18), transparent 34%),
        linear-gradient(180deg,#061016 0%,#0b1118 100%);
    border-bottom:1px solid rgba(255,255,255,.18);
    box-shadow:0 12px 30px rgba(15,23,42,.14);
}

.header:after{
    content:"";
    position:absolute;
    left:0;
    right:0;
    bottom:0;
    height:3px;
    background:linear-gradient(90deg,transparent,rgba(255,42,86,.95),rgba(255,255,255,.85),rgba(16,200,238,.95),transparent);
    box-shadow:0 0 18px rgba(255,42,86,.45);
}

.banner-box{
    width:100%;
    max-width:1050px;
    background:#000;
    border-radius:12px;
    padding:0;
    margin:0 14px;
    position:relative;
    z-index:2;
    box-shadow:0 0 22px rgba(255,45,86,.25),0 0 34px rgba(255,190,70,.14);
}

.header img{
    width:100%;
    height:auto;
    max-height:210px;
    display:block;
    object-fit:contain;
    filter:brightness(1.08) contrast(1.05);
}

.container{
    padding:20px 18px 38px;
    max-width:1450px;
    margin:0 auto;
}

.grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(195px,1fr));
    gap:18px;
}

.masa-card{
    position:relative;
    min-height:272px;
    overflow:hidden;
    border-radius:26px;
    background:
        linear-gradient(180deg,rgba(255,255,255,.94),rgba(245,251,255,.84)),
        radial-gradient(circle at 22% 15%,rgba(54,228,255,.22),transparent 40%),
        radial-gradient(circle at 88% 18%,rgba(255,59,85,.12),transparent 38%);
    border:1px solid rgba(51,65,85,.14);
    box-shadow:
        0 16px 32px rgba(15,23,42,.13),
        0 2px 0 rgba(255,255,255,.85) inset;
    transition:.22s ease;
    isolation:isolate;
}

.masa-card:before{
    content:"";
    position:absolute;
    inset:0;
    border-radius:26px;
    background:linear-gradient(135deg,rgba(255,255,255,.70),transparent 38%,rgba(16,200,238,.10));
    pointer-events:none;
    z-index:1;
}

.masa-card:after{
    content:"";
    position:absolute;
    left:18px;
    right:18px;
    bottom:13px;
    height:4px;
    border-radius:999px;
    background:linear-gradient(90deg,transparent,var(--green),var(--cyan2),transparent);
    box-shadow:0 0 14px rgba(36,232,138,.38);
    z-index:5;
}

.masa-card.dolu:after{
    background:linear-gradient(90deg,transparent,var(--red),#ff9aa8,transparent);
    box-shadow:0 0 14px rgba(255,59,85,.32);
}

.masa-card:hover{
    transform:translateY(-4px);
    border-color:rgba(16,200,238,.42);
    box-shadow:0 20px 42px rgba(15,23,42,.18),0 0 24px rgba(16,200,238,.12);
}

.card-top{
    position:relative;
    z-index:3;
    padding:14px 14px 0;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
}

.masa-label{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:7px 10px;
    border-radius:14px;
    background:rgba(255,255,255,.72);
    border:1px solid rgba(51,65,85,.12);
    font-size:11px;
    color:#334155;
    letter-spacing:.9px;
    font-weight:900;
    line-height:1.05;
    text-transform:uppercase;
    box-shadow:0 8px 18px rgba(15,23,42,.08);
}

.masa-label span{
    width:7px;
    height:7px;
    border-radius:50%;
    background:var(--cyan);
    box-shadow:0 0 10px var(--cyan);
}

.dolu .masa-label span{
    background:var(--red);
    box-shadow:0 0 10px var(--red);
}

.badge{
    min-width:58px;
    text-align:center;
    padding:8px 10px;
    border-radius:15px;
    font-size:12px;
    font-weight:950;
    letter-spacing:.5px;
    border:1px solid rgba(255,255,255,.55);
    box-shadow:0 8px 18px rgba(15,23,42,.12);
}

.bos .badge{
    color:#06351e;
    background:linear-gradient(135deg,#c7ffe0,#38f49a);
}

.dolu .badge{
    color:#fff;
    background:linear-gradient(135deg,#ff8798,var(--red));
}

.table-wrapper{
    position:relative;
    z-index:3;
    height:148px;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:4px 10px 0;
}

.table-wrapper:before{
    content:"";
    position:absolute;
    width:72%;
    height:26px;
    bottom:12px;
    left:14%;
    border-radius:50%;
    background:rgba(30,41,59,.18);
    filter:blur(12px);
    z-index:-1;
}

.table-image{
    width:100%;
    max-width:178px;
    height:142px;
    object-fit:contain;
    filter:drop-shadow(0 12px 12px rgba(15,23,42,.20)) brightness(1.08) contrast(1.04);
}

.masa-info{
    position:relative;
    z-index:3;
    padding:0 14px 18px;
    text-align:center;
}

.masa-no{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:126px;
    height:48px;
    padding:0 15px;
    border-radius:17px;
    font-size:21px;
    font-weight:950;
    letter-spacing:1px;
    color:#0f172a;
    background:linear-gradient(180deg,#ffffff,#e7f7ff);
    border:1px solid rgba(16,200,238,.62);
    box-shadow:inset 0 0 18px rgba(255,255,255,.65),0 8px 20px rgba(16,200,238,.14);
}


.bos .masa-no{
    color:#ffffff;
    background:linear-gradient(135deg,#22c55e,#0f8f3f);
    border-color:rgba(255,255,255,.70);
    text-shadow:0 2px 8px rgba(0,0,0,.30);
    box-shadow:inset 0 0 18px rgba(255,255,255,.18),0 0 22px rgba(34,197,94,.46),0 10px 24px rgba(15,143,63,.25);
}

.dolu .masa-no{
    color:#ffffff;
    background:linear-gradient(135deg,#ff1744,#b40016);
    border-color:rgba(255,255,255,.70);
    text-shadow:0 2px 8px rgba(0,0,0,.35);
    box-shadow:inset 0 0 18px rgba(255,255,255,.18),0 0 22px rgba(255,23,68,.48),0 10px 24px rgba(180,0,22,.28);
}

.masa-text{
    margin-top:9px;
    color:var(--muted);
    font-size:13px;
    font-weight:800;
}

.status-line{display:none;}

@media (max-width:768px){
    body{background:linear-gradient(180deg,#eef8ff 0%,#f8fbff 100%)}
    .header{padding:10px 0 7px}
    .banner-box{max-width:100%;width:100%;border-radius:10px;margin:0 8px}
    .header img{max-height:132px}
    .container{padding:10px 8px 24px}
    .grid{grid-template-columns:repeat(3,1fr);gap:8px}
    .masa-card{min-height:172px;border-radius:18px}
    .masa-card:before{border-radius:18px}
    .masa-card:after{left:14px;right:14px;bottom:7px;height:3px}
    .card-top{padding:7px 7px 0}
    .masa-label{font-size:8px;gap:4px;padding:5px 6px;border-radius:10px;letter-spacing:.5px}
    .masa-label span{width:5px;height:5px}
    .badge{min-width:auto;font-size:9px;padding:5px 7px;border-radius:10px}
    .table-wrapper{height:86px;padding:2px 4px 0}
    .table-image{max-width:104px;height:82px}
    .masa-info{padding:0 6px 12px}
    .masa-no{min-width:76px;height:32px;border-radius:11px;font-size:13px;padding:0 7px;letter-spacing:.4px}
    .masa-text{font-size:9px;margin-top:5px}
}

@media (max-width:390px){
    .grid{grid-template-columns:repeat(3,1fr);gap:7px}
    .masa-card{min-height:164px}
    .table-image{max-width:96px;height:78px}
}
</style>
</head>

<body>

<div class="header">
    <div class="banner-box">
        <img src="/uploads/masalar/macabanner.png?v=<?php echo file_exists($_SERVER["DOCUMENT_ROOT"]."/uploads/masalar/macabanner.png") ? filemtime($_SERVER["DOCUMENT_ROOT"]."/uploads/masalar/macabanner.png") : time(); ?>" alt="Maça Kızı">
    </div>
</div>

<div class="container">
    <div class="grid">

<?php while($masa = $masalar->fetch_assoc()): ?>
<?php
    $durum = $masa["durum"];
    $masaNo = (int)$masa["masa_no"];
    if (in_array($masaNo, [1,2,3,4])) {
        $gorsel = ($durum === "dolu")
            ? "/uploads/masalar/dolulocamasa.png"
            : "/uploads/masalar/locamasa.png";
    } else {
        $gorsel = ($durum === "dolu")
            ? "/uploads/masalar/dolunormalmasa.png"
            : "/uploads/masalar/normalmasa.png";
    }

    $gorselDosya = $_SERVER["DOCUMENT_ROOT"] . $gorsel;
    $gorselVersiyon = file_exists($gorselDosya) ? filemtime($gorselDosya) : time();
    $gorselSrc = $gorsel . "?v=" . $gorselVersiyon;
?>

        <div class="masa-card <?php echo htmlspecialchars($durum); ?>" data-masa-no="<?php echo $masaNo; ?>">
            <div class="card-top">
                            <div class="masa-label"><span></span> Canlı Durum</div>
                <div class="badge"><?php echo $durum == "bos" ? "BOŞ" : "DOLU"; ?></div>
            </div>

            <div class="table-wrapper">
                <img class="table-image" src="<?php echo $gorselSrc; ?>" alt="Masa <?php echo $masaNo; ?>">
            </div>

            <div class="masa-info">
                <div class="masa-no">MASA <?php echo str_pad($masaNo, 2, "0", STR_PAD_LEFT); ?></div>
                <div class="masa-text"><?php echo $durum == "bos" ? "Kullanıma hazır" : "Şu anda dolu"; ?></div>
            </div>

            <div class="status-line"></div>
        </div>

<?php endwhile; ?>

    </div>
</div>

<script>
(function(){
    const endpoint = window.location.pathname + '?ajax=1';

    function setCard(card, item){
        const oldDurum = card.classList.contains('dolu') ? 'dolu' : 'bos';
        if(oldDurum !== item.durum){
            card.classList.remove('bos','dolu');
            card.classList.add(item.durum);
        }

        const badge = card.querySelector('.badge');
        const text = card.querySelector('.masa-text');
        const img = card.querySelector('.table-image');

        if(badge && badge.textContent.trim() !== item.badge){
            badge.textContent = item.badge;
        }
        if(text && text.textContent.trim() !== item.text){
            text.textContent = item.text;
        }
        if(img && item.gorsel && img.getAttribute('src') !== item.gorsel){
            img.setAttribute('src', item.gorsel);
        }
    }

    async function masaDurumuGuncelle(){
        try{
            const res = await fetch(endpoint + '&_=' + Date.now(), {
                cache: 'no-store',
                headers: {'X-Requested-With':'XMLHttpRequest'}
            });
            if(!res.ok) return;
            const data = await res.json();
            data.forEach(item => {
                const card = document.querySelector('.masa-card[data-masa-no="' + item.masa_no + '"]');
                if(card) setCard(card, item);
            });
        }catch(e){}
    }

    masaDurumuGuncelle();
    setInterval(masaDurumuGuncelle, 1000);
})();
</script>

</body>
</html>
