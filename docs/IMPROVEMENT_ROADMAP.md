# Improvement Roadmap — rest-api-php

## Phase Applicability (4–11)

| Phase | Agent | Applies? | Reason / Plan |
|-------|-------|----------|---------------|
| 4 | `git-steward` | **Yes** | `gitleaks` full history (P0 secret), rename `master`→`main` (Gate 4), delete merged branches, add Dependabot config, fix `.gitignore`, remove `composer.bat`. |
| 5 | `container-engineer` | **Yes** | Create from zero: multi-stage `Dockerfile` (base `php:8.3-cli-alpine` → deps → test → runtime `php:8.3-fpm-alpine` + nginx or FrankenPHP), `docker-compose.yml` (app + MariaDB), `Makefile` (`verify`=`build`+`test`, `up`), `.dockerignore`, `.env.example`. Characterization tests in `test` stage → `docs/BASELINE.md`. |
| 6 | `upgrader` | **Yes** | Order: PHP constraint → `phpdotenv` 5.5→5.8 → drop `firebase/php-jwt` (Phase 7) → polyfills 1.28→1.43 → move `roave` to `require` → Composer 2.x latest. Each step: `make verify`. |
| 7 | `cleaner` | **Yes** | Remove `firebase/php-jwt`, commented code in `TaskGateway`/`Auth`, typo `$user_gatwey`→`$user_gateway`, `ORDER BY 'name'` fix, cleanup `query.sql`, remove `composer.bat`. `make verify` after each batch. |
| 8 | `architect` | **Selective** | DRY: remove duplicate commented methods. KISS/YAGNI: pick one auth (JWT), drop API key or document both. SOLID: gateways already DI-friendly; consider `DatabaseInterface` if DB swap needed (unconfirmed). Clean/Hexagonal: **n/a** (no external system swap). DDD: **n/a** (CRUD tasks). EDA: **n/a** (no async reactions). Modular monolith: **n/a** (single deployable). ADRs for each applied principle in `docs/adr/`. |
| 9 | `hardener` | **Yes** | Security: rotate `SECRET_KEY`, make token TTL configurable, add rate limiting (middleware or nginx), CORS policy, security headers, generic 500 errors, input validation hardening, secret scan in CI. Performance: add query indexes if needed, connection pooling (PHP-FPM), pagination for `getAllForUser`. Observability: structured JSON logs, `/health/live` + `/health/ready`, metrics endpoint. Quality: PHPStan level 5, PHP-CS-Fixer, `composer audit` in CI. |
| 10 | `ci-engineer` | **Yes** | GitHub Actions: matrix PHP 8.3/8.4, jobs: lint (PHP-CS-Fixer), static (PHPStan), test (Docker `test` stage), audit (`composer audit`), build (Docker), secret scan (gitleaks). Concurrency, cache, timeout, `permissions: read-all`. Target ≤ 10 min. |
| 11 | `tester` | **Yes** | Unit: `JWTCodec`, `Auth`, gateways, `TaskController`. Integration: API endpoints (login, refresh, CRUD), auth flows, token rotation, expired token cleanup. Edge cases: invalid tokens, missing fields, concurrent updates. Coverage target ≥ 80%. `docs/TESTING_STRATEGY.md`, `docs/TEST_REPORT.md`. |

---

## Per-Phase Risk Assessment

| Phase | Risk | Mitigation |
|-------|------|------------|
| 4 | History rewrite (Gate 1) for P0 secret | Document only; owner rotates credential; rewrite + force push only after approval. |
| 4 | Branch rename `master`→`main` (Gate 4) | Coordinate with GitHub settings (branch protection, default branch). |
| 5 | No existing tests → characterization tests may be brittle | Start with happy-path API calls; record responses in `BASELINE.md`. |
| 6 | PHP version constraint may break on older systems | Choose `^8.3` (actively supported); test in Docker on 8.3 and 8.4. |
| 6 | Removing `firebase/php-jwt` is safe (unused) but verify no dynamic `class_exists` checks | Grep confirms zero references; removal is safe. |
| 7 | Deleting commented code — ensure no reflection/dynamic calls | Static analysis + grep; no dynamic usage found. |
| 8 | Over-engineering — apply only where it solves a real problem | Each ADR must state "why here"; KISS/YAGNI win conflicts. |
| 9 | Hardening may change behavior (e.g., rate limiting) | Characterize baseline first; document changes; feature-flag new restrictions. |
| 10 | CI budget (≤10 min) — Docker build + test can be slow | Use BuildKit cache, parallel jobs, `docker/build-push-action` with layer caching. |
| 11 | No existing test infra — bootstrap from zero | Use PHPUnit 11 + `phpunit/phpunit` in `require-dev`; Docker test stage runs it. |

---

## Standing Decisions — Where They Land

