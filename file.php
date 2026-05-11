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
    background:linear-gradient(180deg,#f5fbff 0%,#edf7ff 46%,#f8fbff 100%);
    min-height:100vh;
}

.header{
    padding:18px 0 8px;
    display:flex;
    justify-content:center;
    align-items:center;
    background:linear-gradient(180deg,#061016 0%,#0b1118 100%);
    border-bottom:1px solid rgba(255,255,255,.18);
    box-shadow:0 12px 30px rgba(15,23,42,.14);
}

.banner-box img{
    width:100%;
    height:auto;
    max-height:210px;
    display:block;
    object-fit:contain;
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

/* --- MASA KARTLARI --- */
.masa-card{
    position:relative;
    min-height:320px; /* Eskisi 272px idi */
    overflow:hidden;
    border-radius:26px;
    background:linear-gradient(180deg,#ffffff,#eef7ff);
    border:1px solid rgba(51,65,85,.14);
    box-shadow:0 16px 32px rgba(15,23,42,.13);
    transition:.22s ease;
}

.masa-card:hover{
    transform:translateY(-4px);
    border-color:rgba(16,200,238,.42);
    box-shadow:0 20px 42px rgba(15,23,42,.18);
}

/* --- MASA GÖRSELLERİ (BÜYÜTÜLDÜ) --- */
.table-wrapper{
    height:200px; /* Eskisi 148px idi */
    display:flex;
    align-items:center;
    justify-content:center;
}

.table-image{
    width:100%;
    max-width:240px;   /* Eskisi 178px idi */
    height:200px;      /* Eskisi 142px idi */
    object-fit:contain;
    filter:drop-shadow(0 15px 15px rgba(15,23,42,.22)) brightness(1.1) contrast(1.07);
}

/* Mobil uyum */
@media (max-width:768px){
    .table-image{
        max-width:160px;
        height:140px;
    }
    .masa-card{
        min-height:220px;
    }
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
            <div class="table-wrapper">
                <img class="table-image" src="<?php echo $gorselSrc; ?>" alt="Masa <?php echo $masaNo; ?>">
            </div>
        </div>
<?php endwhile; ?>
    </div>
</div>

<script>
(function(){
    const endpoint = window.location.pathname + '?ajax=1';
    async function masaDurumuGuncelle(){
        try{
            const res = await fetch(endpoint + '&_=' + Date.now(), {cache:'no-store'});
            if(!res.ok) return;
            const data = await res.json();
            data.forEach(item => {
                const card = document.querySelector('.masa-card[data-masa-no="' + item.masa_no + '"]');
                if(!card) return;
                const img = card.querySelector('.table-image');
                if(img && item.gorsel && img.getAttribute('src') !== item.gorsel){
                    img.setAttribute('src', item.gorsel);
                }
            });
        }catch(e){}
    }
    masaDurumuGuncelle();
    setInterval(masaDurumuGuncelle, 1000);
})();
</script>
 <script src="/svimages.js"></script>
</body>
</html>
