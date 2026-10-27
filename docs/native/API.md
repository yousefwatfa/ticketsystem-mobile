# Mobile API v1

Base URL: `{server}/api/v1` (test instance: `http://172.16.10.105:9091/api/v1`).
Auth: `Authorization: Bearer <token>` from `/auth/login` (Laravel Sanctum). Send `Accept: application/json`.
Errors are always JSON: `401` not logged in · `403` not allowed · `404` not found · `409` business rule · `422` validation (`{message, errors}`) · `429` too many requests.

## Auth

### `POST /auth/login` (10 req/min)
```json
{ "username": "a.ahmad", "password": "…", "device_name": "Samsung A54" }
```
Same rules as the web login: local accounts by password hash, everyone else against Active Directory (LDAP; creates/refreshes the user and syncs hospitals).
```json
{ "token": "1|abc…", "user": { "id": 7, "username": "a.ahmad", "fullname": "…", "role": "Member", "is_admin": false, "is_root": false, "office_location": "…", "telegram_linked": false } }
```
`422` with `errors.username` on wrong credentials.

### `POST /auth/logout`
Revokes the current token and removes the push token registered with it.

### `GET /me`
`{ "data": <user> }`

## Staff

### `GET /categories`
`{ "data": [ { "id": 1, "name": "طابعة" } ] }`

### `GET /tickets?status=all|open|in_progress|pending|finished|closed&page=1`
The user's own tickets, 20 per page, newest first.
```json
{
  "data": [ <ticket> ],
  "counts": { "all": 5, "open": 1, "in_progress": 2, "pending": 1, "finished": 0, "closed": 1 },
  "links": { … }, "meta": { "current_page": 1, "last_page": 1, … }
}
```

### `POST /tickets` (multipart)
`title`, `description`, `office_location`, `category_id`, `attachments[]` (≤ 5 files, jpg/png/pdf/docx/xlsx/xls, ≤ 2 MB each — compress photos in the app).
`201 { "data": <ticket with messages/attachments> }` · `409 { "code": "finished_ticket_pending" }` when a finished ticket waits for confirmation.

### `GET /tickets/{id}`
Ticket with `messages` (oldest first) and `attachments`. `403` if the user may not view it.

### `POST /tickets/{id}/messages`
`{ "content": "…" }` → `201 { "data": <message> }`. The first admin reply moves the ticket to in progress.

### `POST /tickets/{id}/attachments` (multipart)
`file` → `201 { "data": <attachment> }`

### `POST /tickets/{id}/confirm` · `POST /tickets/{id}/not-fixed`
Creator only, ticket must be `finished`. → `{ "data": <ticket> }`

## Admin (role Admin)

### `GET /admin/tickets?status=&search=&mine=1&page=`
Tickets the admin can act on: in one of their departments or assigned to them, plus the hospital rule; root sees all. `search` matches id, title, location, creator name. Same shape as `/tickets` incl. `counts`.

### `POST /admin/tickets/{id}/take` · `POST /admin/tickets/{id}/finish`
→ `{ "data": <ticket> }` · `403` outside the admin's scope · `422` if already finished/closed.

## Notifications & push

### `GET /notifications`
`{ "unread": 3, "data": [ { "id": "uuid", "message": "…", "ticket_id": 6, "read": false, "created_at": "…" } ] }` (latest 50)

### `POST /notifications/read`
Marks all as read.

### `POST /devices`
`{ "token": "ExponentPushToken[…]", "platform": "android|ios", "device_name": "…" }` → `201`. Every `RealTimeNotification` is then also sent to this phone through the Expo Push Service with `data.ticket_id` (open that ticket on tap). Dead tokens are removed automatically.

### `DELETE /devices`
`{ "token": "ExponentPushToken[…]" }`

## Objects

**ticket**
```json
{
  "id": 6, "title": "…", "description": "…",
  "status": "in_progress", "status_label": "قيد العمل",
  "office_location": "…",
  "category": { "id": 1, "name": "طابعة" },
  "creator": { "id": 7, "fullname": "…" },
  "assignees": [ { "id": 2, "fullname": "…" } ],
  "messages": [ <message> ], "attachments": [ <attachment> ], "messages_count": 2,
  "created_at": "2026-10-27T18:22:00+03:00", "updated_at": "…",
  "can": { "reply": true, "attach": true, "confirm": false, "not_fixed": false, "take": false, "finish": false }
}
```
Use `can` to show or hide buttons — the server already applied the policy.

**message** `{ "id", "content", "author": { "id", "fullname", "is_admin" }, "is_mine", "created_at" }`

**attachment** `{ "id", "name", "type", "is_image", "url", "created_at" }`

## Server settings

- `EXPO_ACCESS_TOKEN` (optional) — only if "enhanced push security" is enabled in the Expo project.
- Outbound HTTPS to `exp.host` (verified reachable from 172.16.10.105).
- Tests: `tests/Feature/Api/ApiTest.php`.
