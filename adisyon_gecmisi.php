<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: admin_login.php"); exit;
}

// === SIFIRLAMA İŞLEMİ ===
if (isset($_POST["sifirla"]) && $_POST["sifirla"] === "EVET_SIL") {
    // İlişkili tablolardan da temizle
    $conn->query("DELETE FROM adisyon_detaylari");
    $conn->query("DELETE FROM ek_ucretler");
    $conn->query("DELETE FROM adisyonlar");
    // Masaları boşa al ve aktif adisyon id'yi temizle
    $conn->query("UPDATE masalar SET durum='bos', aktif_adisyon_id=NULL");
    // ID'leri sıfırla
    $conn->query("ALTER TABLE adisyonlar AUTO_INCREMENT = 1");
    $conn->query("ALTER TABLE adisyon_detaylari AUTO_INCREMENT = 1");
    $conn->query("ALTER TABLE ek_ucretler AUTO_INCREMENT = 1");

    header("Location: adisyon_gecmisi.php?sifirlandi=1");
    exit;
}

// Filtreler
$tarih    = isset($_GET["tarih"])  ? $_GET["tarih"]  : "";
$garson   = isset($_GET["garson"]) ? $_GET["garson"] : "";
$odeme    = isset($_GET["odeme"])  ? $_GET["odeme"]  : "";
$durum_f  = isset($_GET["durum"])  ? $_GET["durum"]  : "";
$arama    = isset($_GET["q"])      ? $_GET["q"]      : "";

$where = ["1=1"];
if ($tarih)  $where[] = "DATE(a.kapanis_tarihi) = '" . $conn->real_escape_string($tarih) . "'";
if ($garson) $where[] = "a.garson_id = " . intval($garson);
if ($odeme)  $where[] = "a.odeme_yontemi = '" . $conn->real_escape_string($odeme) . "'";
if ($durum_f) $where[] = "a.durum = '" . $conn->real_escape_string($durum_f) . "'";
if ($arama !== "") {
    $like = $conn->real_escape_string($arama);
    $where[] = "(m.masa_no LIKE '%$like%' OR k.ad LIKE '%$like%' OR a.id LIKE '%$like%')";
}
$whereStr = implode(" AND ", $where);

