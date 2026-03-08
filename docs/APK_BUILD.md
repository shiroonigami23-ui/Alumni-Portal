# Build Android APK From PWA

## Prerequisites
- Node.js 18+
- Java 17+
- Android Studio + SDK
- Public HTTPS deployment URL of this app

## 1) Ensure PWA is live
- Confirm these are reachable on production:
  - `/alumni_portal/manifest.webmanifest`
  - `/alumni_portal/sw.js`
  - `/alumni_portal/assets/icons/app-icon-512.png`

## 2) Generate Android project with Bubblewrap (TWA)
```bash
npm i -g @bubblewrap/cli
bubblewrap init --manifest https://YOUR_DOMAIN/alumni_portal/manifest.webmanifest
```

Use package name like:
- `in.rjit.alumniportal`

## 3) Build APK
```bash
bubblewrap build
```

Generated file:
- `app-release-signed.apk` (if signing configured)

## 4) Install/test locally
```bash
adb install -r app-release-signed.apk
```

## 5) Notification note
- Web push requires VAPID keys.
- Set env var on server:
  - `VAPID_PUBLIC_KEY=...`
- Backend already exposes `api/push_public_key.php` and stores subscriptions through `api/save_push_subscription.php`.

