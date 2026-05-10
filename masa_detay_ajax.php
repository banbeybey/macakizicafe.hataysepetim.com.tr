<?php
session_start();
require_once "db.php";
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    http_response_code(403);
    echo json_encode(["ok" => false, "message" => "Yetkisiz işlem"]);
    exit;
}

$masa_id = intval($_GET["masa_id"] ?? 0);

$masa = $conn->query("SELECT * FROM masalar WHERE id = $masa_id")->fetch_assoc();
if (!$masa || $masa["durum"] !== "dolu" || empty($masa["aktif_adisyon_id"])) {
    echo json_encode(["ok" => false, "message" => "Bu masa şu anda dolu değil."]);
    exit;
}

$adisyon_id = intval($masa["aktif_adisyon_id"]);
$adisyon = $conn->query("SELECT a.*, k.ad AS garson_adi FROM adisyonlar a LEFT JOIN kullanicilar k ON a.garson_id = k.id WHERE a.id = $adisyon_id")->fetch_assoc();

$urunler = [];
$urun_toplam = 0;
$q = $conn->query("SELECT urun_adi, adet, fiyat, (adet*fiyat) AS ara_toplam FROM adisyon_detaylari WHERE adisyon_id = $adisyon_id ORDER BY id ASC");
while($r = $q->fetch_assoc()){
    $r["adet"] = (int)$r["adet"];
    $r["fiyat"] = (float)$r["fiyat"];
    $r["ara_toplam"] = (float)$r["ara_toplam"];
    $urun_toplam += $r["ara_toplam"];
    $urunler[] = $r;
}

$ekler = [];
$ek_toplam = 0;
$e = $conn->query("SELECT aciklama, tutar FROM ek_ucretler WHERE adisyon_id = $adisyon_id ORDER BY id ASC");
while($r = $e->fetch_assoc()){
    $r["tutar"] = (float)$r["tutar"];
    $ek_toplam += $r["tutar"];
    $ekler[] = $r;
}

$genel_toplam = $urun_toplam + $ek_toplam;
$conn->query("UPDATE adisyonlar SET toplam_tutar = $genel_toplam WHERE id = $adisyon_id");

function tl($n){ return number_format((float)$n, 2, ',', '.') . ' ₺'; }

$masa_no_yazi = 'MASA '.str_pad((int)$masa["masa_no"],2,"0",STR_PAD_LEFT);
$masa_no_int = (int)$masa["masa_no"];
$gorsel = in_array($masa_no_int, [1,2,3,4]) ? "/uploads/masalar/dolulocamasa.png" : "/uploads/masalar/dolunormalmasa.png";

$html = '<div class="close-panel-layout">';
$html .= '<div class="close-table-preview">';
$html .= '<div class="masa-card dolu" style="min-height:unset;cursor:default">';
$html .= '<div class="card-top"><div class="masa-label"><span></span> Admin Durum</div><div class="masa-badge">DOLU</div></div>';
$html .= '<div class="table-wrapper"><img class="table-image" src="'.htmlspecialchars($gorsel, ENT_QUOTES, 'UTF-8').'" alt="'.$masa_no_yazi.'"></div>';
$html .= '<div class="masa-main"><div class="masa-no">'.$masa_no_yazi.'</div></div>';
$html .= '<div class="masa-detail"><div><strong>'.htmlspecialchars($adisyon["garson_adi"] ?? "Garson", ENT_QUOTES, 'UTF-8').'</strong> · '.date('H:i', strtotime($adisyon["acilis_tarihi"])).'</div><div class="price-line">'.tl($genel_toplam).'</div></div>';
$html .= '</div>';
$html .= '</div>';
$html .= '<div class="close-detail-area">';
$html .= '<div class="modal-grid">';
$html .= '<div class="mini-info"><span>Masa</span><strong>'.$masa_no_yazi.'</strong></div>';
$html .= '<div class="mini-info"><span>Garson</span><strong>'.htmlspecialchars($adisyon["garson_adi"] ?? '-', ENT_QUOTES, 'UTF-8').'</strong></div>';
$html .= '<div class="mini-info"><span>Sipariş Tarihi</span><strong>'.date('d.m.Y H:i', strtotime($adisyon["acilis_tarihi"])).'</strong></div>';
$html .= '<div class="mini-info"><span>Ödenecek Tutar</span><strong class="red-text">'.tl($genel_toplam).'</strong></div>';
$html .= '</div>';
$html .= '<div class="modal-subtitle">Sipariş Detayları</div>';
$html .= '<div class="order-list">';
if(count($urunler) === 0){
    $html .= '<div class="empty-order">Bu masada ürün yok.</div>';
}else{
    foreach($urunler as $u){
        $html .= '<div class="order-row"><div><b>'.htmlspecialchars($u["urun_adi"], ENT_QUOTES, 'UTF-8').'</b><small>'.$u["adet"].' adet × '.tl($u["fiyat"]).'</small></div><strong>'.tl($u["ara_toplam"]).'</strong></div>';
    }
}
if(count($ekler) > 0){
    $html .= '<div class="modal-subtitle small">Ek Ücretler</div>';
    foreach($ekler as $ek){
        $html .= '<div class="order-row"><div><b>'.htmlspecialchars($ek["aciklama"], ENT_QUOTES, 'UTF-8').'</b><small>Ek ücret</small></div><strong>'.tl($ek["tutar"]).'</strong></div>';
    }
}
$html .= '</div>';
$html .= '<form method="POST" action="hesap_kapat.php" class="close-form">';
$html .= '<input type="hidden" name="masa_id" value="'.$masa_id.'">';
$html .= '<input type="hidden" name="adisyon_id" value="'.$adisyon_id.'">';
$html .= '<div class="payment-box"><label for="odeme_yontemi">Ödeme Yöntemi</label><select id="odeme_yontemi" name="odeme_yontemi" required><option value="Nakit">Nakit</option><option value="Kredi Kartı">Kredi Kartı</option><option value="IBAN">IBAN</option></select></div>';
$html .= '<button class="modal-close-btn" type="submit">Masayı Kapat · '.tl($genel_toplam).'</button>';
$html .= '</form>';
$html .= '</div>';
$html .= '</div>';

echo json_encode(["ok" => true, "html" => $html]);
