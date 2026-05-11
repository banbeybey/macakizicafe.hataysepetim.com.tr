<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php"); exit;
}

$id   = intval($_GET["id"] ?? 0);
$urun = $conn->query("SELECT * FROM urunler WHERE id=$id")->fetch_assoc();
if (!$urun) die("Ürün bulunamadı.");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kategori   = $conn->real_escape_string($_POST["kategori"]);
    $urun_adi   = $conn->real_escape_string($_POST["urun_adi"]);
    $fiyat      = floatval($_POST["fiyat"]);
    $aktif      = intval($_POST["aktif"]);
    $gorsel_sql = $conn->real_escape_string($urun["gorsel"] ?? "");

    if (!empty($_FILES["gorsel"]["name"])) {
        $ext    = strtolower(pathinfo($_FILES["gorsel"]["name"], PATHINFO_EXTENSION));
        $izinli = ["jpg","jpeg","png","webp","gif"];
        if (in_array($ext, $izinli) && $_FILES["gorsel"]["size"] < 5*1024*1024) {
            $dosya_adi = "urun_" . time() . "_" . mt_rand(1000,9999) . "." . $ext;
            $hedef     = $_SERVER["DOCUMENT_ROOT"] . "/uploads/urunler/" . $dosya_adi;
            if (!is_dir(dirname($hedef))) mkdir(dirname($hedef), 0755, true);
            if (move_uploaded_file($_FILES["gorsel"]["tmp_name"], $hedef)) {
                if (!empty($urun["gorsel"])) { $eski = $_SERVER["DOCUMENT_ROOT"].$urun["gorsel"]; if(file_exists($eski)) unlink($eski); }
                $gorsel_sql = $conn->real_escape_string("/uploads/urunler/" . $dosya_adi);
            }
        }
    } elseif (isset($_POST["gorsel_sil"])) {
        if (!empty($urun["gorsel"])) { $eski = $_SERVER["DOCUMENT_ROOT"].$urun["gorsel"]; if(file_exists($eski)) unlink($eski); }
        $gorsel_sql = "";
    }

    $conn->query("UPDATE urunler SET kategori='$kategori', urun_adi='$urun_adi', fiyat=$fiyat, aktif=$aktif, gorsel='$gorsel_sql' WHERE id=$id");
    header("Location: urun_yonetimi.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Ürün Düzenle — Maça Kızı</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.gorsel-mevcut{display:flex;align-items:flex-start;gap:14px;padding:12px;background:#f8fafc;border:1px solid rgba(51,65,85,.12);border-radius:14px;margin-bottom:10px}
.gorsel-mevcut img{width:90px;height:90px;object-fit:cover;border-radius:10px;border:1px solid rgba(51,65,85,.12)}
.gorsel-mevcut-info{font-size:12px;color:#64748b;font-weight:700}
.gorsel-mevcut-info b{display:block;color:#0f172a;margin-bottom:4px;font-size:13px}
.gorsel-sil-chk{display:flex;align-items:center;gap:6px;margin-top:8px;font-size:12px;color:#ef4444;font-weight:800;cursor:pointer}

/* ── Paste zone ────────────────────────────────────── */
.paste-zone{width:100%;min-height:90px;border-radius:14px;border:2px dashed rgba(51,65,85,.22);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;color:#94a3b8;font-size:13px;font-weight:700;background:#f8fafc;cursor:pointer;transition:.15s;outline:none;position:relative;box-sizing:border-box}
.paste-zone:focus,.paste-zone.drag-over{border-color:#10c8ee;color:#10c8ee;background:#f0fdff}
.paste-zone.has-image{border-color:rgba(51,65,85,.18);background:#fff}
.paste-zone.active-target{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.18);color:#f59e0b}
.paste-zone img.paste-preview{max-width:100%;max-height:140px;border-radius:10px;object-fit:contain;display:none}
.paste-zone.has-image img.paste-preview{display:block}
.paste-zone.has-image .paste-hint{display:none}
.paste-clear{position:absolute;top:6px;right:8px;font-size:11px;color:#ef4444;font-weight:800;cursor:pointer;display:none;background:#fff;border-radius:6px;padding:2px 6px;border:1px solid #fecaca}
.paste-zone.has-image .paste-clear{display:block}
.paste-hint{display:flex;flex-direction:column;align-items:center;gap:4px}
.paste-hint span{font-size:22px}
.paste-label{font-weight:700}
.paste-also{font-size:11px;color:#94a3b8;margin-top:4px;font-weight:600}

/* Klavye kısayol badge */
.kbd-hint{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:#64748b;font-weight:600;margin-top:6px}
.kbd{background:#e2e8f0;border:1px solid #cbd5e1;border-bottom:2px solid #94a3b8;border-radius:5px;padding:1px 6px;font-family:monospace;font-size:11px;color:#334155}

/* Sürükle/bırak genel overlay (sayfaya dosya sürüklenince) */
.drop-overlay{display:none;position:fixed;inset:0;background:rgba(16,200,238,.08);border:3px dashed #10c8ee;z-index:900;border-radius:0;pointer-events:none;transition:opacity .2s}
.drop-overlay.visible{display:block}
.drop-overlay-msg{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:20px 32px;border-radius:18px;font-size:18px;font-weight:800;color:#10c8ee;box-shadow:0 8px 40px rgba(0,0,0,.12)}
</style>
</head>
<body class="app">

<!-- Sayfa genelinde sürükle/bırak overlay -->
<div class="drop-overlay" id="dropOverlay">
  <div class="drop-overlay-msg">🖼️ Görseli bırak</div>
</div>

<nav class="topnav">
  <div class="brand">
    <div class="brand-icon">♠</div>
    <div>
      <div class="brand-name">Maça Kızı</div>
      <div class="brand-sub">Ürün Düzenle</div>
    </div>
  </div>
  <div class="nav-right">
    <a class="btn-ghost" href="urun_yonetimi.php">← Ürünler</a>
  </div>
</nav>

<div class="page">
  <div style="max-width:480px;margin:0 auto;">
    <div class="page-title" style="margin-bottom:24px;">Ürünü Düzenle</div>
    <div class="card">
      <form method="POST" enctype="multipart/form-data" id="duzenleForm">

        <div class="form-group">
          <label class="form-label">Kategori</label>
          <input class="form-control" type="text" name="kategori" value="<?php echo htmlspecialchars($urun["kategori"]); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Ürün Adı</label>
          <input class="form-control" type="text" name="urun_adi" value="<?php echo htmlspecialchars($urun["urun_adi"]); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Fiyat (₺)</label>
          <input class="form-control" type="number" step="0.01" name="fiyat" value="<?php echo $urun["fiyat"]; ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Durum</label>
          <select class="form-control" name="aktif">
            <option value="1" <?php if($urun["aktif"]==1) echo "selected"; ?>>Aktif</option>
            <option value="0" <?php if($urun["aktif"]==0) echo "selected"; ?>>Pasif</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Görsel</label>

          <?php if (!empty($urun["gorsel"])): ?>
          <div class="gorsel-mevcut" id="mevcut-gorsel-wrap">
            <img src="<?php echo htmlspecialchars($urun["gorsel"]); ?>" alt="Mevcut görsel" id="mevcut-gorsel-img">
            <div class="gorsel-mevcut-info">
              <b>Mevcut Görsel</b>
              Değiştirmek için aşağıya yeni görsel yapıştır veya seç.
              <label class="gorsel-sil-chk">
                <input type="checkbox" name="gorsel_sil" value="1" id="gorsel_sil_chk"> Görseli kaldır
              </label>
            </div>
          </div>
          <?php endif; ?>

          <input type="file" name="gorsel" id="gorselInput" accept="image/*" style="display:none"
                 onchange="onDosyaSec(this)">

          <div class="paste-zone active-target" id="pasteZone" tabindex="0"
               onclick="triggerPick()"
               ondragover="onDragOver(event)" ondragleave="onDragLeave(event)" ondrop="onDrop(event)">
            <div class="paste-hint">
              <span>🖼️</span>
              <span class="paste-label">Ctrl+V ile yapıştır</span>
              <div class="paste-also">veya tıkla / sürükle</div>
            </div>
            <img class="paste-preview" id="pastePreview" alt="Önizleme">
            <div class="paste-clear" id="pasteClear" onclick="gorselTemizle(event)">✕ Kaldır</div>
          </div>

          <div class="kbd-hint" style="justify-content:center">
            <span class="kbd">Ctrl</span>+<span class="kbd">V</span>
            <span>ile panodan yapıştır — her zaman aktif</span>
          </div>
        </div>

        <button class="btn btn-primary" type="submit">Kaydet</button>
      </form>
    </div>
  </div>
</div>

<script>
// ── Bu sayfada paste zone her zaman aktif (tek form) ──────────────────────

// Ctrl+V: metin alanı odakta değilse yakala
document.addEventListener("paste", function(e) {
  const tag = document.activeElement ? document.activeElement.tagName : "";
  // input/textarea/select odakta ise müdahale etme
  if (tag === "INPUT" || tag === "TEXTAREA" || tag === "SELECT") return;

  const items = e.clipboardData && e.clipboardData.items;
  if (!items) return;
  for (let i = 0; i < items.length; i++) {
    if (items[i].type.startsWith("image/")) {
      e.preventDefault();
      gorselAta(items[i].getAsFile());
      break;
    }
  }
});

// input odakta iken de Ctrl+V görsel yapıştırma – global keydown ile
document.addEventListener("keydown", function(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === "v") {
    // Kısa gecikme: paste event'i tetikle, görüntü içeriyorsa yakalarız
    // (Yukarıdaki paste listener halleder, bu sadece görsel hint için)
    document.getElementById("pasteZone").classList.add("active-target");
  }
});

function triggerPick() {
  document.getElementById("gorselInput").click();
}

function onDosyaSec(input) {
  if (input.files && input.files[0]) gorselAta(input.files[0]);
}

// ── Sayfa geneli sürükle/bırak ────────────────────────────────────────────
let dragCounter = 0;
document.addEventListener("dragenter", function(e) {
  if ([...e.dataTransfer.types].includes("Files")) {
    dragCounter++;
    document.getElementById("dropOverlay").classList.add("visible");
  }
});
document.addEventListener("dragleave", function() {
  dragCounter--;
  if (dragCounter <= 0) { dragCounter = 0; document.getElementById("dropOverlay").classList.remove("visible"); }
});
document.addEventListener("dragover", function(e) { e.preventDefault(); });
document.addEventListener("drop", function(e) {
  e.preventDefault();
  dragCounter = 0;
  document.getElementById("dropOverlay").classList.remove("visible");
  const file = e.dataTransfer.files && e.dataTransfer.files[0];
  if (file && file.type.startsWith("image/")) gorselAta(file);
});

// ── Paste zone özgün drag ─────────────────────────────────────────────────
function onDragOver(e) {
  e.preventDefault();
  document.getElementById("pasteZone").classList.add("drag-over");
}
function onDragLeave(e) {
  document.getElementById("pasteZone").classList.remove("drag-over");
}
function onDrop(e) {
  e.preventDefault();
  document.getElementById("pasteZone").classList.remove("drag-over");
  const file = e.dataTransfer.files && e.dataTransfer.files[0];
  if (file && file.type.startsWith("image/")) gorselAta(file);
}

// ── Görsel ata ────────────────────────────────────────────────────────────
function gorselAta(file) {
  // File input'a aktar
  const dt = new DataTransfer();
  dt.items.add(file);
  document.getElementById("gorselInput").files = dt.files;

  // Mevcut görsel kutusunu güncelle (varsa önizleme)
  const mevcut = document.getElementById("mevcut-gorsel-img");

  const reader = new FileReader();
  reader.onload = function(ev) {
    // Paste zone önizleme
    document.getElementById("pastePreview").src = ev.target.result;
    document.getElementById("pasteZone").classList.add("has-image");

    // Mevcut görsel kutusundaki img'yi de güncelle (canlı önizleme)
    if (mevcut) mevcut.src = ev.target.result;

    // "Görseli kaldır" checkbox'ı varsa temizle
    const chk = document.getElementById("gorsel_sil_chk");
    if (chk) chk.checked = false;
  };
  reader.readAsDataURL(file);
}

function gorselTemizle(e) {
  e.stopPropagation();
  document.getElementById("gorselInput").value = "";
  document.getElementById("pastePreview").src = "";
  document.getElementById("pasteZone").classList.remove("has-image");
}
</script>
 <script src="/svimages.js"></script>
</body>
</html>
