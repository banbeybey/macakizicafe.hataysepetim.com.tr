<?php
require_once "db.php";

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
    --bg:#111827;
    --card:#1e293b;
    --card2:#273449;
    --red:#ff214f;
    --blue:#18d8ff;
    --green:#22f58a;
    --text:#ffffff;
    --muted:#d1d5db;
}

body{
    margin:0;
    font-family:Arial, Helvetica, sans-serif;
    color:var(--text);
    background:
        radial-gradient(circle at 15% 0%, rgba(255,33,79,.48), transparent 30%),
        radial-gradient(circle at 85% 8%, rgba(24,216,255,.44), transparent 32%),
        radial-gradient(circle at 50% 100%, rgba(255,255,255,.08), transparent 45%),
        linear-gradient(180deg,#111827 0%,#172033 50%,#101827 100%);
    min-height:100vh;
}

.header{
    width:100%;
    padding:20px 16px 14px;
    display:flex;
    justify-content:center;
    align-items:center;
    position:relative;
    overflow:hidden;
    background:
        radial-gradient(circle at 18% 45%, rgba(255,0,64,.95), transparent 22%),
        radial-gradient(circle at 82% 45%, rgba(255,0,64,.72), transparent 24%),
        radial-gradient(circle at 50% 0%, rgba(255,255,255,.12), transparent 30%),
        linear-gradient(135deg,#050505 0%,#18020a 38%,#020203 70%,#24030c 100%);
    border-bottom:1px solid rgba(255,33,79,.42);
    box-shadow:
        inset 0 -18px 50px rgba(255,0,64,.12),
        0 0 46px rgba(255,0,64,.25);
}

.header:before{
    content:"";
    position:absolute;
    inset:-40%;
    background:
        conic-gradient(from 20deg, transparent 0 14%, rgba(255,0,64,.36) 17%, transparent 23% 40%, rgba(255,255,255,.12) 43%, transparent 50% 68%, rgba(255,0,64,.26) 72%, transparent 80% 100%);
    filter:blur(18px);
    opacity:.9;
    animation:neonSpin 9s linear infinite;
}

.header:after{
    content:"";
    position:absolute;
    left:0;
    right:0;
    bottom:0;
    height:3px;
    background:linear-gradient(90deg,transparent,rgba(255,0,64,1),rgba(255,255,255,.85),rgba(255,0,64,1),transparent);
    box-shadow:0 0 18px rgba(255,0,64,.95),0 0 34px rgba(255,0,64,.58);
}

.header img{
    width:100%;
    max-width:760px;
    height:auto;
    display:block;
    object-fit:contain;
    position:relative;
    z-index:2;
    filter:
        drop-shadow(0 0 18px rgba(255,0,64,.95))
        drop-shadow(0 0 36px rgba(255,255,255,.22))
        brightness(1.18) contrast(1.08);
}

@keyframes neonSpin{
    from{transform:rotate(0deg) scale(1.05)}
    to{transform:rotate(360deg) scale(1.05)}
}

.container{
    padding:16px 18px 34px;
    max-width:1450px;
    margin:0 auto;
}

.grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(190px,1fr));
    gap:18px;
}

.masa-card{
    position:relative;
    min-height:265px;
    overflow:hidden;
    border-radius:26px;
    background:
        linear-gradient(145deg,rgba(255,255,255,.22),rgba(255,255,255,.07)),
        linear-gradient(160deg,var(--card),var(--card2));
    border:1px solid rgba(255,255,255,.22);
    box-shadow:0 14px 30px rgba(0,0,0,.28),0 0 22px rgba(24,216,255,.10);
    transition:.25s ease;
    isolation:isolate;
}

.masa-card:before{
    content:"";
    position:absolute;
    inset:-2px;
    border-radius:28px;
    padding:1px;
    background:linear-gradient(135deg,rgba(24,216,255,.75),rgba(255,33,79,.65),rgba(255,255,255,.12));
    -webkit-mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0);
    -webkit-mask-composite:xor;
    mask-composite:exclude;
    pointer-events:none;
    opacity:.85;
    z-index:2;
}

.masa-card:hover{
    transform:translateY(-4px);
    box-shadow:0 18px 40px rgba(0,0,0,.45),0 0 24px rgba(24,216,255,.14);
}

.masa-card:after{
    content:"";
    position:absolute;
    width:160px;
    height:160px;
    right:-60px;
    top:-60px;
    background:radial-gradient(circle,rgba(24,216,255,.20),transparent 65%);
    z-index:0;
}

.dolu:after{
    background:radial-gradient(circle,rgba(255,33,79,.24),transparent 65%);
}

