# Repository Audit — rest-api-php

## Repository Metadata
| Item | Value |
|------|-------|
| Name | rest-api-php |
| Owner | RasimAghayev |
| Default branch | `master` (47 commits, no tags) |
| HEAD | `21f372e3c1ffd400646d47b87a3f21b2f2f27e72` |
| License | MIT (2021 Rasim Aghayev) |
| Working tree | Clean |

## File Inventory
```
├── api/
│   ├── .htaccess           # Front-controller + Authorization header pass-through
│   ├── bootstrap.php       # Dotenv, error handlers, JSON header
│   ├── index.php           # /api/tasks router + auth + TaskController
│   ├── login.php           # POST /login → access+refresh tokens
│   ├── logout.php          # POST /logout → delete refresh token
│   ├── refresh.php         # POST /refresh → rotate token pair
│   └── tokens.php          # Shared token generation (included by login/refresh)
├── http_request/           # Postman, curl, PhpStorm HTTP client fixtures
├── src/
│   ├── Auth.php            # API-key + JWT authentication
│   ├── Database.php        # PDO connection singleton
│   ├── ErrorHandler.php    # Error/exception → JSON
│   ├── InvalidSignatureException.php
│   ├── JWTCodec.php        # Hand-rolled HS256 JWT
│   ├── RefreshTokenGateway.php
│   ├── TaskController.php  # Task CRUD + validation
│   ├── TaskGateway.php     # User-scoped task queries
│   ├── TokenExpiredException.php
│   └── UserGateway.php     # User queries + registration
├── composer.json           # 2 prod deps + 1 dev dep, no PHP constraint
├── composer.lock           # Tracked (despite .gitignore comment)
├── composer.bat            # Hardcoded local OpenServer path
├── delete-expired-refresh-tokens.php  # Cron entry point
├── index.php               # HTML/JS demo client (Pico.css)
├── LICENSE                 # MIT
├── query.sql               # MySQL/MariaDB schema + seed + **hardcoded password**
├── register.php            # User registration (HTML + POST handler)
└── README.md               # Minimal, badges only
```

## Security Findings (Redacted)

### P0 — Compromised Credential in Working Tree & History
- **Location**: `query.sql:3`, `query.sql:12`, `query.sql:13`, `query.sql:14`
- **Finding**: MySQL/MariaDB `CREATE USER ... IDENTIFIED BY '<REDACTED>'` and `GRANT ... IDENTIFIED BY '<REDACTED>'` with the same password.
- **History**: Introduced in commit `9cf0c8e` (2021-12-25), present in all 47 commits.
- **Impact**: Per CLAUDE.md, this credential is **compromised** and must be rotated by the owner. Removing the lines is not a fix; history rewrite is **Approval Gate 1**.
- **Status**: Documented here; NOT fixed in this phase.

### P1 — Sample Signing Key in Source
- **Location**: `src/JWTCodec.php:21-26`
- **Finding**: Commented 64-hex-char string labeled "256-bit key" from a key-generator site.
- **Risk**: If this value ever equals the deployed `SECRET_KEY`, it is a live signing key.
- **Action**: Rotate `SECRET_KEY` and delete the comment.

### P1 — Missing PHP Version Constraint
- **Location**: `composer.json` (no `require.php`)
- **Finding**: Code uses PHP 8.0+ features (constructor promotion, typed properties, union types `array|false`). Composer cannot enforce the floor.

### P1 — Dead Dependency with Exact Pin
- **Location**: `composer.json:16` — `"firebase/php-jwt": "v7.0.0"`
- **Finding**: Never imported or used; auth is hand-rolled in `JWTCodec.php`. Exact pin blocks v7.2.1 (security/correctness fixes).

### P1 — Advisory Blocking Only in Dev
- **Location**: `composer.json:21` — `"roave/security-advisories": "dev-latest"` in `require-dev`
- **Finding**: `composer install --no-dev` (production) loses all vulnerability blocking.

### P2 — No Container / CI / Test Baseline
- **Missing**: Dockerfile, docker-compose.yml, Makefile, .github/workflows, phpunit.xml, tests/, PHPStan/Psalm, .env.example, .dockerignore, SECURITY.md, CONTRIBUTING.md.

### P2 — Committed Developer Artifact
- **Location**: `composer.bat:1` — `E:\DevOps\OpenServer\userdata\composer\composer.bat %~1`

