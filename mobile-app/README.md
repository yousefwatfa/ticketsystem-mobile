# الدعم الفني — Native App (React Native + Expo)

Arabic (RTL) mobile app for the ticket system. Talks to the Laravel API in `../routes/api.php` ([API reference](../docs/native/API.md)). Works only on the hospital network, because the server is internal.

**Stack:** Expo SDK 58 · React Native 0.88 · TypeScript · expo-router (file-based routes in `src/app`) · expo-secure-store · expo-image-picker + expo-image-manipulator.

## Run it

```bash
cd mobile-app
npm install
npx expo start          # scan the QR code with Expo Go (phone on hospital Wi-Fi)
npx expo start --web    # quick check in a browser
```

On the login screen open **إعدادات الخادم** to set the server URL (default: the test instance `http://172.16.10.105:9091`). For local development point it at your `php artisan serve` URL; seed demo users with `php artisan db:seed --class=DemoSeeder`.

Checks before committing:

```bash
npx tsc --noEmit
npx eslint src
```

## Structure

```
src/
  app/
    _layout.tsx          root: AuthProvider, forced RTL, login/app guard (Stack.Protected)
    login.tsx            login + server URL setting + offline message
    (tabs)/_layout.tsx   bottom tabs (admin tab only for admins)
    (tabs)/index.tsx     my tickets: status chips, pull to refresh, infinite scroll
    (tabs)/admin.tsx     department tickets: search + status chips
    (tabs)/new.tsx       new ticket: category sheet, camera/gallery, compression, validation
    (tabs)/account.tsx   profile, server, sign out
    ticket/[id].tsx      details, attachments, conversation, reply (+ photo), confirm / not fixed, take / finish
  components/            ui.tsx (Button, Field, Card, Banner, StatusBadge...), tickets.tsx (TicketCard, StatusChips)
  lib/
    api.ts               fetch wrapper: bearer token, timeout, Arabic errors, offline detection, 401 → sign out
    auth.tsx             session context (token in SecureStore)
    images.ts            pick + resize to 1600px JPEG (fits the 2 MB server limit)
    useTicketList.ts     paginated list hook
    theme.ts             colours (≥ 4.5:1 contrast), spacing, status styles, Arabic relative time
```

Buttons for actions come from the ticket's `can` object returned by the API, so permissions live only on the server.

## Builds

Uses EAS (cloud builds, no Mac needed). The project is linked (`extra.eas.projectId`), and the root `.easignore` uploads only this folder.

```bash
npx eas-cli@latest login
npx eas-cli@latest build -p android --profile preview   # installable APK
npx eas-cli@latest build -p ios                         # needs an Apple Developer account
```

- Android allows plain HTTP to the internal server (`usesCleartextTraffic` in `app.json`) for the test phase. iOS blocks plain HTTP — enable HTTPS on the server before the iPhone pilot (it is also needed to protect the AD password on Wi-Fi).
## Push notifications

Implemented in `src/lib/push.ts`, `src/lib/useNotificationTaps.ts`, `src/lib/unread.ts`, `src/app/notifications.tsx`, `src/components/notification-bell.tsx`:

- After sign-in / session restore: create the Android channel `tickets`, ask permission, get the Expo push token, `POST /api/v1/devices`. Sign-out calls `DELETE /api/v1/devices` first.
- Tapping a notification opens `/ticket/{data.ticket_id}`, including when the tap launched the app.
- Bell with unread badge in every tab header; the notifications screen marks all as read.
- Account screen shows the push state (enabled / denied / unavailable + reason) with a retry button.

Android push goes through Firebase Cloud Messaging:

- `google-services.json` (Firebase project `ticketsystem-15fed`) is committed and set as `android.googleServicesFile` in `app.json`.
- The FCM V1 service account key is uploaded to EAS (`eas credentials` → Android → Google Service Account, or expo.dev → Credentials). Keep the key file (`fcm-service-account.json`) out of git; it is ignored.

Remote push does **not** work in Expo Go on Android (SDK 53+) — the app detects this and explains it in the Account screen. Web has no push; the bell and list still work.

Local dev on Windows: PHP has no CA bundle, so `ExpoPushChannel` cannot reach `exp.host` over HTTPS (`cURL error 60`). Set `curl.cainfo` in `php.ini` if you need it locally; the Ubuntu server is fine (verified).
