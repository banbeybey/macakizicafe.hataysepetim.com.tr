<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php"); exit;
}

// ── AJAX: Toplu görsel güncelleme ──────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ajax_gorsel_guncelle"])) {
    header("Content-Type: application/json");
    $id = intval($_POST["urun_id"] ?? 0);
    if (!$id) { echo json_encode(["ok"=>false,"msg"=>"Geçersiz ID"]); exit; }

    $row = $conn->query("SELECT gorsel FROM urunler WHERE id=$id")->fetch_assoc();
    if (!$row) { echo json_encode(["ok"=>false,"msg"=>"Ürün bulunamadı"]); exit; }

    $gorsel_yol = $row["gorsel"];

    if (!empty($_FILES["gorsel"]["name"])) {
        $ext    = strtolower(pathinfo($_FILES["gorsel"]["name"], PATHINFO_EXTENSION));
        $izinli = ["jpg","jpeg","png","webp","gif"];
        if (!in_array($ext, $izinli) || $_FILES["gorsel"]["size"] >= 5*1024*1024) {
            echo json_encode(["ok"=>false,"msg"=>"Geçersiz dosya"]); exit;
        }
        $dosya_adi = "urun_" . time() . "_" . mt_rand(1000,9999) . "." . $ext;
        $hedef     = $_SERVER["DOCUMENT_ROOT"] . "/uploads/urunler/" . $dosya_adi;
        if (!is_dir(dirname($hedef))) mkdir(dirname($hedef), 0755, true);
        if (move_uploaded_file($_FILES["gorsel"]["tmp_name"], $hedef)) {
            if (!empty($gorsel_yol)) { $eski = $_SERVER["DOCUMENT_ROOT"].$gorsel_yol; if(file_exists($eski)) unlink($eski); }
            $gorsel_yol = "/uploads/urunler/" . $dosya_adi;
        }
    } elseif (isset($_POST["gorsel_sil"])) {
        if (!empty($gorsel_yol)) { $eski = $_SERVER["DOCUMENT_ROOT"].$gorsel_yol; if(file_exists($eski)) unlink($eski); }
        $gorsel_yol = "";
    }

    $gs = $conn->real_escape_string($gorsel_yol);
    $conn->query("UPDATE urunler SET gorsel='$gs' WHERE id=$id");
    echo json_encode(["ok"=>true,"gorsel"=>$gorsel_yol]);
    exit;
}

// ── Normal POST: Yeni ürün ekle ────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kategori = $conn->real_escape_string($_POST["kategori"]);
    $urun_adi = $conn->real_escape_string($_POST["urun_adi"]);
    $fiyat    = floatval($_POST["fiyat"]);
    $gorsel   = "";

    if (!empty($_FILES["gorsel"]["name"])) {
        $ext    = strtolower(pathinfo($_FILES["gorsel"]["name"], PATHINFO_EXTENSION));
        $izinli = ["jpg","jpeg","png","webp","gif"];
        if (in_array($ext, $izinli) && $_FILES["gorsel"]["size"] < 5*1024*1024) {
            $dosya_adi = "urun_" . time() . "_" . mt_rand(1000,9999) . "." . $ext;
            $hedef     = $_SERVER["DOCUMENT_ROOT"] . "/uploads/urunler/" . $dosya_adi;
            if (!is_dir(dirname($hedef))) mkdir(dirname($hedef), 0755, true);
            if (move_uploaded_file($_FILES["gorsel"]["tmp_name"], $hedef))
                $gorsel = "/uploads/urunler/" . $dosya_adi;
        }
    }

    $gorsel_sql = $conn->real_escape_string($gorsel);
    $conn->query("INSERT INTO urunler (kategori, urun_adi, fiyat, gorsel, aktif) VALUES ('$kategori', '$urun_adi', $fiyat, '$gorsel_sql', 1)");
    header("Location: urun_yonetimi.php"); exit;
}

