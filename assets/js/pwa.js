(function () {
  const BASE = (window.PORTAL_BASE_PREFIX || "").replace(/\\/g, "/");
  const SW_URL = `${BASE}sw.js`;
  let deferredPrompt = null;

  async function registerServiceWorker() {
    if (!("serviceWorker" in navigator)) return;
    try {
      await navigator.serviceWorker.register(SW_URL, { scope: `${BASE}` || "/" });
    } catch (e) {
      console.error("SW registration failed:", e);
    }
  }

  function bindInstallPrompt() {
    window.addEventListener("beforeinstallprompt", (e) => {
      e.preventDefault();
      deferredPrompt = e;
      const btn = document.getElementById("installAppBtn");
      if (btn) btn.classList.remove("hidden");
    });

    const btn = document.getElementById("installAppBtn");
    if (!btn) return;
    btn.addEventListener("click", async () => {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      await deferredPrompt.userChoice;
      deferredPrompt = null;
      btn.classList.add("hidden");
    });
  }

  function toBase64UrlToUint8Array(base64String) {
    const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
    const rawData = atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
    return outputArray;
  }

  async function registerPushSubscription() {
    if (!("Notification" in window) || !("serviceWorker" in navigator) || !("PushManager" in window)) return;
    if (Notification.permission === "denied") return;

    const permission = Notification.permission === "granted" ? "granted" : await Notification.requestPermission();
    if (permission !== "granted") return;

    try {
      const reg = await navigator.serviceWorker.ready;
      const existing = await reg.pushManager.getSubscription();
      if (existing) {
        await saveSubscription(existing);
        return;
      }

      const keyRes = await fetch(`${BASE}api/push_public_key.php`);
      const keyPayload = await keyRes.json();
      if (!keyPayload || !keyPayload.success || !keyPayload.public_key) return;

      const subscription = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: toBase64UrlToUint8Array(keyPayload.public_key)
      });
      await saveSubscription(subscription);
    } catch (e) {
      console.warn("Push subscription skipped:", e.message || e);
    }
  }

  async function saveSubscription(subscription) {
    const token = localStorage.getItem("jwt_token");
    if (!token) return;
    try {
      await fetch(`${BASE}api/save_push_subscription.php`, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json"
        },
        body: JSON.stringify(subscription.toJSON())
      });
    } catch (e) {
      console.warn("Unable to save push subscription:", e);
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    registerServiceWorker();
    bindInstallPrompt();
    registerPushSubscription();
  });
})();

