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

## 2. Server setup (Ubuntu 24.04 · Apache · PHP 8.4 — server 172.16.10.105)

Current layout on the server:

| Instance | Path | Port | Database |
|---|---|---|---|
| Production | `/var/www/html/ticket-system` | 80 | production DB |
| Stage | `/var/www/html/ticket-system-stage` | 9090 | `ticket_system_stage` |
| Mobile test | `/var/www/html/ticketsystem-mobile` | 9091 | `ticket_system_mobile` (copy of stage) |

The mobile folder pulls from GitHub with a **read-only deploy key** (`~/.ssh/ticketsystem_mobile_deploy`, set as `core.sshCommand` in the repo).

### 2.1 Code (as `ost`, no sudo)

```bash
cd /var/www/html/ticketsystem-mobile
git pull --ff-only
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear      # use config:cache/route:cache/view:cache in production
```

PHP extensions needed: `openssl`, `bcmath` or `gmp`, `mbstring`, `curl`, `ldap` (all present on the server).
`storage/` and `bootstrap/cache` must be group-writable by `www-data`.

### 2.2 `.env`

```dotenv
APP_URL=https://tickets.<hospital-domain>
SESSION_SECURE_COOKIE=true
SESSION_COOKIE=ticketsystem_mobile_session   # different per instance on the same host

TELEGRAM_BOT_TOKEN=<new token from BotFather>
TELEGRAM_BOT_USERNAME=<bot username without @>
# TELEGRAM_PROXY=http://proxy:8080   # only if outbound traffic goes through a proxy

VAPID_SUBJECT=mailto:it@<hospital-domain>
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
# WEBPUSH_PROXY=http://proxy:8080
```

Generate VAPID keys **once** — changing them later breaks every existing subscription:

```bash
php artisan webpush:vapid
```

(On Windows PHP, set `OPENSSL_CONF` to PHP's `extras/ssl/openssl.cnf` first.)

The **test instance** additionally uses `BROADCAST_CONNECTION=log`, an empty `VITE_REVERB_APP_KEY`, `QUEUE_CONNECTION=sync` and an empty `TELEGRAM_BOT_TOKEN` until a separate test bot exists, so it never reaches production Reverb or real staff.

### 2.3 HTTPS (required — app install and push do not work over plain HTTP)

1. Internal DNS: `A  tickets.<domain>  →  172.16.10.105`.
2. `sudo apt install certbot python3-certbot-dns-<provider>` and request the certificate with **DNS-01** (the server is not reachable from the internet, so HTTP-01 cannot work). Certbot installs a renewal timer.
3. `sudo a2enmod ssl headers && sudo a2ensite` a `*:443` vhost pointing to `public/` with the certificate paths; allow 443 in `ufw`.
4. If the DNS provider has no API, delegate `_acme-challenge.tickets.<domain>` to one that does (e.g. acme-dns).

### 2.4 Background services (systemd)

Production already runs `laravel-reverb.service` and `laravel-worker.service`. For the bot add one unit per instance (only **one** poller may run per bot token):

```ini
# /etc/systemd/system/ticketsystem-mobile-telegram.service
[Unit]
Description=Ticket system mobile - Telegram bot polling
After=network-online.target mysql.service

[Service]
User=www-data
WorkingDirectory=/var/www/html/ticketsystem-mobile
ExecStart=/usr/bin/php artisan telegram:poll
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now ticketsystem-mobile-telegram
```

`telegram:poll` calls `deleteWebhook` on start, then long-polls `getUpdates`.

### 2.5 Firewall

- Inbound (`ufw`): 80 (production), 9090 (stage), 9091 (mobile test), 443 once HTTPS exists.
- Outbound 443 (already open on the server): `api.telegram.org`, `fcm.googleapis.com`, `*.push.apple.com`, `updates.push.services.mozilla.com`, `acme-v02.api.letsencrypt.org`, the DNS provider API, `github.com`.

### 2.6 Apache notes

- `sw.js` must be served from the site root (`/sw.js`) — do not move it.
- Reverb behind HTTPS: `a2enmod proxy proxy_http proxy_wstunnel` and proxy `/app` to `127.0.0.1:8080`.

## 3. Test checklist

Automated (local, SQLite): `php artisan test` — 29 tests covering TicketService, the HTTP flows, push subscription routes, the manifest/install page and the Telegram bot flows (with the Telegram API faked).

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
