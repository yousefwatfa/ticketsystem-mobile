# Phase 2 — Native App (React Native + Expo) + API

**Decisions (2026-10-27):** React Native + Expo (TypeScript) · v1 = staff + admin essentials · works only on the hospital network (the server is internal) · push via Expo Push Service (relays to FCM/APNs; server already reaches `exp.host`).

## Architecture

```
Expo app (Android / iOS)
  └─ HTTPS/HTTP → Laravel /api/v1 (Sanctum bearer token)
                     ├─ LdapAuthenticator (shared with web login)
                     ├─ TicketService     (shared with web + Telegram bot)
                     └─ RealTimeNotification → database · broadcast · web push · Expo push · Telegram
```

Outside the hospital network the API is unreachable, so the app shows a "connect to hospital Wi-Fi" screen. No SSID checks.

## API v1 (`routes/api.php`, prefix `/api/v1`, `auth:sanctum` except login)

| Method | Path | Purpose |
|---|---|---|
| POST | `/auth/login` | username + password (+ device_name) → token + user. LDAP or local, same rules as web. Throttled |
| POST | `/auth/logout` | revoke current token (+ remove its device push token) |
| GET | `/me` | current user, role, abilities |
| GET | `/categories` | problem types for the create form |
| GET | `/tickets?status=` | my tickets (paginated) + counts per status |
| POST | `/tickets` | create (multipart, `attachments[]`) — blocked while a finished ticket awaits confirmation |
| GET | `/tickets/{id}` | details, messages, attachments, assignees, allowed actions |
| POST | `/tickets/{id}/messages` | reply |
| POST | `/tickets/{id}/attachments` | add a photo/file |
| POST | `/tickets/{id}/confirm` | creator confirms fix (finished → closed) |
| POST | `/tickets/{id}/not-fixed` | creator reports problem persists (finished → in_progress) |
| GET | `/admin/tickets?status=&search=` | department tickets (admins), same scoping as the web |
| POST | `/admin/tickets/{id}/take` | assign self + in progress |
| POST | `/admin/tickets/{id}/finish` | mark finished |
| GET | `/notifications` | latest database notifications |
| POST | `/notifications/read` | mark all read |
| POST | `/devices` | register Expo push token for this device |
| DELETE | `/devices` | unregister |

Responses use API Resources; status labels in Arabic; every ticket includes `can` (reply / confirm / take / finish) so the app never re-implements policy rules.

## App v1 screens

Staff: Login → My tickets (status chips) → Ticket (conversation, reply, photo, confirm/not-fixed) → New ticket (camera + compression) → Notifications → Account.
Admin: + Department tickets (filters/search) → Ticket actions (take, reply, finish).
Global: RTL Arabic, offline/"not on hospital network" screen, configurable server URL (settings), secure token storage (`expo-secure-store`), push registration.

## Milestones

1. **API** — Sanctum, LDAP service extraction, endpoints, Expo push channel, tests. ✅ done
2. **App skeleton** — Expo + TypeScript + expo-router, RTL, auth flow, API client, network-state screen. ✅ done
3. **Staff screens** — tickets, details, create with camera, notifications screen + push ✅ (push needs `eas init` + a build to try on a phone)
4. **Admin screens** — department tickets, actions. ✅ done ([screenshots](APP.md))
5. **Builds** — Android APK (EAS or local), iOS via EAS + TestFlight (needs Apple Developer account).
6. **Pilot** on the 9091 test instance, then production.

## Prerequisites / risks

- **HTTPS strongly recommended**: login sends the AD password; Android/iOS block plain HTTP unless explicitly allowed (allowed only for the test build).
- **iOS distribution** needs an Apple Developer account ($99/yr); EAS builds iOS in the cloud, no Mac needed.
- **Expo account** for EAS builds and push credentials.
