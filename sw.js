self.addEventListener('push', function(event) {
  const data = event.data ? event.data.json() : {};
  const title = data.title || '🪔 Yeni Nargile Siparişi!';
  const options = {
    body: data.body || 'Yeni sipariş geldi!',
    icon: '/uploads/masalar/normalmasa.png',
    badge: '/uploads/masalar/normalmasa.png',
    vibrate: [300, 100, 300, 100, 300],
    requireInteraction: true,
    tag: 'nargile-' + Date.now()
  };
  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  event.waitUntil(clients.openWindow('/nargileci.php'));
});
