# Phase 1 Mobile Access — Deployment & Testing Guide

Covers the PWA (installable app on hospital Wi-Fi), web push notifications and the Telegram bot.
Design rationale: [phase1-mobile-design.md](phase1-mobile-design.md).

## 0. Before anything: security

1. **Revoke the old Telegram bot token.** It was committed in `tools/send_messages.ps1` (removed in this repo, but it is still in the history of the old public repository). In Telegram: `@BotFather` → `/revoke` → choose the bot → put the **new** token in `.env` only.
2. Make the old `yousefwatfa/ticketsystem` repository private (or delete it once this repo replaces it).
3. Removed in this version: `/debug/hospitals` (unauthenticated user listing), root `debug*.php` scripts, `tools/` scripts with hard-coded token and chat IDs.

## 1. What changed

| Area | Files |
|---|---|
| PWA shell | `public/manifest.json`, `public/sw.js`, `public/offline.html`, `public/icons/*`, `resources/views/layouts/_pwa_head.blade.php` |
| Mobile UI | `resources/css/mobile.css`, `layouts/_bottom_nav.blade.php`, `dashboard.blade.php` (card list + status chips), `tickets/create.blade.php` (camera + compression), `tickets/show.blade.php` (sticky reply), `partials/_install_banner.blade.php`, `pwa/install.blade.php` (`/install`) |
| Image compression | `resources/js/attachments.js` — photos resized to 1600px JPEG so they fit the 2 MB limit |
| Web push | `laravel-notification-channels/webpush`, `config/webpush.php`, `PushSubscriptionController`, `resources/js/push.js`, migration `create_push_subscriptions_table`, `RealTimeNotification::toWebPush()` |
| Shared logic | `app/Services/TicketService.php` — used by `TicketController`, `MessageController`, `AdminTicketController::markAsFinishedTicket` and the bot |
| Telegram bot | `app/Services/Telegram/TelegramApi.php`, `TelegramBotService.php`, `app/Console/Commands/TelegramPoll.php` (`php artisan telegram:poll`), buttons in `RealTimeNotification::toTelegram()` |
| Fixes | migration adding `telegram_link_token` / `telegram_linked_at` (profile "link Telegram" crashed without them); Viewer menu used a non-existent route; Echo no longer breaks all page scripts when Reverb is not configured |

## 2. Server setup (Windows Server)

### 2.1 Code

```powershell
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

PHP extensions needed (in `php.ini`): `openssl`, `gmp` (or `bcmath`), `mbstring`, `curl`, `fileinfo`, `ldap`.

### 2.2 `.env`

```dotenv
APP_URL=https://tickets.<hospital-domain>
SESSION_SECURE_COOKIE=true

TELEGRAM_BOT_TOKEN=<new token from BotFather>
TELEGRAM_BOT_USERNAME=<bot username without @>
# TELEGRAM_PROXY=http://proxy:8080   # only if outbound traffic goes through a proxy

