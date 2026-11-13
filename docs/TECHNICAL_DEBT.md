# Technical Debt — rest-api-php

## Classification Legend
- **P0** — Critical: security, data loss, compromise, blocking release
- **P1** — High: correctness, maintainability, upgrade blockers
- **P2** — Medium: missing infrastructure, testability, ops gaps
- **P3** — Low: cosmetics, dead code, minor inconsistencies

---

## P0 — Critical

### TD-001: Hardcoded Database Password in `query.sql` and Full History
- **Files**: `query.sql:3`, `query.sql:12`, `query.sql:13`, `query.sql:14`
- **Commit**: `9cf0c8e` (2021-12-25) → all 47 commits
- **Description**: MySQL/MariaDB `CREATE USER ... IDENTIFIED BY '<REDACTED>'` and `GRANT ... IDENTIFIED BY '<REDACTED>'`.
- **Impact**: Credential is **compromised** (CLAUDE.md). Must be rotated by owner. History rewrite required (Gate 1).
- **Remediation**: Owner rotates credential; Phase 4 runs `gitleaks` on full history; history rewrite + force push (Gate 1).
- **Status**: Documented, not fixed.

---

## P1 — High

### TD-002: Missing PHP Version Constraint in `composer.json`
- **File**: `composer.json` (no `require.php`)
- **Description**: Code uses PHP 8.0+ features (constructor promotion, typed properties, `array|false` returns). Composer cannot enforce the floor; installs on PHP < 8.0 will break at runtime.
- **Remediation**: Add `"php": "^8.3"` (or latest stable at Phase 6 time) to `composer.json:require`.
- **Phase**: 6 (upgrade) — prerequisite for all upgrades.

### TD-003: Dead Dependency `firebase/php-jwt` Pinned to v7.0.0
- **File**: `composer.json:16` — `"firebase/php-jwt": "v7.0.0"`
- **Description**: Never imported/used; auth is hand-rolled in `JWTCodec.php`. Exact pin blocks v7.1/v7.2 security and correctness fixes.
- **Remediation**: Remove from `composer.json` and `composer.lock` (Phase 7). If JWT library is desired later, add latest and replace `JWTCodec`.

### TD-004: Advisory Blocking Only in Dev Dependencies
- **File**: `composer.json:21` — `"roave/security-advisories": "dev-latest"` in `require-dev`
- **Description**: `composer install --no-dev` (standard production deploy) loses all vulnerability blocking.
- **Remediation**: Move to `require` OR add `composer audit` CI job that runs on the locked set (Phase 10).

### TD-005: Sample Signing Key in `JWTCodec.php` Comment
- **File**: `src/JWTCodec.php:21-26`
- **Description**: Commented 64-hex-char string from a key-generator site. If it matches deployed `SECRET_KEY`, it is a live signing key.
- **Remediation**: Rotate `SECRET_KEY` (owner), delete the comment (Phase 4 or 7).

### TD-006: Hand-Rolled JWT Implementation
- **File**: `src/JWTCodec.php`
- **Description**: Custom HS256 encode/decode with `hash_hmac` + `hash_equals`. While correct in principle, it duplicates `firebase/php-jwt` (declared but unused) and lacks claims validation (nbf, iat, aud, iss), key rotation support, algorithm agility.
- **Remediation**: Either (a) remove dead dependency and keep hand-rolled with documented limitations, or (b) adopt `firebase/php-jwt` v7.2.1+ and delete `JWTCodec.php`. Decision in Phase 7/8.

### TD-007: No Automated Tests
- **Description**: Zero test files, no PHPUnit config, no CI. No regression safety net for any phase.
- **Remediation**: Phase 5 adds characterization tests in Docker `test` stage; Phase 11 builds full suite.

### TD-008: No Container / CI / Build Pipeline
- **Description**: No Dockerfile, docker-compose, Makefile, GitHub Actions. Cannot build, test, or deploy reproducibly.
- **Remediation**: Phase 5 (container baseline), Phase 10 (CI/CD).

---

## P2 — Medium

### TD-009: No `.env.example` Documenting Required Variables
- **Description**: Required env vars: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `SECRET_KEY`. Undocumented; onboarding friction.
- **Remediation**: Phase 5 creates `.env.example` with placeholders.

### TD-010: Committed Developer Artifact `composer.bat`
- **File**: `composer.bat:1` — hardcoded `E:\DevOps\OpenServer\userdata\composer\composer.bat %~1`
- **Description**: Machine-specific path; useless to other developers/CI.
- **Remediation**: Delete file; document `composer` usage in README (Phase 4 or 7).

### TD-011: `.gitignore` Comments Out Tracked `composer.lock`
- **File**: `.gitignore:3` — `#composer.lock`
- **Description**: Lockfile is correctly tracked (reproducible installs) but the comment suggests it should be ignored.
- **Remediation**: Remove the comment line (Phase 4).

### TD-012: SQL Schema File Contains Duplicate/Confusing Statements
- **File**: `query.sql:56-67`
- **Description**: `tasks` table gets `user_id` column added twice (lines 57, 63), two indexes, FK after UPDATE. Accumulated migrations not cleaned.
- **Remediation**: Replace with a single canonical `CREATE TABLE` + indexes + FK (Phase 7 or 8).

### TD-013: `ORDER BY 'name'` Bug (String Literal)
- **Files**: `src/TaskGateway.php:24`, `src/TaskGateway.php:42`
- **Description**: `ORDER BY 'name'` sorts by constant string → no actual ordering.
- **Remediation**: Change to `ORDER BY name` (Phase 7).