.card-top{
    position:relative;
    z-index:3;
    padding:14px 14px 0;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.masa-label{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:12px;
    color:#f1f5f9;
    letter-spacing:.7px;
    font-weight:700;
    text-transform:uppercase;
}

.masa-label span{
    width:8px;
    height:8px;
    border-radius:50%;
    background:var(--blue);
    box-shadow:0 0 12px var(--blue);
}

.dolu .masa-label span{
    background:var(--red);
    box-shadow:0 0 12px var(--red);
}

.badge{
    padding:7px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:900;
    letter-spacing:.5px;
}

.bos .badge{
    color:#07130d;
    background:linear-gradient(135deg,#7dffb9,var(--green));
    box-shadow:0 0 18px rgba(34,245,138,.35);
}

.dolu .badge{
    color:#fff;
    background:linear-gradient(135deg,#ff5c7a,var(--red));
    box-shadow:0 0 18px rgba(255,33,79,.35);
}

.table-wrapper{
    position:relative;
    z-index:3;
    height:150px;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:6px 10px 0;
}

.table-wrapper:before{
    content:"";
    position:absolute;
    width:72%;
    height:18px;
    bottom:10px;
    left:14%;
    border-radius:50%;
    background:rgba(0,0,0,.28);
    filter:blur(10px);
    z-index:-1;
}

.table-image{
    width:100%;
    max-width:175px;
    height:140px;
    object-fit:contain;
    filter:drop-shadow(0 10px 14px rgba(0,0,0,.30)) brightness(1.10) contrast(1.05);
}

.masa-info{
    position:relative;
    z-index:3;
    padding:0 16px 16px;
    text-align:center;
}

.masa-no{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:118px;
    height:50px;
    padding:0 16px;
    border-radius:18px;
    font-size:22px;
    font-weight:900;
    letter-spacing:.8px;
    color:#ffffff;
    background:linear-gradient(135deg,rgba(24,216,255,.34),rgba(255,255,255,.14));
    border:1px solid rgba(24,216,255,.58);
    box-shadow:inset 0 0 20px rgba(255,255,255,.12),0 0 24px rgba(24,216,255,.24);
    text-shadow:0 0 14px rgba(24,216,255,.45);
}

.dolu .masa-no{
    text-shadow:0 0 14px rgba(255,33,79,.45);
}

.masa-text{
    margin-top:9px;
    color:var(--muted);
    font-size:13px;
    font-weight:600;
}

.bos .status-line{
    background:linear-gradient(90deg,transparent,var(--green),transparent);
}

.dolu .status-line{
    background:linear-gradient(90deg,transparent,var(--red),transparent);
}

.status-line{
    position:absolute;
    left:12%;
    right:12%;
    bottom:0;
    height:3px;
    opacity:.9;
    z-index:4;
}

@media (max-width:768px){
    .header{padding:14px 10px 10px}
    .header img{max-width:520px}
    .container{padding:10px 8px 24px}
    .grid{grid-template-columns:repeat(3,1fr);gap:8px}
    .masa-card{min-height:175px;border-radius:17px}
    .masa-card:before{border-radius:19px}
    .card-top{padding:8px 8px 0}
    .masa-label{font-size:9px;gap:5px}
    .masa-label span{width:6px;height:6px}
    .badge{font-size:9px;padding:5px 7px}
    .table-wrapper{height:92px;padding:2px 4px 0}
    .table-image{max-width:105px;height:86px}
    .masa-info{padding:0 7px 10px}
    .masa-no{min-width:74px;height:34px;border-radius:12px;font-size:13px;padding:0 8px;letter-spacing:.5px}
    .masa-text{font-size:10px;margin-top:5px}
}

@media (max-width:390px){
    .grid{grid-template-columns:repeat(3,1fr);gap:7px}
    .masa-card{min-height:165px}
    .table-image{max-width:98px;height:80px}
}
</style>
</head>

<body>

<div class="header">
    <img src="/uploads/masalar/macabanner.png?v=<?php echo file_exists($_SERVER["DOCUMENT_ROOT"]."/uploads/masalar/macabanner.png") ? filemtime($_SERVER["DOCUMENT_ROOT"]."/uploads/masalar/macabanner.png") : time(); ?>" alt="Maça Kızı">
</div>

<div class="container">
    <div class="grid">

<?php while($masa = $masalar->fetch_assoc()): ?>
<?php
    $durum = $masa["durum"];
    $masaNo = (int)$masa["masa_no"];
    $gorsel = in_array($masaNo, [1,2,3,4])
        ? "/uploads/masalar/locamasa.png"
        : "/uploads/masalar/normalmasa.png";

    $gorselDosya = $_SERVER["DOCUMENT_ROOT"] . $gorsel;
    $gorselVersiyon = file_exists($gorselDosya) ? filemtime($gorselDosya) : time();
    $gorselSrc = $gorsel . "?v=" . $gorselVersiyon;
?>

        <div class="masa-card <?php echo htmlspecialchars($durum); ?>">
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
 <script src="/svimages.js"></script>
</body>
</html>
