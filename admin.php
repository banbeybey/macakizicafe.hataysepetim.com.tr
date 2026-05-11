<?php
session_set_cookie_params(5 * 3600);
session_start();
ini_set('session.gc_maxlifetime', 5 * 3600);
require_once "db.php";

// ✅ admin_login.php'ye yönlendir (login.php değil)
if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: admin_login.php"); exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$masalar = $conn->query("
    SELECT m.*, a.toplam_tutar, a.acilis_tarihi, k.ad AS garson_adi
    FROM masalar m
    LEFT JOIN adisyonlar a ON m.aktif_adisyon_id = a.id
    LEFT JOIN kullanicilar k ON a.garson_id = k.id
    ORDER BY m.masa_no ASC
");

$gunluk = $conn->query("
    SELECT COUNT(*) AS siparis_sayisi, IFNULL(SUM(toplam_tutar),0) AS toplam_ciro
    FROM adisyonlar WHERE durum='kapali' AND DATE(kapanis_tarihi) = CURDATE()
")->fetch_assoc();

$dolu = $conn->query("SELECT COUNT(*) AS t FROM masalar WHERE durum='dolu'")->fetch_assoc()["t"];
$bos  = $conn->query("SELECT COUNT(*) AS t FROM masalar WHERE durum='bos'")->fetch_assoc()["t"];

// ✅ Yardımcı fonksiyon: masa görseli seç (dolu/boş, loca/normal)
function masaGorseli($masaNo, $durum) {
    if (in_array($masaNo, [1,2,3,4])) {
        return ($durum === "dolu") ? "/uploads/masalar/dolulocamasa.png" : "/uploads/masalar/locamasa.png";
    } else {
        return ($durum === "dolu") ? "/uploads/masalar/dolunormalmasa.png" : "/uploads/masalar/normalmasa.png";
    }
}

function getMasaAdi($masaNo) {
    if (in_array($masaNo, [1,2,3,4])) {
        return "LOCA " . $masaNo;
    } else {
        return "OYUN " . str_pad($masaNo - 4, 2, "0", STR_PAD_LEFT);
    }
}

if (isset($_GET["ajax"]) && $_GET["ajax"] == "masalar") {
    $masalarAjax = $conn->query("
        SELECT m.*, a.toplam_tutar, a.acilis_tarihi, k.ad AS garson_adi
        FROM masalar m
        LEFT JOIN adisyonlar a ON m.aktif_adisyon_id = a.id
        LEFT JOIN kullanicilar k ON a.garson_id = k.id
        ORDER BY m.masa_no ASC
    ");
    while ($m = $masalarAjax->fetch_assoc()):
        $durum  = $m["durum"];
        $masaNo = (int)$m["masa_no"];
        // ✅ dolu/boş görsel
        $gorsel       = masaGorseli($masaNo, $durum);
        $gorselDosya  = $_SERVER["DOCUMENT_ROOT"] . $gorsel;
        $gorselVersiyon = file_exists($gorselDosya) ? filemtime($gorselDosya) : time();
        $gorselSrc    = $gorsel . "?v=" . $gorselVersiyon;
?>
<div class="masa-card <?php echo htmlspecialchars($durum); ?> <?php echo !in_array($masaNo,[1,2,3,4]) ? 'oyun' : ''; ?>">
    <div class="card-top">
        <div class="masa-label"><span></span> Admin Durum</div>
        <div class="masa-badge"><?php echo $durum == "bos" ? "BOŞ" : "DOLU"; ?></div>
    </div>
    <div class="table-wrapper">
        <img class="table-image" src="<?php echo $gorselSrc; ?>" alt="Masa <?php echo $masaNo; ?>">
    </div>
    <div class="masa-main">
        <div class="masa-no"><?php echo htmlspecialchars(getMasaAdi($masaNo)); ?></div>
    </div>
    <div class="masa-detail">
        <?php if ($durum === "dolu"): ?>
            <div><strong><?php echo htmlspecialchars($m["garson_adi"] ?? "Garson"); ?></strong> · <?php echo $m["acilis_tarihi"] ? date("H:i", strtotime($m["acilis_tarihi"])) : "--:--"; ?></div>
            <div class="price-line"><?php echo number_format((float)$m["toplam_tutar"], 2, ',', '.'); ?> ₺</div>
        <?php else: ?>
            <div><strong>Kullanıma hazır</strong></div>
            <div>Müsait</div>
        <?php endif; ?>
    </div>
    <?php if ($durum === "dolu"): ?>
        <button class="siparis-detay-btn" type="button" onclick="openSiparisDetay(<?php echo (int)$m['id']; ?>)">🧾 Sipariş Detaylarını Gör</button>
        <button class="admin-close-btn" type="button" onclick="openMasaPanel(<?php echo (int)$m['id']; ?>)">Masayı Kapat</button>
    <?php else: ?>
        <button class="admin-close-btn" type="button" disabled>Boş Masa</button>
    <?php endif; ?>
</div>
<?php
    endwhile;
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Admin Paneli — Maça Kızı</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}
:root{
  --bg1:#eef7ff;--bg2:#f7fbff;--card:#ffffff;--line:rgba(51,65,85,.13);
  --cyan:#10c8ee;--cyan2:#36e4ff;--green:#24e88a;--red:#ff3b55;
  --text:#1f2937;--muted:#64748b;--gold:#f5b942;
}
body{
  margin:0;font-family:'DM Sans',Arial,sans-serif;color:var(--text);min-height:100vh;
  background:radial-gradient(circle at 12% 6%,rgba(255,44,92,.20),transparent 28%),
  radial-gradient(circle at 88% 2%,rgba(28,200,238,.25),transparent 30%),
  linear-gradient(180deg,#f5fbff 0%,#edf7ff 46%,#f8fbff 100%);
}
a{text-decoration:none;color:inherit}
.topnav{
  position:sticky;top:0;z-index:50;
  display:flex;align-items:center;justify-content:space-between;gap:18px;
  padding:10px 28px;
  background:linear-gradient(180deg,rgba(255,255,255,0.04) 0%,rgba(255,255,255,0.01) 100%),#0d0d0d;
  border-bottom:1.5px solid rgba(220,30,50,0.45);
  box-shadow:0 0 0 1px rgba(255,255,255,0.04) inset,0 0 30px rgba(200,0,30,0.12),0 4px 24px rgba(0,0,0,0.5);
  overflow:hidden;
}
.topnav::before{
  content:'';position:absolute;top:-60%;left:50%;transform:translateX(-50%);
  width:60%;height:120%;
  background:radial-gradient(ellipse,rgba(200,16,46,0.12) 0%,transparent 70%);
  pointer-events:none;
}
.brand{display:flex;align-items:center;gap:20px;position:relative;z-index:1;}
.brand-logo-inner{display:flex;align-items:center;gap:12px;}
.brand-logo-line{width:40px;height:2px;border-radius:999px;background:linear-gradient(90deg,transparent,rgba(200,16,46,0.8),transparent);box-shadow:0 0 8px rgba(200,16,46,0.5);flex-shrink:0;}
.brand-logo-text{font-family:'Bebas Neue','DM Sans',sans-serif;font-size:32px;font-weight:400;letter-spacing:5px;color:#fff8f0;line-height:1;white-space:nowrap;text-shadow:0 0 20px rgba(255,255,255,0.1),0 2px 4px rgba(0,0,0,0.8);}
.brand-logo-spade{display:inline-block;color:#ff1a2e;font-size:1.05em;vertical-align:middle;position:relative;top:-3px;margin:0 3px;text-shadow:0 0 4px rgba(255,255,255,0.4),0 0 8px #ff0020,0 0 16px rgba(255,0,32,0.5);filter:drop-shadow(0 0 3px rgba(255,0,30,0.6));animation:topspade-pulse 2s ease-in-out infinite;}
@keyframes topspade-pulse{0%,100%{text-shadow:0 0 4px rgba(255,255,255,0.4),0 0 8px #ff0020,0 0 16px rgba(255,0,32,0.5);}50%{text-shadow:0 0 6px rgba(255,255,255,0.5),0 0 12px #ff0020,0 0 22px rgba(255,0,32,0.6);}}
.brand-sub-row{display:flex;align-items:center;gap:8px;margin-top:3px;}
.brand-sub-line{flex:1;height:1px;max-width:50px;border-radius:999px;background:linear-gradient(90deg,transparent,rgba(200,16,46,0.8),transparent);box-shadow:0 0 6px rgba(200,16,46,0.4);}
.brand-sub{font-size:10px;font-weight:700;letter-spacing:4px;color:#e8102a;text-transform:uppercase;white-space:nowrap;text-shadow:0 0 6px rgba(255,0,30,0.3);}
.nav-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end;position:relative;z-index:1;}
.nav-user{font-size:13px;font-weight:800;color:rgba(255,255,255,0.7);background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);padding:9px 13px;border-radius:14px;}
.btn-ghost{font-size:13px;font-weight:900;color:#fff;background:linear-gradient(135deg,#e8102a 0%,#9a0020 100%);padding:10px 16px;border-radius:14px;box-shadow:0 4px 16px rgba(200,16,46,0.4);transition:all 0.2s;}
.btn-ghost:hover{background:linear-gradient(135deg,#ff1a30 0%,#b5002a 100%);transform:translateY(-1px);}
.page{width:100%;max-width:1450px;margin:0 auto;padding:18px 18px 40px}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}
.stat-card{background:rgba(255,255,255,.88);border:1px solid rgba(51,65,85,.12);border-radius:22px;padding:18px;box-shadow:0 15px 30px rgba(15,23,42,.08),inset 0 1px 0 rgba(255,255,255,.95)}
.stat-icon{width:36px;height:36px;display:flex;align-items:center;justify-content:center;margin-bottom:10px;}
.stat-label{font-size:12px;color:var(--muted);font-weight:900;text-transform:uppercase;letter-spacing:.7px}
.stat-value{font-size:27px;font-weight:950;color:#0f172a;margin-top:4px}.gold{color:#c88700}
.nav-menu{display:grid;grid-template-columns:repeat(7,1fr);gap:10px;margin:0 0 22px}
.nav-item{min-height:72px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:7px;text-align:center;padding:12px 8px;border-radius:18px;background:linear-gradient(180deg,rgba(255,255,255,0.04) 0%,rgba(255,255,255,0.01) 100%),#0d0d0d;border:1px solid rgba(220,30,50,0.30);color:rgba(255,248,240,0.82);font-weight:700;font-size:12px;letter-spacing:.3px;box-shadow:0 4px 16px rgba(0,0,0,0.4),0 0 0 1px rgba(255,255,255,0.03) inset;transition:all 0.2s;position:relative;overflow:hidden;}
.nav-item::before{content:'';position:absolute;top:-50%;left:50%;transform:translateX(-50%);width:80%;height:80%;background:radial-gradient(ellipse,rgba(200,16,46,0.07) 0%,transparent 70%);pointer-events:none;}
.nav-item:hover{border-color:rgba(220,30,50,0.65);background:linear-gradient(180deg,rgba(200,16,46,0.10) 0%,rgba(255,255,255,0.02) 100%),#0d0d0d;box-shadow:0 6px 22px rgba(0,0,0,0.5),0 0 18px rgba(200,0,30,0.14);color:#fff;transform:translateY(-2px);}
.nav-icon{width:26px;height:26px;display:flex;align-items:center;justify-content:center;color:#e8102a;flex-shrink:0;filter:drop-shadow(0 0 3px rgba(232,16,42,0.35));}
.section-title{font-size:24px;font-weight:950;color:#0f172a;margin:14px 0 14px;letter-spacing:.2px}
.masalar-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(195px,1fr));gap:18px}
.masa-card{position:relative;min-height:316px;overflow:hidden;border-radius:26px;background:linear-gradient(180deg,rgba(255,255,255,.94),rgba(245,251,255,.84)),radial-gradient(circle at 22% 15%,rgba(54,228,255,.22),transparent 40%),radial-gradient(circle at 88% 18%,rgba(255,59,85,.12),transparent 38%);border:1px solid rgba(51,65,85,.14);box-shadow:0 16px 32px rgba(15,23,42,.13),0 2px 0 rgba(255,255,255,.85) inset;transition:.22s ease;isolation:isolate}
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
.masa-main{position:relative;z-index:3;padding:0 14px 13px;text-align:center}
.masa-no{display:inline-flex;align-items:center;justify-content:center;min-width:126px;height:48px;padding:0 15px;border-radius:17px;font-size:21px;font-weight:950;letter-spacing:1px;color:#0f172a;background:linear-gradient(180deg,#ffffff,#e7f7ff);border:1px solid rgba(16,200,238,.62);box-shadow:inset 0 0 18px rgba(255,255,255,.65),0 8px 20px rgba(16,200,238,.14)}
.bos .masa-no{color:#fff;background:linear-gradient(135deg,#22c55e,#0f8f3f);border-color:rgba(255,255,255,.70);text-shadow:0 2px 8px rgba(0,0,0,.30);box-shadow:inset 0 0 18px rgba(255,255,255,.18),0 0 22px rgba(34,197,94,.46),0 10px 24px rgba(15,143,63,.25)}
.dolu .masa-no{color:#fff;background:linear-gradient(135deg,#ff1744,#b40016);border-color:rgba(255,255,255,.70);text-shadow:0 2px 8px rgba(0,0,0,.35);box-shadow:inset 0 0 18px rgba(255,255,255,.18),0 0 22px rgba(255,23,68,.48),0 10px 24px rgba(180,0,22,.28)}
.masa-card.oyun .masa-no{color:#1a1000;background:linear-gradient(135deg,#ffe033,#f5a800);border-color:rgba(255,255,255,.70);text-shadow:0 1px 4px rgba(255,200,0,.30);box-shadow:inset 0 0 18px rgba(255,255,255,.30),0 0 22px rgba(255,200,0,.50),0 10px 24px rgba(200,130,0,.25)}
.masa-detail{position:relative;z-index:3;padding:0 16px 18px;text-align:center;color:var(--muted);font-size:13px;font-weight:850;min-height:38px}
.masa-detail strong{color:#0f172a}.dolu .masa-detail strong{color:#b40016}.bos .masa-detail strong{color:#0f8f3f}
.price-line{margin-top:5px;font-family:'Playfair Display',serif;font-size:17px;font-weight:800;color:#111827}
.admin-close-btn{position:relative;z-index:4;width:calc(100% - 28px);margin:0 14px 18px;border:0;border-radius:16px;padding:12px 10px;cursor:pointer;font-weight:950;letter-spacing:.2px;color:#fff;background:linear-gradient(135deg,#ff3b55,#b40016);box-shadow:0 12px 22px rgba(255,59,85,.28);font-family:'DM Sans',Arial,sans-serif}
.admin-close-btn:hover{filter:brightness(1.05);transform:translateY(-1px)}
.admin-close-btn:disabled{cursor:not-allowed;opacity:.45;background:#94a3b8;box-shadow:none}
.siparis-detay-btn{position:relative;z-index:4;width:calc(100% - 28px);margin:0 14px 8px;border:0;border-radius:16px;padding:11px 10px;cursor:pointer;font-weight:950;letter-spacing:.2px;color:#0f172a;background:linear-gradient(135deg,#e0f4ff,#b8e8ff);border:1px solid rgba(16,200,238,.45);box-shadow:0 8px 18px rgba(16,200,238,.18);font-family:'DM Sans',Arial,sans-serif;font-size:13px}
.siparis-detay-btn:hover{filter:brightness(1.05);transform:translateY(-1px);box-shadow:0 10px 22px rgba(16,200,238,.28)}
@media(max-width:768px){.siparis-detay-btn{font-size:8.5px;padding:6px 5px;border-radius:10px;width:calc(100% - 12px);margin:0 6px 6px}}
.modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.58);backdrop-filter:blur(8px);z-index:100;display:none;align-items:center;justify-content:center;padding:16px}
.modal-backdrop.active{display:flex}
.modal-box{width:min(860px,100%);max-height:88vh;overflow:auto;background:linear-gradient(180deg,#fff,#f7fbff);border:1px solid rgba(255,255,255,.72);border-radius:26px;box-shadow:0 30px 80px rgba(15,23,42,.35);padding:18px;color:#0f172a}
.sd-backdrop{position:fixed;inset:0;background:rgba(8,15,30,.72);backdrop-filter:blur(12px);z-index:200;display:none;align-items:center;justify-content:center;padding:16px}
.sd-backdrop.active{display:flex}
.sd-box{width:min(520px,100%);max-height:90vh;overflow:auto;border-radius:28px;background:linear-gradient(160deg,#0f1b2d 0%,#0d2137 60%,#0a1a2e 100%);border:1px solid rgba(16,200,238,.22);box-shadow:0 40px 100px rgba(0,0,0,.60),0 0 0 1px rgba(255,255,255,.06) inset;padding:0;color:#e2f0ff;position:relative;overflow:hidden}
.sd-box:before{content:"";position:absolute;inset:0;background:radial-gradient(ellipse at 70% -10%,rgba(16,200,238,.14),transparent 55%),radial-gradient(ellipse at 10% 100%,rgba(36,232,138,.08),transparent 45%);pointer-events:none;z-index:0}
.sd-inner{position:relative;z-index:1;padding:22px}
.sd-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:20px}
.sd-eyebrow{font-size:10px;font-weight:900;letter-spacing:2px;text-transform:uppercase;color:rgba(16,200,238,.8);margin-bottom:5px}
.sd-masa-no{font-size:26px;font-weight:950;color:#fff;letter-spacing:.5px;line-height:1}
.sd-x{width:36px;height:36px;border:1px solid rgba(255,255,255,.12);border-radius:12px;background:rgba(255,255,255,.06);color:rgba(255,255,255,.7);font-size:22px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:.15s}
.sd-x:hover{background:rgba(255,59,85,.18);border-color:rgba(255,59,85,.4);color:#fff}
.sd-meta{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:20px}
.sd-meta-item{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:10px 12px}
.sd-meta-label{font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:1.2px;color:rgba(16,200,238,.65);margin-bottom:4px}
.sd-meta-val{font-size:13px;font-weight:800;color:#e8f4ff}
.sd-divider{height:1px;background:linear-gradient(90deg,transparent,rgba(16,200,238,.25),transparent);margin:0 0 16px}
.sd-section-title{font-size:10px;font-weight:900;letter-spacing:2px;text-transform:uppercase;color:rgba(16,200,238,.65);margin-bottom:10px}
.sd-list{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
.sd-row{display:flex;justify-content:space-between;align-items:center;gap:10px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:13px;padding:10px 14px;transition:.15s}
.sd-row:hover{background:rgba(16,200,238,.07);border-color:rgba(16,200,238,.18)}
.sd-row-name{font-size:13px;font-weight:800;color:#f0f8ff;display:block}
.sd-row-sub{font-size:11px;color:rgba(200,220,255,.5);margin-top:2px;display:block}
.sd-row-price{font-size:14px;font-weight:900;color:#36e4ff;white-space:nowrap}
.sd-ek-row .sd-row-price{color:#f5b942}
.sd-empty{padding:14px;border-radius:13px;background:rgba(255,59,85,.10);border:1px solid rgba(255,59,85,.18);color:rgba(255,140,150,.9);font-weight:800;text-align:center;font-size:13px}
.sd-total-bar{background:linear-gradient(135deg,rgba(16,200,238,.15),rgba(36,232,138,.08));border:1px solid rgba(16,200,238,.28);border-radius:16px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;margin-top:4px}
.sd-total-label{font-size:11px;font-weight:900;letter-spacing:1.2px;text-transform:uppercase;color:rgba(16,200,238,.7)}
.sd-total-amount{font-family:'Playfair Display',serif;font-size:22px;font-weight:900;color:#fff;text-shadow:0 0 20px rgba(16,200,238,.4)}
.sd-loading{text-align:center;padding:40px 20px;color:rgba(200,230,255,.5);font-size:13px;font-weight:700}
.sd-spinner{width:32px;height:32px;border:3px solid rgba(16,200,238,.15);border-top-color:#10c8ee;border-radius:50%;animation:sd-spin .7s linear infinite;margin:0 auto 12px}
@keyframes sd-spin{to{transform:rotate(360deg)}}
.modal-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
.modal-title{font-size:22px;font-weight:950}.modal-x{width:38px;height:38px;border:0;border-radius:13px;background:#eef2f7;color:#0f172a;font-size:24px;cursor:pointer}
.modal-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
.mini-info{background:#f1f8ff;border:1px solid rgba(51,65,85,.10);border-radius:18px;padding:12px}
.mini-info span{display:block;font-size:11px;color:#64748b;font-weight:900;text-transform:uppercase;margin-bottom:5px}
.mini-info strong{font-size:15px;font-weight:950}.red-text{color:#d6001c!important}
.modal-subtitle{font-size:16px;font-weight:950;margin:16px 0 10px}.modal-subtitle.small{font-size:13px;margin-top:12px}
.order-list{display:flex;flex-direction:column;gap:8px}
.order-row{display:flex;justify-content:space-between;gap:12px;align-items:center;background:#fff;border:1px solid rgba(51,65,85,.10);border-radius:16px;padding:11px 12px}
.order-row b{display:block;font-size:14px}.order-row small{display:block;color:#64748b;margin-top:3px}
.empty-order{padding:16px;border-radius:16px;background:#fff3f4;color:#b40016;font-weight:900;text-align:center}
.close-form{margin-top:16px}
.modal-close-btn{width:100%;border:0;border-radius:18px;padding:15px 14px;cursor:pointer;font-weight:950;font-size:15px;color:#fff;background:linear-gradient(135deg,#ff3b55,#a90014);box-shadow:0 14px 28px rgba(255,59,85,.30)}
.close-panel-layout{display:grid;grid-template-columns:260px 1fr;gap:16px;align-items:start}
.close-table-preview{position:sticky;top:0}
.preview-card{border-radius:24px;background:linear-gradient(180deg,#fff,#eef9ff);border:1px solid rgba(51,65,85,.12);box-shadow:0 16px 32px rgba(15,23,42,.12);padding:14px;text-align:center;overflow:hidden}
.preview-top{display:flex;align-items:center;justify-content:center;gap:8px;font-size:12px;font-weight:950;color:#b40016;margin-bottom:10px}
.preview-dot{width:9px;height:9px;border-radius:99px;background:#ff1744;box-shadow:0 0 12px #ff1744}
.preview-img-wrap{height:150px;display:flex;align-items:center;justify-content:center;position:relative}
.preview-img-wrap:before{content:"";position:absolute;width:78%;height:26px;bottom:10px;left:11%;border-radius:50%;background:rgba(30,41,59,.18);filter:blur(12px)}
.preview-img-wrap img{position:relative;max-width:210px;width:100%;height:142px;object-fit:contain;filter:drop-shadow(0 12px 12px rgba(15,23,42,.20)) brightness(1.08) contrast(1.04)}
.preview-table-no{display:inline-flex;align-items:center;justify-content:center;min-width:136px;height:44px;border-radius:16px;font-size:19px;font-weight:950;color:#fff;background:linear-gradient(135deg,#ff1744,#b40016);box-shadow:0 0 22px rgba(255,23,68,.35);margin-top:8px}
.preview-total{margin-top:12px;font-family:'Playfair Display',serif;font-size:24px;font-weight:900;color:#c88700}
.payment-box{margin:14px 0 12px;background:#f8fbff;border:1px solid rgba(51,65,85,.12);border-radius:17px;padding:12px}
.payment-box label{display:block;font-size:12px;font-weight:950;color:#334155;text-transform:uppercase;margin-bottom:7px}
.payment-box select{width:100%;border:1px solid rgba(51,65,85,.18);border-radius:14px;background:#fff;padding:13px 12px;font-size:15px;font-weight:900;color:#0f172a;outline:none}
.payment-box select:focus{border-color:#ff3b55;box-shadow:0 0 0 3px rgba(255,59,85,.12)}
@media(max-width:1100px){.stats-grid{grid-template-columns:repeat(2,1fr)}.nav-menu{grid-template-columns:repeat(3,1fr)}}
@media(max-width:768px){
  body{background:linear-gradient(180deg,#eef8ff 0%,#f8fbff 100%)}
  .topnav{position:relative;padding:10px 12px;min-height:auto}.brand-icon{width:38px;height:38px;border-radius:13px}.brand-name{font-size:19px}.brand-sub{font-size:10px}.nav-user{display:none}.btn-ghost{padding:9px 11px;font-size:12px}
  .page{padding:10px 8px 24px}.stats-grid{grid-template-columns:repeat(2,1fr);gap:8px}.stat-card{padding:12px;border-radius:17px}.stat-icon{font-size:18px;margin-bottom:4px}.stat-label{font-size:9px}.stat-value{font-size:19px}
  .nav-menu{grid-template-columns:repeat(2,1fr);gap:7px}.nav-item{min-height:45px;border-radius:14px;font-size:11px;padding:8px}.section-title{font-size:18px;margin:12px 2px}
  .masalar-grid{grid-template-columns:repeat(3,1fr);gap:8px}.masa-card{min-height:202px;border-radius:18px}.masa-card:before{border-radius:18px}.masa-card:after{left:14px;right:14px;bottom:7px;height:3px}.card-top{padding:7px 7px 0}.masa-label{font-size:8px;gap:4px;padding:5px 6px;border-radius:10px;letter-spacing:.5px}.masa-label span{width:5px;height:5px}.masa-badge{min-width:auto;font-size:9px;padding:5px 7px;border-radius:10px}.table-wrapper{height:86px;padding:2px 4px 0}.table-image{max-width:104px;height:82px}.masa-main{padding:0 6px 8px}.masa-no{min-width:76px;height:32px;border-radius:11px;font-size:13px;padding:0 7px;letter-spacing:.4px}.masa-detail{font-size:9px;line-height:1.25;min-height:30px;padding:0 6px 11px}.price-line{font-size:12px;margin-top:3px}
  .admin-close-btn{font-size:9px;padding:7px 5px;border-radius:10px;width:calc(100% - 12px);margin:0 6px 10px}
  .close-panel-layout{grid-template-columns:1fr}.close-table-preview{position:relative}.preview-img-wrap{height:110px}.preview-img-wrap img{height:104px}.preview-total{font-size:20px}
  .modal-grid{grid-template-columns:1fr}.modal-box{border-radius:20px;padding:14px}.modal-title{font-size:18px}.order-row{padding:10px}.order-row b{font-size:13px}
  .sd-box{border-radius:22px}.sd-inner{padding:16px}.sd-masa-no{font-size:20px}.sd-meta{grid-template-columns:1fr 1fr;gap:6px}.sd-total-amount{font-size:18px}
}
@media(max-width:390px){.masalar-grid{grid-template-columns:repeat(3,1fr);gap:7px}.masa-card{min-height:196px}.table-image{max-width:96px;height:78px}}
</style>
</head>
<body>

<nav class="topnav">
  <div class="brand">
    <div class="brand-logo-inner">
      <span class="brand-logo-line"></span>
      <div>
        <div class="brand-logo-text">M<span class="brand-logo-spade">♠</span>ÇA KIZI</div>
        <div class="brand-sub-row">
          <span class="brand-sub-line"></span>
          <span class="brand-sub">CAFE &amp; OYUN SALONU</span>
          <span class="brand-sub-line"></span>
        </div>
      </div>
      <span class="brand-logo-line"></span>
    </div>
  </div>
  <div class="nav-right">
    <span class="nav-user">👤 <?php echo htmlspecialchars($_SESSION["ad"]); ?></span>
    <a class="btn-ghost" href="logout.php">Çıkış</a>
  </div>
</nav>

<div class="page">
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#c88700" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div class="stat-label">Günlük Ciro</div><div class="stat-value gold"><?php echo number_format($gunluk["toplam_ciro"], 0, ',', '.'); ?> ₺</div></div>
    <div class="stat-card"><div class="stat-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div><div class="stat-label">Kapalı Adisyon</div><div class="stat-value"><?php echo $gunluk["siparis_sayisi"]; ?></div></div>
    <div class="stat-card"><div class="stat-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ff3b55" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg></div><div class="stat-label">Dolu Masa</div><div class="stat-value"><?php echo $dolu; ?></div></div>
    <div class="stat-card"><div class="stat-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#24e88a" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg></div><div class="stat-label">Boş Masa</div><div class="stat-value"><?php echo $bos; ?></div></div>
  </div>

  <div class="nav-menu">
    <a class="nav-item" href="garson_ekle.php">
      <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
      Garson Yönetimi
    </a>
    <a class="nav-item" href="urun_yonetimi.php">
      <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2h18l-2 7H5z"/><path d="M5 9a7 7 0 0 0 14 0"/><path d="M12 16v6m-3-3h6"/></svg></span>
      Ürün Yönetimi
    </a>
    <a class="nav-item" href="ciro.php">
      <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
      Ciro Raporu
    </a>
    <a class="nav-item" href="adisyon_gecmisi.php">
      <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg></span>
      Adisyon Geçmişi
    </a>
    <a class="nav-item" href="performans.php">
      <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></span>
      Performans
    </a>
    <a class="nav-item" href="masalar.php" target="_blank">
      <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg></span>
      Müşteri Ekranı
    </a>
    <a class="nav-item" href="menu.php" target="_blank">
      <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span>
      Müşteri Menü
    </a>
  </div>

  <div class="section-title">Canlı Masa Durumu</div>
  <div class="masalar-grid" id="masalarGrid">

  <?php while ($m = $masalar->fetch_assoc()):
    $durum  = $m["durum"];
    $masaNo = (int)$m["masa_no"];
    // ✅ dolu/boş görsel
    $gorsel       = masaGorseli($masaNo, $durum);
    $gorselDosya  = $_SERVER["DOCUMENT_ROOT"] . $gorsel;
    $gorselVersiyon = file_exists($gorselDosya) ? filemtime($gorselDosya) : time();
    $gorselSrc    = $gorsel . "?v=" . $gorselVersiyon;
  ?>
    <div class="masa-card <?php echo htmlspecialchars($durum); ?> <?php echo !in_array($masaNo,[1,2,3,4]) ? 'oyun' : ''; ?>">
      <div class="card-top">
        <div class="masa-label"><span></span> Admin Durum</div>
        <div class="masa-badge"><?php echo $durum == "bos" ? "BOŞ" : "DOLU"; ?></div>
      </div>
      <div class="table-wrapper">
        <img class="table-image" src="<?php echo $gorselSrc; ?>" alt="Masa <?php echo $masaNo; ?>">
      </div>
      <div class="masa-main">
        <div class="masa-no"><?php echo htmlspecialchars(getMasaAdi($masaNo)); ?></div>
      </div>
      <div class="masa-detail">
        <?php if ($durum === "dolu"): ?>
          <div><strong><?php echo htmlspecialchars($m["garson_adi"] ?? "Garson"); ?></strong> · <?php echo $m["acilis_tarihi"] ? date("H:i", strtotime($m["acilis_tarihi"])) : "--:--"; ?></div>
          <div class="price-line"><?php echo number_format((float)$m["toplam_tutar"], 2, ',', '.'); ?> ₺</div>
        <?php else: ?>
          <div><strong>Kullanıma hazır</strong></div>
          <div>Müsait</div>
        <?php endif; ?>
      </div>
      <?php if ($durum === "dolu"): ?>
        <button class="siparis-detay-btn" type="button" onclick="openSiparisDetay(<?php echo (int)$m['id']; ?>)">🧾 Sipariş Detaylarını Gör</button>
        <button class="admin-close-btn" type="button" onclick="openMasaPanel(<?php echo (int)$m['id']; ?>)">Masayı Kapat</button>
      <?php else: ?>
        <button class="admin-close-btn" type="button" disabled>Boş Masa</button>
      <?php endif; ?>
    </div>
  <?php endwhile; ?>
  </div>
</div>

<!-- Masa Kapatma Modal -->
<div class="modal-backdrop" id="masaModal" onclick="modalBackdropClose(event)">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-title">Masa Kapatma Paneli</div>
      <button class="modal-x" type="button" onclick="closeMasaPanel()">×</button>
    </div>
    <div id="masaModalContent">Yükleniyor...</div>
  </div>
</div>

<!-- Sipariş Detay Modal -->
<div class="sd-backdrop" id="siparisDetayModal" onclick="sdBackdropClose(event)">
  <div class="sd-box">
    <div class="sd-inner" id="siparisDetayIcerik">
      <div class="sd-loading"><div class="sd-spinner"></div>Yükleniyor...</div>
    </div>
  </div>
</div>

<script>
function openMasaPanel(masaId){
  const modal = document.getElementById('masaModal');
  const content = document.getElementById('masaModalContent');
  modal.classList.add('active');
  content.innerHTML = '<div class="empty-order">Yükleniyor...</div>';
  fetch('masa_detay_ajax.php?masa_id=' + encodeURIComponent(masaId), {cache:'no-store'})
    .then(r => r.json())
    .then(d => { content.innerHTML = d.ok ? d.html : '<div class="empty-order">' + (d.message || 'Bilgi alınamadı') + '</div>'; })
    .catch(() => { content.innerHTML = '<div class="empty-order">Bağlantı hatası. Tekrar deneyin.</div>'; });
}
function closeMasaPanel(){ document.getElementById('masaModal').classList.remove('active'); }
function modalBackdropClose(e){ if(e.target.id === 'masaModal') closeMasaPanel(); }
function tl(n){ return n.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2}) + ' ₺'; }

function openSiparisDetay(masaId){
  const modal = document.getElementById('siparisDetayModal');
  const icerik = document.getElementById('siparisDetayIcerik');
  modal.classList.add('active');
  icerik.innerHTML = '<div class="sd-loading"><div class="sd-spinner"></div>Yükleniyor...</div>';
  fetch('siparis_detay_ajax.php?masa_id=' + encodeURIComponent(masaId), {cache:'no-store'})
    .then(r => r.json())
    .then(d => {
      if(!d.ok){ icerik.innerHTML = '<div class="sd-loading">' + (d.message||'Bilgi alınamadı') + '</div>'; return; }
      let urunSatir = '';
      if(d.urunler.length === 0){
        urunSatir = '<div class="sd-empty">Bu masada henüz ürün yok.</div>';
      } else {
        d.urunler.forEach(u => {
          urunSatir += `<div class="sd-row"><div class="sd-row-left"><span class="sd-row-name">${u.urun_adi}</span><span class="sd-row-sub">${u.adet} adet × ${tl(u.fiyat)}</span></div><span class="sd-row-price">${tl(u.ara_toplam)}</span></div>`;
        });
      }
      let ekSatir = '';
      if(d.ekler.length > 0){
        ekSatir = '<div class="sd-section-title" style="margin-top:14px">Ek Ücretler</div><div class="sd-list">';
        d.ekler.forEach(ek => {
          ekSatir += `<div class="sd-row sd-ek-row"><div class="sd-row-left"><span class="sd-row-name">${ek.aciklama}</span><span class="sd-row-sub">Ek ücret</span></div><span class="sd-row-price">${tl(ek.tutar)}</span></div>`;
        });
        ekSatir += '</div>';
      }
      icerik.innerHTML = `
        <div class="sd-head">
          <div class="sd-title-wrap"><div class="sd-eyebrow">🧾 Sipariş Detayları</div><div class="sd-masa-no">${d.masa_no}</div></div>
          <button class="sd-x" onclick="closeSiparisDetay()">×</button>
        </div>
        <div class="sd-meta">
          <div class="sd-meta-item"><div class="sd-meta-label">Garson</div><div class="sd-meta-val">${d.garson}</div></div>
          <div class="sd-meta-item"><div class="sd-meta-label">Açılış Saati</div><div class="sd-meta-val">${d.acilis}</div></div>
          <div class="sd-meta-item"><div class="sd-meta-label">Ürün Sayısı</div><div class="sd-meta-val">${d.urunler.length} kalem</div></div>
          <div class="sd-meta-item"><div class="sd-meta-label">Ürün Toplamı</div><div class="sd-meta-val">${tl(d.urun_toplam)}</div></div>
        </div>
        <div class="sd-divider"></div>
        <div class="sd-section-title">Siparişler</div>
        <div class="sd-list">${urunSatir}</div>
        ${ekSatir}
        <div class="sd-total-bar">
          <span class="sd-total-label">Genel Toplam</span>
          <span class="sd-total-amount">${d.genel_toplam_yazi}</span>
        </div>`;
    })
    .catch(() => { icerik.innerHTML = '<div class="sd-loading">Bağlantı hatası. Tekrar deneyin.</div>'; });
}
function closeSiparisDetay(){ document.getElementById('siparisDetayModal').classList.remove('active'); }
function sdBackdropClose(e){ if(e.target.id === 'siparisDetayModal') closeSiparisDetay(); }
document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ closeMasaPanel(); closeSiparisDetay(); } });

async function canliMasalariYenile(){
  try{
    const response = await fetch('admin.php?ajax=masalar&_=' + Date.now(), {cache:'no-store'});
    const html = await response.text();
    if(html.trim() !== '') document.getElementById('masalarGrid').innerHTML = html;
  }catch(e){}
}
setInterval(canliMasalariYenile, 2000);

// Her 1 dakikada sessiz sayfa yenileme
setInterval(function(){
  window.location.replace(window.location.href.split('?')[0]);
}, 60 * 1000);
</script>
 <script src="/svimages.js"></script>
</body>
</html>
