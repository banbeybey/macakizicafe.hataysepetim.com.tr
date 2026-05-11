// svimages.js — Service Worker kaydı
// Her sayfanın </body> öncesine ekle:
// <script src="/svimages.js"></script>

(function () {
  if (!("serviceWorker" in navigator)) return;

  navigator.serviceWorker.register("/sw-images.js", { scope: "/" })
    .then(reg => {
      // Güncelleme varsa hemen aktif et
      reg.addEventListener("updatefound", () => {
        const newWorker = reg.installing;
        if (newWorker) {
          newWorker.addEventListener("statechange", () => {
            if (newWorker.state === "activated") {
              // Sayfadaki tüm görselleri yenile (src'yi tekrar ata)
              document.querySelectorAll("img[src*='/uploads/']").forEach(img => {
                const src = img.src;
                img.src = "";
                img.src = src;
              });
            }
          });
        }
      });
    })
    .catch(() => {}); // Sessizce geç, kritik değil
})();
