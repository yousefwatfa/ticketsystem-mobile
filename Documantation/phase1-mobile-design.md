# Ticket System — Phase 1 Mobile Access Design

**Status:** Draft (brainstorming output) · **Date:** 2026-10-27
**Repo:** github.com/yousefwatfa/ticketsystem (Laravel 12, Blade + Metronic RTL, jQuery/DataTables, Reverb, Telegram, LDAP)

## 1. Goal

Let hospital staff create and follow tickets from their phones, and let admins handle them, **without exposing the internal server to the internet** and without VPN/MDM.

- **PWA** — full system on phones while on hospital Wi-Fi, installable on Android and iOS, with push notifications.
- **Telegram bot** — lightweight access from anywhere (create, follow, reply, confirm), using outbound-only long polling.

Out of scope for Phase 1: native apps, public API, internet exposure (Cloudflare Tunnel), moving reports/admin screens to mobile.

## 2. Constraints

| Constraint | Consequence |
|---|---|
| Server is internal-only (Windows Server) | No inbound webhooks; everything external is outbound (Telegram polling, push services, ACME) |
| No VPN / MDM / M365 | No per-app VPN, no managed distribution → PWA + Telegram |
| Hospital owns a domain, DNS editable | Real TLS cert via Let's Encrypt DNS-01 |
| Login is LDAP (AD) + sessions | PWA reuses existing session login unchanged |
| Upload limit 2 MB | Phone photos must be compressed client-side |

## 3. Step 0 — Security cleanup (before anything else)

1. **Revoke the Telegram bot token** committed in `tools/send_messages.ps1` (@BotFather → `/revoke`); put the new one only in `.env`. Git history still contains the old one.
2. Make the repo private again (or purge history).
3. Remove the unauthenticated `/debug/hospitals` route in `routes/web.php` and the root `debug*.php` files.
4. Move the `tools/` scripts out of the repo or make them read the token from `.env`.

## 4. Part A — PWA

### 4.1 HTTPS on the internal server

```
Phone (hospital Wi-Fi) ──► internal DNS: tickets.<domain> A → <server internal IP>
                                │
                                ▼
                 Windows Server (IIS or Apache) with Let's Encrypt cert
                                │ win-acme (DNS-01) writes _acme-challenge TXT
                                ▼
                      Public DNS provider (API) ◄── Let's Encrypt validates
```

- Internal DNS: `A tickets.<domain> → <internal IP>` (only inside the network).
- **win-acme** (`wacs.exe`) with a DNS-01 plugin for the public DNS provider; it creates a Windows Scheduled Task for auto-renewal.
  - IIS: win-acme installs and binds the cert automatically.
  - Apache (XAMPP/Laragon): export PEM files to a fixed folder and reload Apache in the renewal script.
- If the DNS provider has no API: delegate `_acme-challenge.tickets.<domain>` (CNAME/NS) to a provider that does (e.g. acme-dns).
- `.env`: `APP_URL=https://tickets.<domain>`, `SESSION_SECURE_COOKIE=true`.

### 4.2 Installable app shell

- `public/manifest.webmanifest`: `name: "الدعم الفني والمهام"`, `lang: ar`, `dir: rtl`, `display: standalone`, `start_url: /dashboard`, theme color, icons 192/512 + maskable (from `assets/media/logos/logos.png`).
- `public/sw.js` (registered from `layouts/app.blade.php` and `layouts/auth.blade.php`):
  - Cache-first for static assets (`/assets/**`, Vite build, fonts).
  - **Network-only for HTML and data** — never cache ticket pages (patient/staff data).
  - Offline fallback page: "لست متصلاً بشبكة المستشفى".
- `<link rel="manifest">`, `apple-touch-icon`, `apple-mobile-web-app-capable` meta tags.
- `/install` help page with Android and iOS (Share → Add to Home Screen) instructions.
- Self-host Google Fonts / Bootstrap Icons currently loaded from CDNs, so the shell works if internet egress is restricted.

### 4.3 Push notifications

- Package: `laravel-notification-channels/webpush`; VAPID keys in `.env`; migration for `push_subscriptions`.
- `User` uses `HasPushSubscriptions`.
- Add `WebPushChannel::class` to `RealTimeNotification::via()` with a `toWebPush()` (title, body, `url: /tickets/{id}`). Every existing `safeNotify(...)` call then reaches phones automatically.
- Profile page: "فعّل الإشعارات" button → `Notification.requestPermission()` → `pushManager.subscribe()` → `POST /push-subscriptions`. (iOS: only works after install to Home Screen, iOS 16.4+.)
- `sw.js` handles `push` (show notification) and `notificationclick` (open ticket URL).
- PHP: enable `extension=gmp` (and `openssl`) in `php.ini`.
- Firewall: outbound 443 to `fcm.googleapis.com`, `*.push.apple.com`, `updates.push.services.mozilla.com`.

### 4.4 Mobile UX changes

