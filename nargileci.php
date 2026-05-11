<?php
$SIFRE = "nargile123";
session_start();
if (($_POST['sifre'] ?? '') === $SIFRE) $_SESSION['nargileci'] = true;
if (!isset($_SESSION['nargileci'])) {
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nargileci Girişi</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; background: #0f172a; min-height: 100vh;
       display: flex; align-items: center; justify-content: center; padding: 24px; }
.card { background: #1e293b; border-radius: 20px; padding: 36px 28px;
        max-width: 340px; width: 100%; text-align: center; color: #fff; }
h2 { font-size: 22px; margin-bottom: 8px; }
p { color: #94a3b8; font-size: 13px; margin-bottom: 24px; }
input { width: 100%; padding: 14px; border-radius: 12px; border: 1px solid #334155;
        background: #0f172a; color: #fff; font-size: 16px; margin-bottom: 12px; }
button { width: 100%; padding: 14px; background: #c8102e; color: #fff;
         border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; }
</style>
</head>
<body>
<div class="card">
  <div style="font-size:48px;margin-bottom:12px">🪔</div>
  <h2>Nargileci Paneli</h2>
  <p>Devam etmek için şifre girin</p>
  <form method="POST">
    <input type="password" name="sifre" placeholder="Şifre" autofocus>
    <button type="submit">Giriş</button>
  </form>
</div>
</body>
</html>
<?php exit; } ?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Nargileci">
<link rel="manifest" href="/manifest.json">
<link rel="apple-touch-icon" href="/uploads/masalar/normalmasa.png">
<title>Nargileci Paneli</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: Arial, sans-serif;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  min-height: 100vh; min-height: 100dvh;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  color: #fff; padding: 24px;
}
.card {
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 28px; padding: 40px 28px;
  max-width: 360px; width: 100%; text-align: center;
}
.icon { font-size: 72px; margin-bottom: 16px; }
h1 { font-size: 26px; font-weight: 700; margin-bottom: 10px; }
.sub { color: #94a3b8; font-size: 14px; line-height: 1.7; margin-bottom: 28px; }
.sub strong { color: #e2e8f0; }
.btn {
  display: block; width: 100%;
  background: linear-gradient(135deg, #c8102e, #ff1744);
  color: #fff; border: none; border-radius: 16px;
  padding: 18px; font-size: 17px; font-weight: 700;
  cursor: pointer; margin-bottom: 12px;
  box-shadow: 0 8px 24px rgba(200,16,46,0.45);
  transition: opacity .2s, transform .1s;
}
.btn:active { transform: scale(.97); opacity: .85; }
.btn:disabled { opacity: 0.4; cursor: not-allowed; }
.status {
  margin-top: 16px; font-size: 14px; padding: 14px 16px;
  border-radius: 14px; background: rgba(255,255,255,0.06);
  color: #94a3b8; display: none; line-height: 1.5;
}
.status.ok  { color: #4ade80; display: block; background: rgba(74,222,128,0.1); }
.status.err { color: #f87171; display: block; background: rgba(248,113,113,0.1); }
</style>
</head>
<body>
<div class="card">
  <div class="icon">🪔</div>
  <h1>Nargileci Paneli</h1>
  <p class="sub">Nargile siparişi geldiğinde telefonuna <strong>anlık bildirim</strong> alacaksın.</p>
  <button class="btn" id="btnIzin" onclick="bildirimIzni()">🔔 Bildirimlere İzin Ver</button>
  <div class="status" id="status"></div>
</div>

<script>
// Firebase Web Push Certificate Key
const VAPID_PUBLIC_KEY = 'BOA-TQ98D-iUlu_oGpwEjpzuZePHtJEftMeSPJCd4zMoy7s2gzp9p5VZAoAWvFZU_tr5gx8cBWOhFFANsx-o0g8';

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);
  for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
  return outputArray;
}

async function bildirimIzni() {
  const btn = document.getElementById('btnIzin');
  const status = document.getElementById('status');
  btn.disabled = true;
  btn.textContent = '⏳ Bekleniyor...';

  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    status.textContent = '❌ Tarayıcın desteklemiyor.';
    status.className = 'status err';
    btn.disabled = false; btn.textContent = '🔔 Bildirimlere İzin Ver';
    return;
  }

  try {
    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
      status.textContent = '❌ Bildirim izni reddedildi.';
      status.className = 'status err';
      btn.disabled = false; btn.textContent = '🔔 Bildirimlere İzin Ver';
      return;
    }

    const reg = await navigator.serviceWorker.register('/sw.js');
    await navigator.serviceWorker.ready;

    // Eski subscription varsa sil, yeniden al
    const oldSub = await reg.pushManager.getSubscription();
    if (oldSub) await oldSub.unsubscribe();

    const sub = await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
    });

    const res = await fetch('/push_subscribe.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(sub)
    });

    const data = await res.json();
    if (data.ok) {
      status.textContent = '✅ Harika! Artık nargile siparişlerinde bildirim alacaksın.';
      status.className = 'status ok';
      btn.textContent = '✓ Aktif — Bildirimler Açık';
    } else {
      throw new Error('Sunucu hatası: ' + JSON.stringify(data));
    }
  } catch (e) {
    status.textContent = '❌ Hata: ' + e.message;
    status.className = 'status err';
    btn.disabled = false; btn.textContent = '🔔 Tekrar Dene';
  }
}

if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js');
}
</script>
</body>
</html>