### TD-014: ErrorHandler Leaks Internal Details in 500 Responses
- **File**: `src/ErrorHandler.php:25-30`
- **Description**: Returns `file`, `line`, `code`, `message` to client — information disclosure.
- **Remediation**: In production, return generic message; log details server-side (Phase 9).

### TD-015: Extremely Short Access Token TTL (20 seconds)
- **File**: `api/tokens.php:8` — `"exp"=>time()+20`
- **Description**: Likely a dev value; production should be 15–30 minutes with refresh token rotation.
- **Remediation**: Make TTL configurable via env (e.g., `ACCESS_TOKEN_TTL=900`) (Phase 8/9).

### TD-016: Mixed Authentication Mechanisms with Commented Code
- **Files**: `api/index.php:33-34`, `src/Auth.php:22-38` (API key), `src/Auth.php:53-94` (JWT)
- **Description**: Two auth paths (API key + JWT); API key path commented in router. Unclear contract.
- **Remediation**: Pick one, remove the other, or document both with clear use cases (Phase 8).

### TD-017: No Rate Limiting, CORS, Security Headers
- **Description**: No protection against brute force, no CORS policy, no `X-Frame-Options`, `Content-Security-Policy`, etc.
- **Remediation**: Phase 9 (hardening) — add middleware or web-server config.

### TD-018: No Migrations System
- **Description**: Schema only in `query.sql`; no versioned migrations, no rollback, no CI check.
- **Remediation**: Phase 8 — add a simple migration runner or adopt a library (e.g., `phinx/phinx`) if justified.

---

## P3 — Low

### TD-019: Commented-Out Code in `TaskGateway` (~130 lines)
- **File**: `src/TaskGateway.php:98-113`, `141-181`, `236-245`
- **Description**: Non-user-scoped CRUD methods duplicated and commented out.
- **Remediation**: Delete (Phase 7).

### TD-020: Commented-Out Base64 Fallback in `Auth`
- **File**: `src/Auth.php:61-74`
- **Description**: Dead code path for non-JWT token format.
- **Remediation**: Delete (Phase 7).

### TD-021: `var_dump` / Debug Comments Left in Code
- **Files**: `src/Auth.php:92`, `src/TaskGateway.php:24` (commented SQL), `register.php:12`
- **Remediation**: Clean up (Phase 7).

### TD-022: Inconsistent Naming: `user_gatwey` (typo) in Multiple Files
- **Files**: `api/index.php:25`, `api/login.php:23`, `api/refresh.php:42`, `register.php:10`
- **Description**: Variable named `$user_gatwey` instead of `$user_gateway`.
- **Remediation**: Rename (Phase 7).

### TD-023: `api/index.php` Router Hardcodes `/tasks` Resource
- **File**: `api/index.php:14-19`
- **Description**: Only handles `tasks`; 404 for anything else. No extensible routing.
- **Remediation**: If more resources added, introduce a router (Phase 8, YAGNI for now).

### TD-024: `index.php` (HTML Client) Hardcodes API Host
- **File**: `index.php:48` — `const API_HOST='https://restapi-php.cmc/api';`
- **Description**: Demo client points to a specific deployed URL; not configurable.
- **Remediation**: Make configurable or document as demo-only (Phase 15).

### TD-025: Postman/HTTP Fixtures Contain Sample Credentials
- **Files**: `http_request/*` — contain `x-api-key: 85e71d6e3038f170e13cbf2ba21c0fc2`, sample passwords.
- **Description**: Not secrets (sample data), but could be confused for real values.
- **Remediation**: Replace with `{{api_key}}` placeholders (Phase 15).

---

## Standing Decisions Mapping (CLAUDE.md §50–57)

| Decision | Applies Here? | How It Lands |
|----------|---------------|--------------|
| 1. Latest versions always | Yes | Phase 6: PHP ^8.3, phpdotenv 5.8.0, drop firebase/php-jwt, polyfills updated, roave moved to require |
| 2. Docker-first | Yes | Phase 5: multi-stage Dockerfile, Compose, Makefile with `verify`/`up` |
| 3. No new lib before dep analysis | Yes | Phase 2 done; any new lib (e.g., migration tool, test framework) needs entry in `docs/DEPENDENCY_DECISIONS.md` |
| 4. Semantic Git | Yes | Phase 3 commits: `docs: add architecture, repository audit, technical debt and improvement roadmap` |
| 5. Branch hygiene | Yes | Phase 4: rename `master`→`main` (Gate 4), delete merged branches |
| 6. Dead code removal | Yes | Phase 7: remove firebase/php-jwt, commented code, typo variables |
| 7. Design principles | Selective | DRY: remove duplicate commented methods. KISS/YAGNI: pick one auth, no router yet. SOLID: gateways already DI-friendly. Clean/Hexagonal: not needed (no DB swap). DDD/EDA: not applicable (CRUD). |
| 10. Skill loop | Yes | Phase 3 audit skill proposal in handoff. |
| 12. No AI attribution | Yes | Commits/PR will have no co-author trailers. |

---

## Unconfirmed / Assumptions
- Latest stable PHP version not queried (assumed ≥ 8.3 based on other program repos using `php:8.5`).
- Whether `query.sql` password is still valid anywhere — cannot be determined from repo.
- Runtime behavior of API (no tests, no container) — characterization tests in Phase 5 will establish baseline.
- Cross-repo coupling: none found statically; runtime integrations not observed.