| Screen | Change |
|---|---|
| Create ticket (`tickets/create`) | Camera button (`accept="image/*" capture="environment"`); client-side resize/compress to ≤ 1600 px, JPEG ~0.8, so files fit the 2 MB rule |
| My tickets / dashboard | Card list below `md` breakpoint instead of DataTables (data already in `$recentTickets`) |
| Ticket view (`tickets/show`) | Chat-style messages, sticky reply box at bottom, attachment thumbnails |
| Navigation | Bottom nav on small screens: Home · New ticket · My tickets · Profile |
| Admin ticket view | Quick actions (take / reply / finish) as large buttons on mobile |

**IP tagging:** phones on Wi-Fi get Wi-Fi subnet IPs → `IpTaggingService` would return `EXTERNAL`. Add the hospital Wi-Fi subnets to the ranges, and fall back to the user's linked hospital when the IP is unknown. `host_name` will be empty for phones (acceptable).

## 5. Part B — Telegram bot

### 5.1 Long polling instead of webhook

Current state: `TelegramController::handleWebhook` exists but has **no route**, and webhooks cannot reach an internal server, so `/start <token>` linking from the profile page likely never completes.

- New `App\Services\Telegram\TelegramBotService` — holds the update-handling logic (moved from `TelegramController`).
- New command `php artisan telegram:poll`: calls `deleteWebhook` once, then loops on `getUpdates` (timeout 30 s, tracks `offset`), dispatches each update to the service. Catches errors and backs off.
- Run as a Windows service via **NSSM** (auto-restart). Same for `queue:work` and `reverb:start` if not already.
- Linking: profile button generates `telegram_link_token` → deep link `https://t.me/<bot>?start=<token>` → poller handles `/start <token>` (existing logic).

### 5.2 Member flows

| Trigger | Behaviour |
|---|---|
| `/new` | Guided conversation: category (inline buttons) → office location → title → description → optional photo(s) → confirm. State in cache keyed by `chat_id`, 15-min TTL, `/cancel` to abort |
| `/my` | List of open tickets with inline buttons; tapping shows status, assignee, last 3 messages |
| Reply to a ticket notification | Text/photo is added as a `Message` / attachment on that ticket |
| Ticket marked finished | Message with buttons **تأكيد الحل** (→ close, same as `finishTicket`) / **المشكلة مستمرة** (→ reopen request) |

Existing rule kept: no new ticket while one is `finished` and awaiting confirmation.

### 5.3 Admin flows

New-ticket notifications to admins (private chat) carry inline buttons: **استلام** (assign self, `in_progress`), **رد**, **تم الحل** (`finished`). Department group chats keep receiving notifications only — **no actions from groups**.

### 5.4 Security

- Act only for chats whose `chat_id` matches a user's `telegram_number`; everything else gets the "link your account" reply.
- Every action authorized with `TicketPolicy` / `getUsersAllowedToView` — same rules as the web.
- Callback data carries ticket id + action only; re-check permissions on every callback.
- Per-chat rate limiting (`RateLimiter`).
- Bot `/help` text reminds users not to send patient-identifying information.

### 5.5 Shared domain logic

Extract from controllers into `App\Services\TicketService`: `create()`, `addMessage()`, `addAttachment()`, `assign()`, `markFinished()`, `confirmClose()`. Each writes the `TicketLog` and sends notifications (Reverb + web push + Telegram) once. Web controllers and the bot both call it, so rules never drift.

## 6. Windows Server runbook

| Process | How |
|---|---|
| Web | IIS (FastCGI PHP) **or** Apache service (XAMPP). Laragon is a dev tool — avoid for production |
| TLS | win-acme + Scheduled Task renewal |
| Reverb (`wss`) | NSSM service `php artisan reverb:start`; proxy `/app` + `/apps` — IIS: URL Rewrite + ARR with WebSocket Protocol feature; Apache: `mod_proxy_wstunnel` |
| Queue | NSSM service `php artisan queue:work --tries=3` |
| Telegram poller | NSSM service `php artisan telegram:poll` |
| Scheduler | Task Scheduler, every minute: `php artisan schedule:run` |
| Outbound firewall | `api.telegram.org`, push services, `acme-v02.api.letsencrypt.org`, DNS provider API |

## 7. Delivery order

0. Security cleanup (§3)
1. Domain + HTTPS + Reverb over `wss` (§4.1, §6)
2. PWA shell + mobile create-ticket with photo compression (§4.2, §4.4)
3. Web push (§4.3)
4. `TicketService` refactor (§5.5) + poller + working account linking (§5.1)
5. Bot member flows, then admin flows (§5.2–5.4)
6. Pilot with one department, then roll out

## 8. Open questions

- Web server in production: **IIS or Apache (XAMPP)?**
- Public DNS provider for the domain (API available?)
- Hospital Wi-Fi subnets (for `IpTaggingService`)
- Does the server have outbound internet to Telegram / push services / Let's Encrypt, or is it behind a proxy?
- Hospital policy on ticket content passing through Telegram