### P2 — .gitignore Misconfiguration
- **Location**: `.gitignore:3` — `#composer.lock` commented out while `composer.lock` is tracked.

### P3 — Information Leak in Error Responses
- **Location**: `src/ErrorHandler.php:25-30`
- **Finding**: 500 responses include `file`, `line`, `code`, `message` — exposes internal structure.

### P3 — Extremely Short Access Token TTL
- **Location**: `api/tokens.php:8` — `"exp"=>time()+20`
- **Finding**: 20-second access token lifetime (likely a dev value; production should be minutes).

### P3 — SQL ORDER BY Bug
- **Location**: `src/TaskGateway.php:24`, `src/TaskGateway.php:42`
- **Finding**: `ORDER BY 'name'` (string literal) instead of `ORDER BY name` — no actual sorting occurs.

### P3 — Mixed/Commented Auth Paths
- **Location**: `api/index.php:33-34` — `authenticateAPIKey()` commented, `authenticateAccessToken()` active.
- **Finding**: Two auth mechanisms (API key + JWT) with one disabled; unclear contract.

### P3 — Duplicate/Confusing SQL in query.sql
- **Location**: `query.sql:56-67`
- **Finding**: `tasks` table gets `user_id` column added twice (lines 57 and 63), with two indexes and a FK added after an UPDATE. Likely copy-paste accumulation.

### P3 — No Rate Limiting, CORS, Helmet Equivalents
- **Finding**: No protection against brute force, no CORS policy, no security headers.

## Code Quality Findings

### DRY Violations
- `TaskGateway`: ~130 lines of commented-out non-user-scoped CRUD duplicates the active user-scoped methods.
- `Auth`: Base64 decode fallback commented out (lines 61-74).
- `query.sql`: Redundant `ALTER TABLE` statements.

### KISS / YAGNI
- Hand-rolled JWT (`JWTCodec`) while `firebase/php-jwt` is declared but unused. Either use the library or remove the dependency.
- Two auth mechanisms (API key + JWT) for a simple task API — one would suffice.

### SOLID
- `TaskGateway` and `UserGateway` are single-responsibility but tightly coupled to `Database` (concrete PDO).
- `Auth` depends on `UserGateway` and `JWTCodec` (good DI), but also emits HTTP responses directly (violates separation).

### Clean/Hexagonal
- No ports/adapters; `Database` is a concrete PDO wrapper. Swapping DB would require changing gateways.

### DDD / EDA
- No domain events, aggregates, or bounded contexts. CRUD-style code — DDD/EDA not applicable.

## Dependency Audit Summary (from `projects/info/deps/rest-api-php.md`)

| Package | Current | Latest | Gap | Note |
|---------|---------|--------|-----|------|
| `vlucas/phpdotenv` | 5.5.0 | 5.8.0 | 3 minor | Safe upgrade |
| `firebase/php-jwt` | 7.0.0 (pinned) | 7.2.1 | 2 minor | **Unused — remove in Phase 7** |
| `ext-pdo` | platform | — | — | Required by code |
| `roave/security-advisories` | dev-latest (dev) | — | — | **Move to `require` or add CI audit** |

Transitive deps (all 1–3 years behind): `graham-campbell/result-type`, `phpoption/phpoption`, `symfony/polyfill-*`.

`composer audit --locked` → exit 0, no advisories (but dev-only metapackage).

## Git Hygiene
- Default branch: `master` → **Gate 4** to rename to `main`.
- No tags, no releases.
- 47 commits, linear history with one merge (Dependabot PR #6).
- `.gitignore` excludes `.env`, `vendor/`, IDE dirs — good.
- `composer.lock` tracked (correct for reproducible installs) but `.gitignore` comment is confusing.

## Documentation Gaps
- No `SECURITY.md`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `CHANGELOG.md`, `docs/DEPLOYMENT.md`, `docs/OPERATIONS.md`, `.env.example`.
- README is minimal (badges + structure list only).

## Out-of-Scope for Phase 3
- Fixing any of the above (Phases 4–11).
- Rotating the P0 credential (owner action + Gate 1).
- Renaming `master` → `main` (Phase 4, Gate 4).

## Unconfirmed Items
- Whether the `query.sql` password is still valid in any environment.
- Latest stable PHP version (not queried; other program repos use `php:8.5`).
- Latest stable Composer version.
- Runtime behavior of the API (no tests, no container).