| Decision (CLAUDE.md §50–57) | Target / Location |
|------------------------------|-------------------|
| 1. Latest versions always | Phase 6: PHP `^8.3`, `phpdotenv` `^5.8`, drop `firebase/php-jwt`, polyfills `^1.43`, `roave` in `require`, Composer 2.latest |
| 2. Docker-first | Phase 5: `Dockerfile` (multi-stage), `docker-compose.yml`, `Makefile` (`verify`, `up`, `test`, `lint`, `shell`, `logs`) |
| 3. No new lib before dep analysis | Phase 2 done; any new lib (PHPUnit, PHPStan, PHP-CS-Fixer, migration tool) → `docs/DEPENDENCY_DECISIONS.md` |
| 4. Semantic Git | Phase 3 commit: `docs: add architecture, repository audit, technical debt and improvement roadmap`; Phase 4+ follow Conventional Commits |
| 5. Branch hygiene | Phase 4: `master`→`main` (Gate 4), delete merged branches, only `main`/`test` remain |
| 6. Dead code removal | Phase 7: remove `firebase/php-jwt`, commented methods, typo vars, debug leftovers |
| 7. Design principles | Phase 8 ADRs: DRY (remove dupes), KISS/YAGNI (one auth, no router), SOLID (gateways OK), Clean/Hexagonal (n/a), DDD (n/a), EDA (n/a) |
| 10. Skill loop | Phase 3 handoff includes skill proposal for `phase-3-audit-php` |
| 12. No AI attribution | All commits/PR: no `Co-Authored-By`, no session trailers |

---

## Docker Target (Phase 5)
- **Base**: `php:8.3-cli-alpine` (build), `php:8.3-fpm-alpine` + `nginx:alpine` or `dunglas/frankenphp` (runtime)
- **Stages**: `base` → `deps` (composer install `--no-dev` + `--no-scripts`) → `test` (composer install `--dev`, run PHPUnit) → `runtime` (copy only prod deps + source, non-root user, `HEALTHCHECK` on `/health/live`)
- **Compose**: `app` (FrankenPHP/FPM+nginx), `db` (MariaDB 11, healthcheck), `cron` (delete-expired-refresh-tokens)
- **Ports**: From `projects/info/state/ports.md` (reserved block for this repo)
- **Secrets**: `SECRET_KEY`, `DB_PASS` via BuildKit `--mount=type=secret` at build; runtime via `.env` / Docker secrets

---

## CI Budget (Phase 10)
- **Target**: ≤ 10 minutes wall-clock on PR
- **Jobs** (parallel where possible):
  1. `lint` (PHP-CS-Fixer --dry-run) — ~1 min
  2. `static` (PHPStan level 5) — ~2 min
  3. `test` (Docker `test` stage) — ~4 min (cached image)
  4. `audit` (`composer audit --locked`) — ~30 s
  5. `secret-scan` (gitleaks) — ~30 s
  6. `build` (Docker runtime stage) — ~2 min (reuses cached layers)
- **Cache**: `actions/cache` for Composer, `docker/build-push-action` with `cache-from/to` (GitHub cache or registry)
- **Concurrency**: `cancel-in-progress: true` on PRs
- **Timeouts**: Each job ≤ 15 min

---

## Upgrade Targets (from `projects/info/deps/rest-api-php.md`)

| Package | Current | Target | Phase |
|---------|---------|--------|-------|
| PHP | (none declared) | `^8.3` | 6 (prereq) |
| `vlucas/phpdotenv` | 5.5.0 | 5.8.0 | 6 |
| `firebase/php-jwt` | 7.0.0 (pinned) | **REMOVE** | 7 |
| `symfony/polyfill-ctype` | 1.28.0 | 1.37.0 | 6 |
| `symfony/polyfill-mbstring` | 1.28.0 | 1.43.3 | 6 |
| `symfony/polyfill-php80` | 1.28.0 | **DROP** (PHP ≥ 8.0) | 7 |
| `graham-campbell/result-type` | 1.1.1 | 1.2.0 | 6 |
| `phpoption/phpoption` | 1.9.1 | 1.10.0 | 6 |
| `roave/security-advisories` | dev-latest (dev) | dev-latest (**move to `require`**) | 6 |

---

## Gate Status

| Gate | Description | Status |
|------|-------------|--------|
| 1 | History rewrite / force push (P0 secret) | **Not executed** — documented only; requires owner rotation + approval |
| 4 | Default branch rename `master`→`main` | **Not executed** — Phase 4, requires approval |
| 5 | Breaking change to public API/contract | N/A — no public API consumers documented |
| 7 | New shared library creation | N/A — Phase 13 |
| 8 | Shared library major bump | N/A |
| 9 | Deploy to real cluster / real secrets | N/A — Phase 16 |
| 10 | Phase 3 plan approval | **This PR** — `needs-approval` |

---

## Out-of-Scope for This Repo (Phases 12–17)
- Phase 12 (Merge small repos): This repo is not a merge candidate.
- Phase 13 (Shared library): Duplication scan in Phase 13; this repo may contribute utilities (JWT, DB, validation) if ≥3 repos share them.
- Phase 14 (Rename): Format `<short_business_name>_BestPracticeArcht` — e.g., `task-api_BestPracticeArcht` (Gate 2).
- Phase 15 (Docs/manifest/diagrams): Standard community files + `repo-manifest.yaml` + Mermaid diagrams.
- Phase 16 (Platform repo): Umbrella repo for multi-service deploy (if ecosystem grows).
- Phase 17 (Final report): Aggregates all phases.

---

## Unconfirmed Assumptions
- Latest stable PHP is 8.3.x or 8.4.x (to be queried at Phase 6 start).
- No external consumers of the API (no webhook contracts, no mobile apps) — if they exist, Phase 6 Gate 5 may apply.
- The `query.sql` password is not used in any live environment (owner must confirm).
- No runtime integrations with other `RasimAghayev` services (static analysis only).