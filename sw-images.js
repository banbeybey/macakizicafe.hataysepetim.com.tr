// sw-images.js — Maça Kızı Görsel Cache Service Worker
// Strateji: Cache-first + arka planda güncelleme (Stale-While-Revalidate)
// Görsel URL değişince (yeni ?v= parametresi) otomatik yeniler.

const CACHE_NAME = "macakizi-images-v1";

// Cache'lenecek yol prefixleri
const IMAGE_PATHS = ["/uploads/urunler/", "/uploads/masalar/"];

function isImage(url) {
  const u = new URL(url);
  return IMAGE_PATHS.some(p => u.pathname.startsWith(p));
}

// ── Install: önceki cache'i temizle ──────────────────────────────────────
self.addEventListener("install", e => {
  self.skipWaiting();
});

// ── Activate: eski cache versiyonlarını sil ──────────────────────────────
self.addEventListener("activate", e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(k => k !== CACHE_NAME)
          .map(k => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

// ── Fetch: görsel isteği gelince cache-first + revalidate ─────────────────
self.addEventListener("fetch", e => {
  if (e.request.method !== "GET") return;
  if (!isImage(e.request.url)) return;

  e.respondWith(staleWhileRevalidate(e.request));
});

async function staleWhileRevalidate(request) {
  const cache = await caches.open(CACHE_NAME);

  // Cache anahtarı: URL'deki ?v= parametresini dahil et
  // Böylece ?v=111 ile ?v=222 farklı cache girdisi = eski görsel korunmaz
  const cached = await cache.match(request);

  const fetchPromise = fetch(request).then(async response => {
    if (response && response.ok) {
      // Yeni versiyonu cache'e yaz
      await cache.put(request, response.clone());

      // Aynı pathname'in eski versiyonlarını temizle
      await purgeOldVersions(cache, request.url);
    }
    return response;
  }).catch(() => null);

  // Cache'te varsa hemen dön, arka planda güncelle
  if (cached) {
    fetchPromise; // arka planda çalışsın
    return cached;
  }

  // Cache'te yoksa ağdan bekle
  const fresh = await fetchPromise;
  if (fresh) return fresh;

  // İkisi de yoksa boş 404
  return new Response("Not found", { status: 404 });
}

// Aynı dosyanın eski ?v= versiyonlarını cache'den sil
async function purgeOldVersions(cache, currentUrl) {
  const currentU = new URL(currentUrl);
  const currentPath = currentU.pathname;

  const keys = await cache.keys();
  const toDelete = keys.filter(req => {
    const u = new URL(req.url);
    return u.pathname === currentPath && req.url !== currentUrl;
  });

  await Promise.all(toDelete.map(req => cache.delete(req)));
}