VAPID_SUBJECT=mailto:it@<hospital-domain>
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
# WEBPUSH_PROXY=http://proxy:8080
```

Generate VAPID keys **once** (changing them later breaks every existing subscription):

```powershell
# Windows PHP needs an OpenSSL config to create EC keys
$env:OPENSSL_CONF = "C:\php\extras\ssl\openssl.cnf"
php artisan webpush:vapid
```

### 2.3 HTTPS (required — a PWA and push do not work over plain HTTP)

1. Internal DNS: `A  tickets.<domain>  →  <server internal IP>`.
2. Install **win-acme** (`wacs.exe`) and request a certificate with the **DNS-01** validation plugin for your public DNS provider. It creates a Scheduled Task that renews automatically.
   - IIS: win-acme binds the certificate to the site.
   - Apache (XAMPP): choose PEM output to a fixed folder, point `SSLCertificateFile` / `SSLCertificateKeyFile` to it, and add an Apache restart to the renewal script.
3. If the DNS provider has no API, delegate `_acme-challenge.tickets.<domain>` to one that does (e.g. acme-dns).

### 2.4 Background services (NSSM)

```powershell
nssm install TicketsQueue    "C:\php\php.exe" "C:\inetpub\ticketsystem\artisan queue:work --tries=3"
nssm install TicketsReverb   "C:\php\php.exe" "C:\inetpub\ticketsystem\artisan reverb:start"
nssm install TicketsTelegram "C:\php\php.exe" "C:\inetpub\ticketsystem\artisan telegram:poll"
nssm set TicketsTelegram AppDirectory "C:\inetpub\ticketsystem"
nssm start TicketsTelegram
```

Set `AppDirectory` for each service. Scheduler: Task Scheduler → every minute → `php artisan schedule:run`.

`telegram:poll` calls `deleteWebhook` on start, then long-polls `getUpdates`. Only **one** poller may run per bot token.

### 2.5 Outbound firewall (port 443)

`api.telegram.org`, `fcm.googleapis.com`, `*.push.apple.com`, `updates.push.services.mozilla.com`, `*.notify.windows.com`, `acme-v02.api.letsencrypt.org`, your DNS provider's API.

### 2.6 IIS only

- Make sure `.json` is served as `application/json` (manifest) and `.js` as `application/javascript` (service worker).
- `sw.js` must be served from the site root (`/sw.js`) — do not move it.
- WebSockets for Reverb: enable the *WebSocket Protocol* Windows feature + URL Rewrite/ARR proxy to the Reverb port.

## 3. Test checklist

Automated (local, SQLite): `php artisan test` — 28 tests covering TicketService, the HTTP flows, push subscription routes, the manifest/install page and the Telegram bot flows (with the Telegram API faked).

Local demo data: `php artisan db:seed --class=DemoSeeder` (credentials are in the seeder; never run in production).

### PWA (phone on hospital Wi-Fi)

- [ ] `https://tickets.<domain>` opens with a valid certificate (no warning)
- [ ] Android Chrome: install banner appears → app installs → opens full screen from home screen
- [ ] iPhone Safari: Share → Add to Home Screen → opens full screen
- [ ] Bottom nav: Home / (Tickets for admins) / New ticket / Account — all reachable with the thumb
- [ ] New ticket: "التقاط صورة" opens the camera; a 3-5 MB photo shows a preview under 2 MB; ticket is created with the photo
- [ ] Dashboard on phone: card list + status chips filter correctly
- [ ] Ticket page: reply box stays above the bottom nav
- [ ] Turn Wi-Fi off → "لست متصلاً بشبكة المستشفى" page appears
- [ ] Desktop: dashboard table, top menu and footer unchanged

### Push

- [ ] Profile → "فعّل الإشعارات" → allow → status shows enabled
- [ ] From another account reply to the user's ticket → notification arrives with the screen locked → tap opens the ticket
- [ ] iPhone: works only from the installed app (iOS 16.4+)
- [ ] "إيقاف الإشعارات" stops them

### Telegram bot

- [ ] Profile → "ربط الحساب" → "افتح البوت لإكمال الربط" → Start → bot replies with the menu; profile shows "الحساب مربوط"
- [ ] `/new` → category → location → title → description → photo → send → ticket appears on the web with the photo
- [ ] `/my` lists open tickets; tapping one shows details and buttons
- [ ] Swipe-reply to a notification containing `#id` → reply appears on the ticket
- [ ] Admin: "استلام" assigns + moves to in progress; "تم الحل" marks finished
- [ ] Member gets "تأكيد الحل" / "المشكلة مستمرة" buttons on the finished notification; each works once
- [ ] A user who is not linked only receives the "link your account" message
- [ ] Department group chats still receive notifications but buttons there do nothing

## 4. Known limitations

- The PWA works only on hospital Wi-Fi (by design — nothing is exposed to the internet). The Telegram bot works anywhere.
- Push and Telegram notifications are sent synchronously inside the request, same as before. With many admins per department consider making `RealTimeNotification` implement `ShouldQueue`.
- Dates still show in English ("1 minute ago") because `APP_LOCALE` is `en`; set `APP_LOCALE=ar` to localise them.
- `composer audit` reports advisories in existing dependencies; review and update separately.