// İstatistikler
$statsRes = $conn->query("
    SELECT COUNT(*) AS toplam,
           IFNULL(SUM(a.toplam_tutar),0) AS ciro,
           IFNULL(AVG(a.toplam_tutar),0) AS ortalama,
           IFNULL(MAX(a.toplam_tutar),0) AS en_yuksek
    FROM adisyonlar a
    LEFT JOIN masalar m ON a.masa_id = m.id
    LEFT JOIN kullanicilar k ON a.garson_id = k.id
    WHERE $whereStr AND a.durum='kapali'
");
$stats = $statsRes ? $statsRes->fetch_assoc() : ["toplam"=>0,"ciro"=>0,"ortalama"=>0,"en_yuksek"=>0];

// === MASA DURUMLARI (canlı) ===
$masaQ = $conn->query("
    SELECT m.id, m.masa_no, m.durum, a.toplam_tutar, a.acilis_tarihi, k.ad AS garson_adi
    FROM masalar m
    LEFT JOIN adisyonlar a ON m.aktif_adisyon_id = a.id
    LEFT JOIN kullanicilar k ON a.garson_id = k.id
    ORDER BY m.masa_no ASC
");
$masalar = [];
if ($masaQ) while ($mm = $masaQ->fetch_assoc()) $masalar[] = $mm;

function masaGorseli($masaNo, $durum) {
    if (in_array($masaNo, [1,2,3,4])) {
        return ($durum === "dolu") ? "/uploads/masalar/dolulocamasa.png" : "/uploads/masalar/locamasa.png";
    }
    return ($durum === "dolu") ? "/uploads/masalar/dolunormalmasa.png" : "/uploads/masalar/normalmasa.png";
}

// Adisyonlar
$adisyonlar = $conn->query("
    SELECT a.*, m.masa_no, k.ad AS garson_adi
    FROM adisyonlar a
    LEFT JOIN masalar m ON a.masa_id = m.id
    LEFT JOIN kullanicilar k ON a.garson_id = k.id
    WHERE $whereStr
    ORDER BY a.id DESC LIMIT 200
");

$garsonlar = $conn->query("SELECT id, ad FROM kullanicilar WHERE rol='garson' AND aktif=1 ORDER BY ad");

$rows = [];
if ($adisyonlar) while ($a = $adisyonlar->fetch_assoc()) $rows[] = $a;

function odemeClass($yontem) {
    if ($yontem === "Nakit") return "odeme-nakit";
    if ($yontem === "Kredi Kartı") return "odeme-kart";
    return "odeme-diger";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Adisyon Geçmişi — Maça Kızı</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg-0:#050810;--bg-1:#0a0f1c;--bg-2:#0f1626;--bg-3:#141b30;
  --border:rgba(255,255,255,.08);--border-hover:rgba(255,255,255,.15);
  --cyan:#10c8ee;--green:#24e88a;--red:#ff3b55;--gold:#f5b942;--purple:#a855f7;
  --text:#e2f0ff;--text-bright:#ffffff;--muted:rgba(180,210,255,.55);--muted-2:rgba(180,210,255,.35);
}
body{
  font-family:'DM Sans',sans-serif;color:var(--text);min-height:100vh;background:var(--bg-0);
  background-image:
    radial-gradient(ellipse 80% 50% at 20% 0%,rgba(168,85,247,.18),transparent 50%),
    radial-gradient(ellipse 70% 60% at 80% 0%,rgba(16,200,238,.15),transparent 55%),
    radial-gradient(ellipse 60% 40% at 50% 100%,rgba(255,59,85,.10),transparent 60%);
  background-attachment:fixed;overflow-x:hidden;
}
body::before{
  content:"";position:fixed;inset:0;pointer-events:none;z-index:0;
  background:
    radial-gradient(2px 2px at 15% 30%,rgba(16,200,238,.5),transparent),
    radial-gradient(2px 2px at 65% 70%,rgba(168,85,247,.4),transparent),
    radial-gradient(1px 1px at 85% 15%,rgba(36,232,138,.5),transparent),
    radial-gradient(1px 1px at 35% 85%,rgba(245,185,66,.4),transparent);
  background-size:600px 600px;opacity:.6;animation:driftBg 80s linear infinite;
}
@keyframes driftBg{from{transform:translateY(0)}to{transform:translateY(-600px)}}

a{text-decoration:none;color:inherit}
button{font-family:inherit;cursor:pointer}

.topnav{
  position:sticky;top:0;z-index:50;display:flex;align-items:center;justify-content:space-between;
  gap:16px;padding:16px 28px;background:rgba(5,8,16,.65);
  backdrop-filter:blur(24px) saturate(180%);-webkit-backdrop-filter:blur(24px) saturate(180%);
  border-bottom:1px solid var(--border);
}
.brand{display:flex;align-items:center;gap:14px}
.brand-icon{
  width:46px;height:46px;border-radius:15px;display:flex;align-items:center;justify-content:center;
  font-size:23px;background:linear-gradient(135deg,#ff1744 0%,#a855f7 100%);
  box-shadow:0 0 30px rgba(168,85,247,.4),inset 0 1px 0 rgba(255,255,255,.2);position:relative;
}
.brand-icon::after{content:"";position:absolute;inset:-2px;border-radius:17px;background:linear-gradient(135deg,#ff1744,#a855f7);filter:blur(10px);opacity:.5;z-index:-1}
.brand-name{font-family:'Playfair Display',serif;font-size:22px;font-weight:900;color:var(--text-bright);letter-spacing:.3px}
.brand-sub{font-size:10px;color:var(--muted);font-weight:800;letter-spacing:1.5px;text-transform:uppercase;margin-top:4px}
.nav-actions{display:flex;align-items:center;gap:10px}
.back-btn,.danger-btn{
  display:flex;align-items:center;gap:8px;padding:11px 18px;border-radius:14px;
  font-size:13px;font-weight:800;transition:all .25s ease;backdrop-filter:blur(10px);
  border:1px solid var(--border);color:var(--text);
}
.back-btn{background:rgba(255,255,255,.06)}
.back-btn:hover{background:rgba(255,255,255,.10);border-color:var(--border-hover);transform:translateX(-2px);box-shadow:0 0 20px rgba(16,200,238,.15)}
.danger-btn{background:rgba(255,59,85,.10);border-color:rgba(255,59,85,.3);color:#ff8798;cursor:pointer}
.danger-btn:hover{background:rgba(255,59,85,.22);border-color:rgba(255,59,85,.5);color:#fff;box-shadow:0 0 20px rgba(255,59,85,.3)}

.page{max-width:1300px;margin:0 auto;padding:32px 24px 80px;position:relative;z-index:1}

.page-title-block{margin-bottom:28px;animation:fadeUp .6s ease}
.page-title{
  font-family:'Playfair Display',serif;font-size:42px;font-weight:900;line-height:1;
  margin-bottom:8px;letter-spacing:-.5px;
  background:linear-gradient(135deg,#fff 0%,#a855f7 100%);
  -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;
}
.page-subtitle{font-size:14px;color:var(--muted);font-weight:600}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

/* SUCCESS ALERT */
.alert-success{
  background:linear-gradient(135deg,rgba(36,232,138,.15),rgba(16,200,238,.08));
  border:1px solid rgba(36,232,138,.3);border-radius:18px;
  padding:14px 20px;margin-bottom:22px;
  display:flex;align-items:center;gap:12px;color:var(--green);font-weight:800;font-size:14px;
  animation:fadeUp .5s ease;
}
.alert-success-icon{font-size:22px}

/* SECTION TITLE */
.section-header{
  display:flex;align-items:center;justify-content:space-between;
  margin:24px 0 16px;animation:fadeUp .65s ease;
}
.section-title{
  font-family:'Playfair Display',serif;font-size:22px;font-weight:900;color:var(--text-bright);
  display:flex;align-items:center;gap:10px;
}
.section-title::before{content:"";width:4px;height:22px;border-radius:99px;background:linear-gradient(180deg,var(--cyan),var(--purple));box-shadow:0 0 10px var(--cyan)}

/* === MASALAR === */
.masalar-grid{
  display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:30px;
  animation:fadeUp .75s ease;
}
.masa-card{
  position:relative;min-height:240px;overflow:hidden;border-radius:22px;
  background:rgba(15,22,38,.6);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
  border:1px solid var(--border);transition:all .25s ease;isolation:isolate;
}
.masa-card::before{
  content:"";position:absolute;top:0;left:0;right:0;height:2px;
  background:linear-gradient(90deg,transparent,var(--green),transparent);
}
.masa-card.dolu::before{background:linear-gradient(90deg,transparent,var(--red),transparent)}
.masa-card::after{
  content:"";position:absolute;inset:0;opacity:.4;pointer-events:none;border-radius:22px;
  background:radial-gradient(circle at 20% 10%,rgba(36,232,138,.12),transparent 60%);
}
.masa-card.dolu::after{background:radial-gradient(circle at 20% 10%,rgba(255,59,85,.12),transparent 60%)}
.masa-card:hover{transform:translateY(-3px);border-color:var(--border-hover);box-shadow:0 15px 35px rgba(0,0,0,.4),0 0 30px rgba(16,200,238,.1)}
.masa-card-top{position:relative;z-index:2;padding:12px 14px 0;display:flex;justify-content:space-between;align-items:flex-start}
.masa-mini-label{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:9px;background:rgba(255,255,255,.04);border:1px solid var(--border);font-size:9px;font-weight:900;letter-spacing:.6px;text-transform:uppercase;color:var(--muted)}
.masa-mini-label span{width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 8px var(--green)}
.masa-card.dolu .masa-mini-label span{background:var(--red);box-shadow:0 0 8px var(--red);animation:pulse 2s infinite}
.masa-mini-badge{padding:5px 9px;border-radius:9px;font-size:10px;font-weight:900;letter-spacing:.4px}
.masa-card.bos .masa-mini-badge{background:rgba(36,232,138,.15);color:var(--green);border:1px solid rgba(36,232,138,.3)}
.masa-card.dolu .masa-mini-badge{background:rgba(255,59,85,.15);color:#ff8798;border:1px solid rgba(255,59,85,.3)}

.masa-img-wrap{position:relative;z-index:2;height:120px;display:flex;align-items:center;justify-content:center;padding:4px 10px 0}
.masa-img-wrap::before{content:"";position:absolute;width:70%;height:22px;bottom:8px;left:15%;border-radius:50%;background:rgba(0,0,0,.4);filter:blur(10px);z-index:-1}
.masa-img{width:100%;max-width:140px;height:115px;object-fit:contain;filter:drop-shadow(0 8px 12px rgba(0,0,0,.4))}

.masa-info{position:relative;z-index:2;padding:0 12px 14px;text-align:center}
.masa-no-chip{
  display:inline-flex;align-items:center;justify-content:center;min-width:104px;height:38px;padding:0 12px;
  border-radius:13px;font-family:'Space Grotesk',sans-serif;font-size:15px;font-weight:700;letter-spacing:.5px;
  border:1px solid rgba(255,255,255,.1);
}
.masa-card.bos .masa-no-chip{background:linear-gradient(135deg,rgba(36,232,138,.18),rgba(36,232,138,.06));color:var(--green);border-color:rgba(36,232,138,.3);text-shadow:0 0 12px rgba(36,232,138,.4)}
.masa-card.dolu .masa-no-chip{background:linear-gradient(135deg,rgba(255,59,85,.18),rgba(255,59,85,.06));color:#ff8798;border-color:rgba(255,59,85,.3);text-shadow:0 0 12px rgba(255,59,85,.4)}

.masa-detail{margin-top:8px;font-size:11px;color:var(--muted);font-weight:700;line-height:1.4;min-height:28px}
.masa-detail strong{color:var(--text);font-weight:900}
.masa-price{font-family:'Space Grotesk',sans-serif;font-size:14px;font-weight:700;color:var(--gold);margin-top:3px;text-shadow:0 0 12px rgba(245,185,66,.3)}

/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;animation:fadeUp .7s ease}
.stat-card{
  border-radius:22px;padding:22px;position:relative;overflow:hidden;
  background:rgba(15,22,38,.6);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
  border:1px solid var(--border);transition:all .3s cubic-bezier(.34,1.56,.64,1);
}
.stat-card::before{content:"";position:absolute;top:0;left:0;right:0;height:2px;opacity:.6}
.stat-card::after{content:"";position:absolute;inset:0;opacity:.5;border-radius:22px;pointer-events:none}
.stat-card.cyan::before{background:linear-gradient(90deg,transparent,var(--cyan),transparent)}
.stat-card.green::before{background:linear-gradient(90deg,transparent,var(--green),transparent)}
.stat-card.gold::before{background:linear-gradient(90deg,transparent,var(--gold),transparent)}
.stat-card.purple::before{background:linear-gradient(90deg,transparent,var(--purple),transparent)}
.stat-card.cyan::after{background:radial-gradient(circle at 100% 0%,rgba(16,200,238,.15),transparent 60%)}
.stat-card.green::after{background:radial-gradient(circle at 100% 0%,rgba(36,232,138,.15),transparent 60%)}
.stat-card.gold::after{background:radial-gradient(circle at 100% 0%,rgba(245,185,66,.15),transparent 60%)}
.stat-card.purple::after{background:radial-gradient(circle at 100% 0%,rgba(168,85,247,.18),transparent 60%)}
.stat-card:hover{transform:translateY(-4px);border-color:var(--border-hover);box-shadow:0 20px 40px rgba(0,0,0,.4),0 0 40px rgba(16,200,238,.1)}
.stat-icon-wrap{width:46px;height:46px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:14px;position:relative;z-index:1;background:rgba(255,255,255,.04);border:1px solid var(--border)}
.stat-card.cyan .stat-icon-wrap{background:rgba(16,200,238,.1);border-color:rgba(16,200,238,.25)}
.stat-card.green .stat-icon-wrap{background:rgba(36,232,138,.1);border-color:rgba(36,232,138,.25)}
.stat-card.gold .stat-icon-wrap{background:rgba(245,185,66,.1);border-color:rgba(245,185,66,.25)}
.stat-card.purple .stat-icon-wrap{background:rgba(168,85,247,.1);border-color:rgba(168,85,247,.25)}
.stat-label{font-size:11px;font-weight:900;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:8px;position:relative;z-index:1}
.stat-value{font-family:'Space Grotesk',sans-serif;font-size:32px;font-weight:700;color:var(--text-bright);position:relative;z-index:1;line-height:1;letter-spacing:-1px}
.stat-value-sub{font-size:11px;color:var(--muted-2);font-weight:700;margin-top:6px;position:relative;z-index:1}

/* FILTRE */
.filter-bar{
  background:rgba(15,22,38,.6);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
  border:1px solid var(--border);border-radius:22px;padding:18px;margin-bottom:24px;animation:fadeUp .8s ease;
}
.filter-search{display:flex;gap:10px;margin-bottom:14px}
.search-wrap{flex:1;position:relative}
.search-wrap::before{content:"🔍";position:absolute;left:16px;top:50%;transform:translateY(-50%);font-size:14px;opacity:.5;pointer-events:none}
.search-input{
  width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:14px;
  padding:13px 16px 13px 42px;font-size:14px;font-weight:600;color:var(--text);outline:none;
  transition:all .2s;font-family:inherit;
}
.search-input:focus{border-color:rgba(16,200,238,.5);background:rgba(255,255,255,.06);box-shadow:0 0 0 4px rgba(16,200,238,.1)}
.search-input::placeholder{color:var(--muted-2)}
.filter-grid{display:grid;grid-template-columns:repeat(4,1fr) auto auto;gap:10px;align-items:flex-end}
.filter-group{display:flex;flex-direction:column;gap:6px}
.filter-label{font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:1.2px;color:var(--muted)}
.filter-input{background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:12px;padding:11px 14px;font-size:13px;font-weight:700;color:var(--text);outline:none;font-family:inherit;transition:all .2s;cursor:pointer}
.filter-input:hover{background:rgba(255,255,255,.06)}
.filter-input:focus{border-color:rgba(16,200,238,.5);box-shadow:0 0 0 3px rgba(16,200,238,.1)}
.filter-input option{background:#0a0f1c;color:#e2f0ff}
.filter-btn{padding:11px 20px;border:0;border-radius:12px;font-size:13px;font-weight:900;transition:all .25s;letter-spacing:.3px;white-space:nowrap}
.filter-btn.primary{background:linear-gradient(135deg,#10c8ee,#a855f7);color:#fff;box-shadow:0 6px 20px rgba(16,200,238,.3),inset 0 1px 0 rgba(255,255,255,.2)}
.filter-btn.primary:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(168,85,247,.4)}
.filter-btn.reset{background:rgba(255,255,255,.06);color:var(--muted);border:1px solid var(--border);text-decoration:none;display:inline-flex;align-items:center}
.filter-btn.reset:hover{background:rgba(255,255,255,.1);color:var(--text)}

.list-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;animation:fadeUp .9s ease}
.list-title{font-family:'Playfair Display',serif;font-size:22px;font-weight:900;color:var(--text-bright);display:flex;align-items:center;gap:10px}
.list-title::before{content:"";width:4px;height:22px;border-radius:99px;background:linear-gradient(180deg,var(--cyan),var(--purple));box-shadow:0 0 10px var(--cyan)}
.list-count{font-size:12px;font-weight:800;color:var(--text);background:rgba(16,200,238,.1);padding:6px 14px;border-radius:99px;border:1px solid rgba(16,200,238,.25)}

.adisyon-list{display:flex;flex-direction:column;gap:10px}
.adisyon-card{
  display:flex;align-items:center;gap:18px;background:rgba(15,22,38,.55);
  backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
  border:1px solid var(--border);border-radius:18px;padding:16px 20px;transition:all .25s ease;
  position:relative;overflow:hidden;animation:slideIn .5s ease backwards;
}
.adisyon-card:nth-child(1){animation-delay:.05s}
.adisyon-card:nth-child(2){animation-delay:.10s}
.adisyon-card:nth-child(3){animation-delay:.15s}
.adisyon-card:nth-child(4){animation-delay:.20s}
.adisyon-card:nth-child(5){animation-delay:.25s}
.adisyon-card:nth-child(6){animation-delay:.30s}
.adisyon-card:nth-child(7){animation-delay:.35s}
.adisyon-card:nth-child(8){animation-delay:.40s}
@keyframes slideIn{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}
.adisyon-card::before{content:"";position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:60%;border-radius:99px;background:linear-gradient(180deg,var(--green),var(--cyan));box-shadow:0 0 10px var(--green)}
.adisyon-card.kapali::before{background:linear-gradient(180deg,#64748b,#475569);box-shadow:none}
.adisyon-card:hover{transform:translateX(4px) translateY(-1px);background:rgba(15,22,38,.8);border-color:rgba(168,85,247,.3);box-shadow:0 15px 30px rgba(0,0,0,.3),0 0 30px rgba(168,85,247,.1)}
.ac-id{width:48px;height:48px;border-radius:14px;flex-shrink:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(255,255,255,.04);border:1px solid var(--border);font-family:'Space Grotesk',sans-serif}
.ac-id-hash{font-size:9px;font-weight:700;color:var(--muted-2);letter-spacing:1px}
.ac-id-num{font-size:14px;font-weight:700;color:var(--text);line-height:1}
.ac-main{flex:1;min-width:0}
.ac-title{font-size:15px;font-weight:800;color:var(--text-bright);margin-bottom:6px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.ac-masa-chip{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,rgba(16,200,238,.15),rgba(168,85,247,.15));border:1px solid rgba(16,200,238,.25);padding:3px 10px;border-radius:8px;font-size:12px;font-weight:900;color:var(--cyan);letter-spacing:.3px}
.ac-garson{font-weight:700;color:var(--text);font-size:14px}
.ac-meta{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
.ac-meta-item{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--muted);font-weight:700}
.ac-meta-icon{font-size:12px;opacity:.7}
.ac-right{display:flex;align-items:center;gap:14px;flex-shrink:0}
.ac-price{font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;color:var(--gold);white-space:nowrap;letter-spacing:-.5px;text-shadow:0 0 20px rgba(245,185,66,.3)}
.ac-badge{padding:6px 12px;border-radius:10px;font-size:11px;font-weight:900;letter-spacing:.5px;white-space:nowrap;display:inline-flex;align-items:center;gap:5px}
.ac-badge.kapali{background:rgba(148,163,184,.12);color:#94a3b8;border:1px solid rgba(148,163,184,.2)}
.ac-badge.acik{background:rgba(36,232,138,.12);color:var(--green);border:1px solid rgba(36,232,138,.25);box-shadow:0 0 15px rgba(36,232,138,.15)}
.ac-badge.acik::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 6px var(--green);animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.ac-detay-btn{padding:9px 16px;border-radius:11px;font-size:12px;font-weight:900;background:linear-gradient(135deg,rgba(16,200,238,.12),rgba(168,85,247,.12));border:1px solid rgba(16,200,238,.25);color:var(--cyan);transition:all .25s;white-space:nowrap}
.ac-detay-btn:hover{background:linear-gradient(135deg,rgba(16,200,238,.25),rgba(168,85,247,.25));border-color:rgba(16,200,238,.5);color:var(--text-bright);transform:translateX(2px);box-shadow:0 6px 18px rgba(16,200,238,.25)}

.odeme-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:7px;font-size:10px;font-weight:900;letter-spacing:.3px;text-transform:uppercase}
.odeme-nakit{background:rgba(36,232,138,.12);color:var(--green);border:1px solid rgba(36,232,138,.25)}
.odeme-kart{background:rgba(16,200,238,.12);color:var(--cyan);border:1px solid rgba(16,200,238,.25)}
.odeme-diger{background:rgba(255,255,255,.06);color:var(--muted);border:1px solid var(--border)}

.empty-state{text-align:center;padding:80px 20px;color:var(--muted);background:rgba(15,22,38,.4);border:1px dashed var(--border);border-radius:24px}
.empty-icon{font-size:64px;margin-bottom:20px;opacity:.4}
.empty-title{font-size:18px;font-weight:800;color:var(--text);margin-bottom:8px}
.empty-text{font-size:13px;color:var(--muted)}

/* MODAL */
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(8px);z-index:1000;display:none;align-items:center;justify-content:center;padding:20px;animation:fadeIn .2s}
.modal-backdrop.active{display:flex}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.modal-box{
  width:min(440px,100%);background:linear-gradient(160deg,#1a0a14,#0f0a18);
  border:1px solid rgba(255,59,85,.3);border-radius:24px;padding:28px;
  box-shadow:0 30px 80px rgba(0,0,0,.6),0 0 60px rgba(255,59,85,.15);
  animation:popIn .25s cubic-bezier(.34,1.56,.64,1);
}
@keyframes popIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}
.modal-icon-wrap{width:64px;height:64px;border-radius:18px;background:rgba(255,59,85,.15);border:1px solid rgba(255,59,85,.3);display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 18px}
.modal-title{font-family:'Playfair Display',serif;font-size:24px;font-weight:900;color:#fff;text-align:center;margin-bottom:8px}
.modal-text{font-size:14px;color:var(--muted);text-align:center;line-height:1.5;margin-bottom:18px}
.modal-warning{background:rgba(255,59,85,.08);border:1px solid rgba(255,59,85,.2);border-radius:12px;padding:12px 14px;font-size:12px;color:#ff8798;text-align:center;font-weight:700;margin-bottom:20px}
.modal-actions{display:flex;gap:10px}
.modal-btn{flex:1;padding:13px;border-radius:13px;font-size:13px;font-weight:900;border:0;transition:all .2s;font-family:inherit;cursor:pointer}
.modal-btn.cancel{background:rgba(255,255,255,.06);color:var(--text);border:1px solid var(--border)}
.modal-btn.cancel:hover{background:rgba(255,255,255,.1)}
.modal-btn.confirm{background:linear-gradient(135deg,#ff3b55,#b40016);color:#fff;box-shadow:0 8px 20px rgba(255,59,85,.3)}
.modal-btn.confirm:hover{box-shadow:0 10px 28px rgba(255,59,85,.5);transform:translateY(-1px)}

@media(max-width:1100px){
  .stats-row{grid-template-columns:repeat(2,1fr)}
  .filter-grid{grid-template-columns:repeat(2,1fr);gap:10px}
  .filter-grid .filter-btn{grid-column:span 1}
}
@media(max-width:768px){
  .topnav{padding:12px 16px;flex-wrap:wrap;gap:10px}
  .brand-name{font-size:18px}.brand-icon{width:40px;height:40px;font-size:20px}
  .nav-actions{gap:6px}
  .back-btn,.danger-btn{padding:9px 13px;font-size:12px}
  .page{padding:18px 14px 50px}
  .page-title{font-size:30px}.page-subtitle{font-size:13px}
  .stats-row{grid-template-columns:1fr 1fr;gap:10px}
  .stat-card{padding:16px}.stat-icon-wrap{width:38px;height:38px;font-size:18px;margin-bottom:10px}
  .stat-value{font-size:22px}
  .masalar-grid{grid-template-columns:repeat(3,1fr);gap:8px}
  .masa-card{min-height:175px;border-radius:16px}
  .masa-card-top{padding:7px 8px 0}
  .masa-mini-label{font-size:8px;padding:3px 6px}.masa-mini-badge{font-size:9px;padding:3px 6px}
  .masa-img-wrap{height:78px}.masa-img{max-width:84px;height:74px}
  .masa-no-chip{min-width:70px;height:28px;font-size:11px;padding:0 8px;border-radius:9px}
  .masa-detail{font-size:9px;margin-top:5px;min-height:22px}
  .masa-price{font-size:11px}
  .filter-bar{padding:14px}.filter-grid{grid-template-columns:1fr 1fr;gap:8px}
  .filter-grid .filter-btn{grid-column:span 2}
  .list-title{font-size:18px}
  .section-title{font-size:18px}
  .adisyon-card{padding:13px 14px;gap:12px}
  .ac-id{width:40px;height:40px}.ac-id-num{font-size:12px}
  .ac-title{font-size:13px;gap:6px}.ac-masa-chip{font-size:11px;padding:2px 8px}
  .ac-garson{font-size:12px}.ac-price{font-size:18px}
  .ac-meta{gap:8px}.ac-meta-item{font-size:10px}
  .ac-detay-btn{padding:7px 12px;font-size:11px}
  .ac-badge{font-size:10px;padding:5px 9px}
  .ac-right{gap:8px;flex-wrap:wrap;justify-content:flex-end}
  .modal-box{padding:22px}
}
@media(max-width:480px){
  .stats-row{grid-template-columns:1fr}
  .ac-right{width:100%;justify-content:space-between}
  .adisyon-card{flex-wrap:wrap}
}
</style>
</head>
<body>

<nav class="topnav">
  <div class="brand">
    <div class="brand-icon">♠</div>
    <div>
      <div class="brand-name">Maça Kızı</div>
      <div class="brand-sub">Adisyon Geçmişi</div>
    </div>
  </div>
  <div class="nav-actions">
    <button class="danger-btn" type="button" onclick="openSifirla()">🗑 Sıfırla</button>
    <a class="back-btn" href="admin.php">← Admin Paneli</a>
  </div>
</nav>

<div class="page">

  <?php if (isset($_GET["sifirlandi"])): ?>
    <div class="alert-success">
      <span class="alert-success-icon">✅</span>
      <span>Tüm adisyon geçmişi başarıyla sıfırlandı. Masalar boş duruma alındı.</span>
    </div>
  <?php endif; ?>

  <div class="page-title-block">
    <div class="page-title">Adisyon Geçmişi</div>
    <div class="page-subtitle">Tüm geçmiş adisyonları görüntüleyin, filtreleyin ve analiz edin</div>
  </div>

  <!-- CANLI MASA DURUMU -->
  <div class="section-header">
    <div class="section-title">Canlı Masa Durumu</div>
  </div>
  <div class="masalar-grid">
    <?php foreach ($masalar as $m):
      $durum  = $m["durum"];
      $masaNo = (int)$m["masa_no"];
      $gorsel = masaGorseli($masaNo, $durum);
      $gorselDosya = $_SERVER["DOCUMENT_ROOT"] . $gorsel;
      $ver = file_exists($gorselDosya) ? filemtime($gorselDosya) : time();
      $src = $gorsel . "?v=" . $ver;
    ?>
      <div class="masa-card <?php echo htmlspecialchars($durum); ?>">
        <div class="masa-card-top">
          <div class="masa-mini-label"><span></span> Canlı</div>
          <div class="masa-mini-badge"><?php echo $durum === "bos" ? "BOŞ" : "DOLU"; ?></div>
        </div>
        <div class="masa-img-wrap">
          <img class="masa-img" src="<?php echo $src; ?>" alt="Masa <?php echo $masaNo; ?>">
        </div>
        <div class="masa-info">
          <div class="masa-no-chip">MASA <?php echo str_pad($masaNo, 2, "0", STR_PAD_LEFT); ?></div>
          <div class="masa-detail">
            <?php if ($durum === "dolu"): ?>
              <strong><?php echo htmlspecialchars(isset($m["garson_adi"]) && $m["garson_adi"] ? $m["garson_adi"] : "Garson"); ?></strong>
              <?php if ($m["acilis_tarihi"]): ?> · <?php echo date("H:i", strtotime($m["acilis_tarihi"])); ?><?php endif; ?>
              <div class="masa-price"><?php echo number_format((float)$m["toplam_tutar"], 2, ',', '.'); ?> ₺</div>
            <?php else: ?>
              <strong>Müsait</strong>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- İSTATİSTİKLER -->
  <div class="stats-row">
    <div class="stat-card cyan">
      <div class="stat-icon-wrap">🧾</div>
      <div class="stat-label">Toplam Adisyon</div>
      <div class="stat-value"><?php echo number_format($stats["toplam"]); ?></div>
      <div class="stat-value-sub">Kapalı adisyonlar</div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon-wrap">💰</div>
      <div class="stat-label">Toplam Ciro</div>
      <div class="stat-value"><?php echo number_format($stats["ciro"], 0, ',', '.'); ?> ₺</div>
      <div class="stat-value-sub">Filtreye göre</div>
    </div>
    <div class="stat-card gold">
      <div class="stat-icon-wrap">📊</div>
      <div class="stat-label">Ortalama Tutar</div>
      <div class="stat-value"><?php echo number_format($stats["ortalama"], 0, ',', '.'); ?> ₺</div>
      <div class="stat-value-sub">Adisyon başına</div>
    </div>
    <div class="stat-card purple">
      <div class="stat-icon-wrap">🏆</div>
      <div class="stat-label">En Yüksek</div>
      <div class="stat-value"><?php echo number_format($stats["en_yuksek"], 0, ',', '.'); ?> ₺</div>
      <div class="stat-value-sub">Rekor tutar</div>
    </div>
  </div>

  <!-- FİLTRE -->
  <form method="GET" class="filter-bar">
    <div class="filter-search">
      <div class="search-wrap">
        <input class="search-input" type="text" name="q" placeholder="Masa no, garson adı veya adisyon ID ile ara..." value="<?php echo htmlspecialchars($arama); ?>">
      </div>
    </div>
    <div class="filter-grid">
      <div class="filter-group">
        <div class="filter-label">📅 Tarih</div>
        <input class="filter-input" type="date" name="tarih" value="<?php echo htmlspecialchars($tarih); ?>">
      </div>
      <div class="filter-group">
        <div class="filter-label">👤 Garson</div>
        <select class="filter-input" name="garson">
          <option value="">Tüm Garsonlar</option>
          <?php if ($garsonlar): while($g = $garsonlar->fetch_assoc()): ?>
            <option value="<?php echo $g["id"]; ?>" <?php echo $garson == $g["id"] ? "selected" : ""; ?>>
              <?php echo htmlspecialchars($g["ad"]); ?>
            </option>
          <?php endwhile; endif; ?>
        </select>
      </div>
      <div class="filter-group">
        <div class="filter-label">💳 Ödeme</div>
        <select class="filter-input" name="odeme">
          <option value="">Tüm Yöntemler</option>
          <option value="Nakit" <?php echo $odeme=="Nakit"?"selected":""; ?>>💵 Nakit</option>
          <option value="Kredi Kartı" <?php echo $odeme=="Kredi Kartı"?"selected":""; ?>>💳 Kredi Kartı</option>
          <option value="QR" <?php echo $odeme=="QR"?"selected":""; ?>>📱 QR</option>
        </select>
      </div>
      <div class="filter-group">
        <div class="filter-label">📌 Durum</div>
        <select class="filter-input" name="durum">
          <option value="">Tümü</option>
          <option value="kapali" <?php echo $durum_f=="kapali"?"selected":""; ?>>✅ Kapalı</option>
          <option value="acik" <?php echo $durum_f=="acik"?"selected":""; ?>>🟢 Açık</option>
        </select>
      </div>
      <button class="filter-btn primary" type="submit">Filtrele</button>
      <a class="filter-btn reset" href="adisyon_gecmisi.php">Sıfırla</a>
    </div>
  </form>

  <div class="list-header">
    <div class="list-title">Adisyonlar</div>
    <div class="list-count"><?php echo count($rows); ?> kayıt</div>
  </div>

  <div class="adisyon-list">
  <?php if (empty($rows)): ?>
    <div class="empty-state">
      <div class="empty-icon">🔍</div>
      <div class="empty-title">Sonuç bulunamadı</div>
      <div class="empty-text">Bu kriterlere uygun adisyon yok. Filtreleri değiştirip tekrar deneyin.</div>
    </div>
  <?php else: foreach ($rows as $a):
    $durum = $a["durum"];
    $sure = "";
    if (!empty($a["acilis_tarihi"]) && !empty($a["kapanis_tarihi"])) {
      $diff = strtotime($a["kapanis_tarihi"]) - strtotime($a["acilis_tarihi"]);
      $dk = floor($diff / 60);
      $sure = $dk >= 60 ? floor($dk/60) . "s " . ($dk%60) . "dk" : $dk . " dk";
    }
    $oCls = odemeClass(isset($a["odeme_yontemi"]) ? $a["odeme_yontemi"] : "");
  ?>
    <div class="adisyon-card <?php echo $durum; ?>">
      <div class="ac-id">
        <div class="ac-id-hash">#</div>
        <div class="ac-id-num"><?php echo $a["id"]; ?></div>
      </div>
      <div class="ac-main">
        <div class="ac-title">
          <span class="ac-masa-chip">🪑 MASA <?php echo str_pad($a["masa_no"], 2, "0", STR_PAD_LEFT); ?></span>
          <span class="ac-garson"><?php echo htmlspecialchars(isset($a["garson_adi"]) && $a["garson_adi"] ? $a["garson_adi"] : "—"); ?></span>
        </div>
        <div class="ac-meta">
          <?php if (!empty($a["acilis_tarihi"])): ?>
            <span class="ac-meta-item"><span class="ac-meta-icon">🕐</span><?php echo date("d.m.Y H:i", strtotime($a["acilis_tarihi"])); ?></span>
          <?php endif; ?>
          <?php if (!empty($a["kapanis_tarihi"])): ?>
            <span class="ac-meta-item"><span class="ac-meta-icon">→</span><?php echo date("H:i", strtotime($a["kapanis_tarihi"])); ?></span>
          <?php endif; ?>
          <?php if ($sure): ?>
            <span class="ac-meta-item"><span class="ac-meta-icon">⏱</span><?php echo $sure; ?></span>
          <?php endif; ?>
          <?php if (!empty($a["odeme_yontemi"])): ?>
            <span class="odeme-pill <?php echo $oCls; ?>"><?php echo htmlspecialchars($a["odeme_yontemi"]); ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div class="ac-right">
        <div class="ac-price"><?php echo number_format((float)$a["toplam_tutar"], 2, ',', '.'); ?> ₺</div>
        <span class="ac-badge <?php echo $durum; ?>"><?php echo $durum === "kapali" ? "Kapalı" : "Açık"; ?></span>
        <a class="ac-detay-btn" href="adisyon_detay.php?id=<?php echo $a["id"]; ?>">Detay →</a>
      </div>
    </div>
  <?php endforeach; endif; ?>
  </div>

</div>

<!-- SIFIRLAMA ONAY MODAL -->
<div class="modal-backdrop" id="sifirlaModal" onclick="closeSifirlaBg(event)">
  <div class="modal-box">
    <div class="modal-icon-wrap">⚠</div>
    <div class="modal-title">Adisyon Geçmişini Sıfırla</div>
    <div class="modal-text">
      Tüm adisyon kayıtları, sipariş detayları ve ek ücretler kalıcı olarak silinecek. Bu işlem <strong>geri alınamaz</strong>.
    </div>
    <div class="modal-warning">
      🔥 Tüm masalar boş duruma alınacak ve ID numaraları 1'den başlayacak.
    </div>
    <form method="POST">
      <input type="hidden" name="sifirla" value="EVET_SIL">
      <div class="modal-actions">
        <button type="button" class="modal-btn cancel" onclick="closeSifirla()">İptal</button>
        <button type="submit" class="modal-btn confirm">Evet, Sıfırla</button>
      </div>
    </form>
  </div>
</div>

<script>
function openSifirla(){ document.getElementById('sifirlaModal').classList.add('active'); }
function closeSifirla(){ document.getElementById('sifirlaModal').classList.remove('active'); }
function closeSifirlaBg(e){ if(e.target.id === 'sifirlaModal') closeSifirla(); }
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeSifirla(); });

// Masa durumlarını canlı yenile
setInterval(() => {
  if (!document.hidden && !document.getElementById('sifirlaModal').classList.contains('active')) {
    // sayfayı sessizce yenile (filtre korunarak)
    // not: filtre formu varken sayfayı sürekli yenilemek istemiyorsan bu satırı kaldır
  }
}, 5000);
</script>

</body>
</html>