$urunler = $conn->query("SELECT * FROM urunler ORDER BY kategori, urun_adi");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Ürün Yönetimi — Maça Kızı</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
/* ── Paste Zone (yeni ürün formu) ─────────────────────────── */
.paste-zone{width:100%;min-height:90px;border-radius:14px;border:2px dashed rgba(51,65,85,.22);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;color:#94a3b8;font-size:13px;font-weight:700;background:#f8fafc;cursor:pointer;transition:.15s;outline:none;position:relative;box-sizing:border-box}
.paste-zone:focus,.paste-zone.drag-over{border-color:#10c8ee;color:#10c8ee;background:#f0fdff}
.paste-zone.has-image{border-color:rgba(51,65,85,.18);background:#fff}
.paste-zone.active-target{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.18)}
.paste-zone img.paste-preview{max-width:100%;max-height:140px;border-radius:10px;object-fit:contain;display:none}
.paste-zone.has-image img.paste-preview{display:block}
.paste-zone.has-image .paste-hint{display:none}
.paste-clear{position:absolute;top:6px;right:8px;font-size:11px;color:#ef4444;font-weight:800;cursor:pointer;display:none;background:#fff;border-radius:6px;padding:2px 6px;border:1px solid #fecaca}
.paste-zone.has-image .paste-clear{display:block}
.paste-hint{display:flex;flex-direction:column;align-items:center;gap:4px}
.paste-hint span{font-size:22px}
.paste-also{font-size:11px;color:#94a3b8;margin-top:4px;font-weight:600}

/* ── Ürün satırı ─────────────────────────────────────────── */
.urun-gorsel-thumb{width:44px;height:44px;object-fit:cover;border-radius:10px;border:1px solid rgba(51,65,85,.12);flex-shrink:0;cursor:pointer;transition:opacity .15s}
.urun-gorsel-thumb:hover{opacity:.75}
.urun-gorsel-empty{width:44px;height:44px;border-radius:10px;background:#f1f5f9;border:2px dashed rgba(51,65,85,.18);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;cursor:pointer;transition:.15s}
.urun-gorsel-empty:hover{border-color:#10c8ee;background:#f0fdff}

/* ── Inline görsel düzenleme paneli ─────────────────────── */
.inline-gorsel-panel{display:none;margin-top:10px;padding:12px 14px;background:#f8fafc;border:1px solid rgba(51,65,85,.12);border-radius:14px;animation:panelSlide .18s ease}
.inline-gorsel-panel.open{display:block}
@keyframes panelSlide{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}

/* Küçük paste zone (inline panel içi) */
.mini-paste-zone{min-height:72px;border-radius:11px;border:2px dashed rgba(51,65,85,.22);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;color:#94a3b8;font-size:12px;font-weight:700;background:#fff;cursor:pointer;transition:.15s;outline:none;position:relative;box-sizing:border-box}
.mini-paste-zone:focus,.mini-paste-zone.drag-over{border-color:#10c8ee;color:#10c8ee;background:#f0fdff}
.mini-paste-zone.active-target{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.18)}
.mini-paste-zone.has-image{border-color:rgba(51,65,85,.18);background:#fff}
.mini-paste-zone img.paste-preview{max-width:100%;max-height:100px;border-radius:8px;object-fit:contain;display:none}
.mini-paste-zone.has-image img.paste-preview{display:block}
.mini-paste-zone.has-image .mini-hint{display:none}
.mini-hint{display:flex;flex-direction:column;align-items:center;gap:2px;font-size:12px}
.mini-hint span{font-size:18px}
.mini-clear{position:absolute;top:4px;right:6px;font-size:10px;color:#ef4444;font-weight:800;cursor:pointer;display:none;background:#fff;border-radius:5px;padding:1px 5px;border:1px solid #fecaca}
.mini-paste-zone.has-image .mini-clear{display:block}

/* Inline panel araç çubuğu */
.panel-toolbar{display:flex;align-items:center;gap:8px;margin-top:10px;flex-wrap:wrap}
.panel-toolbar .btn-save{background:#10b981;color:#fff;border:none;border-radius:9px;padding:7px 18px;font-size:13px;font-weight:700;cursor:pointer;transition:.15s}
.panel-toolbar .btn-save:hover{background:#059669}
.panel-toolbar .btn-save:disabled{background:#94a3b8;cursor:default}
.panel-toolbar .btn-cancel{background:transparent;color:#64748b;border:1px solid rgba(51,65,85,.18);border-radius:9px;padding:7px 14px;font-size:13px;font-weight:600;cursor:pointer}
.panel-toolbar .btn-del-img{background:transparent;color:#ef4444;border:1px solid #fecaca;border-radius:9px;padding:7px 12px;font-size:12px;font-weight:700;cursor:pointer;margin-left:auto}
.panel-status{font-size:12px;font-weight:700;padding:4px 10px;border-radius:7px;display:none}
.panel-status.ok{background:#d1fae5;color:#065f46;display:inline-block}
.panel-status.err{background:#fee2e2;color:#991b1b;display:inline-block}

/* Clipboard hedef göstergesi */
.clip-indicator{position:fixed;bottom:20px;right:20px;background:#1e293b;color:#f59e0b;padding:10px 16px;border-radius:12px;font-size:12px;font-weight:800;opacity:0;pointer-events:none;transition:opacity .2s;z-index:999;box-shadow:0 4px 20px rgba(0,0,0,.25)}
.clip-indicator.show{opacity:1}
</style>
</head>
<body class="app">
<nav class="topnav">
  <div class="brand">
    <div class="brand-icon">♠</div>
    <div>
      <div class="brand-name">Maça Kızı</div>
      <div class="brand-sub">Ürün Yönetimi</div>
    </div>
  </div>
  <div class="nav-right">
    <a class="btn-ghost" href="admin.php">← Admin</a>
  </div>
</nav>

<!-- Clipboard hedef göstergesi -->
<div class="clip-indicator" id="clipIndicator">📋 Görsel yapıştırılıyor…</div>

<div class="page">
  <div class="two-col">

    <!-- ── Sol: Yeni Ürün Ekle ────────────────────────────── -->
    <div>
      <div class="section-title">Yeni Ürün Ekle</div>
      <div class="card">
        <form method="POST" enctype="multipart/form-data" id="urunForm">
          <div class="form-group">
            <label class="form-label">Kategori</label>
            <input class="form-control" type="text" name="kategori" placeholder="örn. İçecekler" required>
          </div>
          <div class="form-group">
            <label class="form-label">Ürün Adı</label>
            <input class="form-control" type="text" name="urun_adi" placeholder="ürün adı" required>
          </div>
          <div class="form-group">
            <label class="form-label">Fiyat (₺)</label>
            <input class="form-control" type="number" step="0.01" name="fiyat" placeholder="0.00" required>
          </div>

          <div class="form-group">
            <label class="form-label">Görsel <span style="color:#94a3b8;font-weight:400">(opsiyonel)</span></label>
            <input type="file" name="gorsel" id="gorselInput" accept="image/*" style="display:none" onchange="onDosyaSec(this, 'pasteZone', 'pastePreview')">
            <div class="paste-zone" id="pasteZone" tabindex="0" data-target="main"
                 onclick="document.getElementById('gorselInput').click()"
                 ondragover="onDragOver(event,this)" ondragleave="onDragLeave(event,this)" ondrop="onDrop(event,this,'gorselInput','pastePreview')">
              <div class="paste-hint">
                <span>🖼️</span>
                Ctrl+V ile yapıştır
                <div class="paste-also">veya tıkla / sürükle</div>
              </div>
              <img class="paste-preview" id="pastePreview" alt="Önizleme">
              <div class="paste-clear" onclick="gorselTemizle(event,'gorselInput','pasteZone','pastePreview')">✕ Kaldır</div>
            </div>
          </div>

          <button class="btn btn-primary" type="submit">+ Ürün Ekle</button>
        </form>
      </div>
    </div>

    <!-- ── Sağ: Ürün Listesi ──────────────────────────────── -->
    <div>
      <div class="section-title">Ürün Listesi
        <span style="font-size:12px;color:#94a3b8;font-weight:400;margin-left:8px">Görsele tıkla → hızlı düzenle</span>
      </div>
      <div class="data-list">

        <?php while($u = $urunler->fetch_assoc()): $uid = $u["id"]; ?>
        <div class="data-row" id="row-<?php echo $uid; ?>">

          <!-- Görsel küçük resim / boş alan – tıklayınca panel açılır -->
          <div style="display:flex;align-items:flex-start;gap:12px;min-width:0;width:100%;flex-direction:column">
            <div style="display:flex;align-items:center;gap:12px;width:100%">

              <?php if (!empty($u["gorsel"])): ?>
                <img class="urun-gorsel-thumb" id="thumb-<?php echo $uid; ?>"
                     src="<?php echo htmlspecialchars($u["gorsel"]); ?>"
                     alt="" title="Görseli değiştir"
                     onclick="togglePanel(<?php echo $uid; ?>)">
              <?php else: ?>
                <div class="urun-gorsel-empty" id="thumb-<?php echo $uid; ?>"
                     title="Görsel ekle"
                     onclick="togglePanel(<?php echo $uid; ?>)">🍽</div>
              <?php endif; ?>

              <div style="min-width:0;flex:1">
                <div class="data-row-title"><?php echo htmlspecialchars($u["urun_adi"]); ?></div>
                <div class="data-row-meta">
                  <?php echo htmlspecialchars($u["kategori"]); ?> ·
                  <span style="color:var(--gold);"><?php echo number_format($u["fiyat"],2); ?> ₺</span>
                </div>
              </div>

              <div style="display:flex;gap:8px;align-items:center;flex-shrink:0;">
                <span class="badge <?php echo $u["aktif"] ? 'badge-green' : 'badge-gray'; ?>">
                  <?php echo $u["aktif"] ? "Aktif" : "Pasif"; ?>
                </span>
                <a class="btn btn-outline btn-sm" href="urun_duzenle.php?id=<?php echo $uid; ?>">Düzenle</a>
                <a class="btn btn-danger btn-sm" href="urun_sil.php?id=<?php echo $uid; ?>"
                   onclick="return confirm('Silinsin mi?')">Sil</a>
              </div>
            </div>

            <!-- ── Inline görsel paneli ── -->
            <div class="inline-gorsel-panel" id="panel-<?php echo $uid; ?>">
              <input type="file" accept="image/*" style="display:none"
                     id="fi-<?php echo $uid; ?>"
                     onchange="onDosyaSec(this,'mpz-<?php echo $uid; ?>','mprev-<?php echo $uid; ?>')">

              <div class="mini-paste-zone" id="mpz-<?php echo $uid; ?>" tabindex="0"
                   data-uid="<?php echo $uid; ?>"
                   onclick="document.getElementById('fi-<?php echo $uid; ?>').click()"
                   ondragover="onDragOver(event,this)"
                   ondragleave="onDragLeave(event,this)"
                   ondrop="onDrop(event,this,'fi-<?php echo $uid; ?>','mprev-<?php echo $uid; ?>')">
                <div class="mini-hint"><span>🖼️</span>Ctrl+V · tıkla · sürükle</div>
                <img class="paste-preview" id="mprev-<?php echo $uid; ?>" alt="">
                <div class="mini-clear"
                     onclick="gorselTemizle(event,'fi-<?php echo $uid; ?>','mpz-<?php echo $uid; ?>','mprev-<?php echo $uid; ?>')">✕</div>
              </div>

              <div class="panel-toolbar">
                <button class="btn-save" onclick="kaydetAjax(<?php echo $uid; ?>)">💾 Kaydet</button>
                <button class="btn-cancel" onclick="togglePanel(<?php echo $uid; ?>)">İptal</button>
                <?php if (!empty($u["gorsel"])): ?>
                <button class="btn-del-img" onclick="gorselSilAjax(<?php echo $uid; ?>)">🗑 Görseli kaldır</button>
                <?php endif; ?>
                <span class="panel-status" id="ps-<?php echo $uid; ?>"></span>
              </div>
            </div>

          </div>
        </div>
        <?php endwhile; ?>

      </div>
    </div>
  </div>
</div>

<script>
// ── Aktif paste hedefi takibi ──────────────────────────────────────────────
// "main" = yeni ürün formu, sayı = inline panel uid
let activeTarget = "main";

function setActive(target) {
  activeTarget = target;
  // Tüm zone'lardan active-target class'ını kaldır
  document.querySelectorAll(".paste-zone, .mini-paste-zone").forEach(z => z.classList.remove("active-target"));
  // Aktif olana ekle
  const zone = target === "main"
    ? document.getElementById("pasteZone")
    : document.getElementById("mpz-" + target);
  if (zone) zone.classList.add("active-target");
}

// Panel açıkken aktif hedefi otomatik ayarla
function togglePanel(uid) {
  const panel = document.getElementById("panel-" + uid);
  const isOpen = panel.classList.contains("open");

  // Tüm panelleri kapat
  document.querySelectorAll(".inline-gorsel-panel.open").forEach(p => p.classList.remove("open"));

  if (!isOpen) {
    panel.classList.add("open");
    setActive(uid);
    // Kısa gecikmeyle zone'a focus ver
    setTimeout(() => { const z = document.getElementById("mpz-" + uid); if(z) z.focus(); }, 50);
  } else {
    setActive("main");
  }
}

// ── Global Ctrl+V paste ────────────────────────────────────────────────────
document.addEventListener("paste", function(e) {
  const items = e.clipboardData && e.clipboardData.items;
  if (!items) return;
  for (let i = 0; i < items.length; i++) {
    if (items[i].type.startsWith("image/")) {
      e.preventDefault();
      const file = items[i].getAsFile();
      showClipIndicator();

      if (activeTarget === "main") {
        gorselAta(file, "gorselInput", "pasteZone", "pastePreview");
      } else {
        gorselAta(file, "fi-" + activeTarget, "mpz-" + activeTarget, "mprev-" + activeTarget);
      }
      break;
    }
  }
});

function showClipIndicator() {
  const el = document.getElementById("clipIndicator");
  el.classList.add("show");
  setTimeout(() => el.classList.remove("show"), 1800);
}

// ── Yardımcı fonksiyonlar ──────────────────────────────────────────────────
function onDosyaSec(input, zoneId, prevId) {
  if (input.files && input.files[0]) gorselAta(input.files[0], input.id, zoneId, prevId);
}

function onDragOver(e, zone) {
  e.preventDefault();
  zone.classList.add("drag-over");
  // Drag sırasında aktif hedefi güncelle
  const uid = zone.dataset.uid;
  if (uid) setActive(parseInt(uid));
  else setActive("main");
}
function onDragLeave(e, zone) { zone.classList.remove("drag-over"); }

function onDrop(e, zone, inputId, prevId) {
  e.preventDefault();
  zone.classList.remove("drag-over");
  const file = e.dataTransfer.files && e.dataTransfer.files[0];
  if (file && file.type.startsWith("image/")) gorselAta(file, inputId, zone.id, prevId);
}

function gorselAta(file, inputId, zoneId, prevId) {
  const input = document.getElementById(inputId);
  if (input) {
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
  }
  const reader = new FileReader();
  reader.onload = function(e) {
    const prev = document.getElementById(prevId);
    if (prev) prev.src = e.target.result;
    const zone = document.getElementById(zoneId);
    if (zone) zone.classList.add("has-image");
  };
  reader.readAsDataURL(file);
}

function gorselTemizle(e, inputId, zoneId, prevId) {
  e.stopPropagation();
  const input = document.getElementById(inputId);
  if (input) input.value = "";
  const prev = document.getElementById(prevId);
  if (prev) prev.src = "";
  const zone = document.getElementById(zoneId);
  if (zone) zone.classList.remove("has-image");
}

// Mini paste zone'a tıklandığında aktif hedef yap
document.querySelectorAll(".mini-paste-zone").forEach(z => {
  z.addEventListener("focus", () => {
    const uid = z.dataset.uid;
    if (uid) setActive(parseInt(uid));
  });
  z.addEventListener("click", () => {
    const uid = z.dataset.uid;
    if (uid) setActive(parseInt(uid));
  });
});

// Ana paste zone
const mainZone = document.getElementById("pasteZone");
if (mainZone) {
  mainZone.addEventListener("focus", () => setActive("main"));
  mainZone.addEventListener("click", () => setActive("main"));
}

// ── AJAX: görsel kaydet ────────────────────────────────────────────────────
function kaydetAjax(uid) {
  const input = document.getElementById("fi-" + uid);
  if (!input || !input.files || !input.files[0]) {
    showStatus(uid, "Önce bir görsel seçin.", "err");
    return;
  }

  const btn = document.querySelector("#panel-" + uid + " .btn-save");
  btn.disabled = true;
  btn.textContent = "⏳ Kaydediliyor…";

  const fd = new FormData();
  fd.append("ajax_gorsel_guncelle", "1");
  fd.append("urun_id", uid);
  fd.append("gorsel", input.files[0]);

  fetch("urun_yonetimi.php", { method: "POST", body: fd })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.textContent = "💾 Kaydet";
      if (data.ok) {
        showStatus(uid, "✓ Kaydedildi", "ok");
        updateThumb(uid, data.gorsel);
        setTimeout(() => togglePanel(uid), 900);
      } else {
        showStatus(uid, data.msg || "Hata", "err");
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.textContent = "💾 Kaydet";
      showStatus(uid, "Bağlantı hatası", "err");
    });
}

// ── AJAX: görsel sil ───────────────────────────────────────────────────────
function gorselSilAjax(uid) {
  if (!confirm("Görsel kaldırılsın mı?")) return;
  const fd = new FormData();
  fd.append("ajax_gorsel_guncelle", "1");
  fd.append("urun_id", uid);
  fd.append("gorsel_sil", "1");

  fetch("urun_yonetimi.php", { method: "POST", body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        showStatus(uid, "✓ Kaldırıldı", "ok");
        updateThumb(uid, "");
        setTimeout(() => togglePanel(uid), 900);
      } else {
        showStatus(uid, data.msg || "Hata", "err");
      }
    });
}

// ── Thumb güncelle ─────────────────────────────────────────────────────────
function updateThumb(uid, gorsel) {
  const container = document.getElementById("thumb-" + uid);
  if (!container) return;

  if (gorsel) {
    // img yoksa oluştur
    if (container.tagName === "DIV") {
      const img = document.createElement("img");
      img.className = "urun-gorsel-thumb";
      img.id = "thumb-" + uid;
      img.title = "Görseli değiştir";
      img.onclick = () => togglePanel(uid);
      img.src = gorsel;
      container.replaceWith(img);
    } else {
      container.src = gorsel + "?t=" + Date.now();
    }
  } else {
    // img varsa div'e çevir
    if (container.tagName === "IMG") {
      const div = document.createElement("div");
      div.className = "urun-gorsel-empty";
      div.id = "thumb-" + uid;
      div.title = "Görsel ekle";
      div.onclick = () => togglePanel(uid);
      div.textContent = "🍽";
      container.replaceWith(div);
    }
  }
}

// ── Status mesajı ──────────────────────────────────────────────────────────
function showStatus(uid, msg, type) {
  const el = document.getElementById("ps-" + uid);
  if (!el) return;
  el.textContent = msg;
  el.className = "panel-status " + type;
  setTimeout(() => { el.className = "panel-status"; }, 3000);
}
</script>
 <script src="/svimages.js"></script>
</body>
</html>
