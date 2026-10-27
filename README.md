# Ticket System — Mobile Edition

Laravel 12 IT help-desk ticket system with Phase 1 mobile access:

- **PWA** — installable on Android and iOS from hospital Wi-Fi, mobile-first screens, camera upload with automatic photo compression, offline page.
- **Web push** — ticket notifications on the phone (Android, iOS 16.4+ installed app, desktop browsers).
- **Telegram bot** — open, follow, reply to and confirm tickets from anywhere; admins can take and finish tickets. Uses long polling, so the server stays internal.

Docs:
- **[PWA documentation with screenshots (Arabic)](docs/pwa/README.md)**
- [Phase 1 design](Documantation/phase1-mobile-design.md)
- [Deployment & testing guide](Documantation/mobile-deployment.md)

## Requirements

PHP 8.2+ (extensions: openssl, gmp, mbstring, curl, fileinfo, ldap), Composer, Node.js 22 LTS, MySQL.

## Quick start (local)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan webpush:vapid
npm ci && npm run build
php artisan serve
```

Run the tests with `php artisan test`. Demo data for local testing: `php artisan db:seed --class=DemoSeeder`.

Telegram bot (needs `TELEGRAM_BOT_TOKEN`):

```bash
php artisan telegram:poll
```
