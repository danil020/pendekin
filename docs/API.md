# Pendekin API

Base URL examples use `http://localhost:8000`. Requests with a JSON body should send
`Accept: application/json` and `Content-Type: application/json`.

## Endpoints

| Method | Path | Auth | Success |
| --- | --- | --- | --- |
| `GET` | `/health` | No | `200` when the database is connected; Redis status is reported separately. |
| `POST` | `/api/register` | No | `201` and a user resource with a Sanctum token. |
| `POST` | `/api/login` | No | `200` and a user resource with a Sanctum token. |
| `POST` | `/api/logout` | Bearer token | `200`; revokes the current token. |
| `POST` | `/api/links` | Bearer token | `201` and the created link. |
| `GET` | `/api/links` | Bearer token | `200` and a paginated list of the current user's links. |
| `GET` | `/api/links/{link}/stats` | Bearer token | `200` and click statistics for a link owned by the current user. |
| `PATCH` | `/api/links/{link}/deactivate` | Bearer token | `200` and the deactivated link. |
| `GET` | `/{code}` | No | `302` redirect, `404` if unknown, or `410` if inactive/expired. |

Protected endpoints require `Authorization: Bearer <token>`. Obtain a token by
registering or logging in.

## Authentication

### Register

`POST /api/register`

```json
{
  "name": "Ayu",
  "email": "ayu@example.com",
  "password": "<password>",
  "password_confirmation": "<password>"
}
```

Example response (`201`):

```json
{
  "data": {
    "id": 1,
    "name": "Ayu",
    "email": "ayu@example.com"
  },
  "token": "<sanctum-token>"
}
```

### Login

`POST /api/login`

```json
{
  "email": "ayu@example.com",
  "password": "<password>"
}
```

Response: same shape as register, with status `200`.

### Logout

`POST /api/logout`

Requires a bearer token. Response (`200`):

```json
{
  "message": "Berhasil logout."
}
```

## Links

### Create a link

`POST /api/links`

```json
{
  "original_url": "https://example.com/article",
  "alias": "artikel-1",
  "expires_at": "2027-01-01T00:00:00+07:00"
}
```

`original_url` is required and must be an HTTP(S) URL. `alias` and `expires_at`
are optional. Aliases must be unique, 3–30 characters, and contain only letters,
numbers, or hyphens; reserved route names are rejected. Without an alias, the API
generates a unique six-character code. `expires_at`, when provided, must be in
the future.

Response (`201`):

```json
{
  "data": {
    "id": 1,
    "code": "artikel-1",
    "short_url": "http://localhost:8000/artikel-1",
    "original_url": "https://example.com/article",
    "is_active": true,
    "expires_at": "2027-01-01T00:00:00.000000Z",
    "created_at": "2026-11-17T00:00:00.000000Z"
  }
}
```

### List links

`GET /api/links`

Returns only links owned by the authenticated user. Response (`200`) is a
paginated Laravel resource collection:

```json
{
  "data": [
    {
      "id": 1,
      "code": "artikel-1",
      "short_url": "http://localhost:8000/artikel-1",
      "original_url": "https://example.com/article",
      "is_active": true,
      "expires_at": null,
      "created_at": "2026-11-17T00:00:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/links?page=1",
    "last": "http://localhost:8000/api/links?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://localhost:8000/api/links",
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

### Deactivate a link

`PATCH /api/links/{link}/deactivate`

Only the owner can deactivate a link. Returns the updated link resource (`200`);
returns `403` for another user's link and `404` if the link does not exist.

### Get click statistics

`GET /api/links/{link}/stats`

Optional inclusive date filters use `YYYY-MM-DD`, for example
`/api/links/1/stats?from=2026-11-01&to=2026-11-17`. Only the owner can view stats.

Response (`200`):

```json
{
  "data": {
    "total_clicks": 12,
    "daily_stats": [
      {
        "date": "2026-11-17",
        "clicks": 12
      }
    ]
  }
}
```

## Redirect and health

`GET /{code}` returns `302` with a `Location` header for active, unexpired links.
Unknown codes return `404`; inactive or expired links return `410`.

`GET /health` returns:

```json
{
  "status": "ok",
  "database": "connected",
  "cache": "connected"
}
```

Database failure returns `503`. Redis/cache failure is reported as
`"cache": "disconnected"` and does not by itself make health return `503`.

## Status codes

| Status | Meaning |
| --- | --- |
| `200` | Request succeeded. |
| `201` | Link or account created. |
| `302` | Short link redirect; destination is in `Location`. |
| `401` | Missing or invalid authentication token. |
| `403` | Authenticated user does not own the link. |
| `404` | Link or route not found. |
| `410` | Link is inactive or expired. |
| `422` | Validation failed. |
| `503` | Database health check failed. |

## Jenkins smoke tests

Run migrations and seed the demo link before starting the application:

```sh
php artisan migrate --force
php artisan db:seed --force
```

After the application is listening on port `8000`, run:

```sh
curl -fsS http://localhost:8000/health
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/demo01
```

The second command should print `302`. The seeder creates `demo01` as an active
link without an expiration time.

## Automated test coverage

| Test file | Cases and rules covered |
| --- | --- |
| `tests/Feature/AuthTest.php` | Registration, login, token-protected logout, and token revocation. |
| `tests/Feature/LinkTest.php` | Duplicate/illegal/reserved aliases; HTTP(S)-only URLs; unique six-character codes; owner-only list, deactivation, and stats; redirect `302`, unknown `404`, inactive/expired `410`; atomic daily clicks; date filters; cache invalidation and cache hits. |
| `tests/Feature/HealthTest.php` | Database-connected `200`, database-disconnected `503`, and cache failure does not fail health. |
| `tests/Feature/DatabaseSeederTest.php` | Repeated seeding stays consistent and creates an active, unexpired `demo01` with sample stats. |
