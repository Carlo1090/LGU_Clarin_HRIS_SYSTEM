# Architecture Decision Log

Clarin Municipal HRIS. This log records technical decisions, why they were made,
and what they cost us. Add a new entry (never rewrite an old one) whenever a
decision changes. If a decision is reversed, mark the old entry "Superseded" and
link to the new one.

Entry format: Status, Context, Decision, Consequences.

---

## D-001: Application stack (Laravel + React + Inertia)

- **Date:** 2026-11-17
- **Status:** Accepted
- **Context:** The original build prompt suggested Laravel 11 with Vue. The
  project was already started from the Laravel starter kit using React.
- **Decision:** Keep Laravel 13, PHP 8.4, React 19, Inertia 3, TypeScript and
  Tailwind CSS 4. Do not switch to Vue.
- **Consequences:** Step prompts that mention Vue must be read as React. The
  starter kit already includes Fortify (login, two-factor columns) and a
  passkeys migration, which helps Step 3.

## D-002: PHP 8.4 is the minimum version

- **Date:** 2026-11-17
- **Status:** Accepted
- **Context:** The locked dependencies (Symfony 8, Pest 5, PHPUnit 13) require
  PHP 8.4 or newer. The first Docker build on PHP 8.3 failed because of this.
- **Decision:** Docker image and CI both use PHP 8.4.
- **Consequences:** Anyone running PHP directly on their own machine needs 8.4+.
  `composer.json` still says `^8.3` and should be raised to `^8.4` (see
  Follow-ups).

## D-003: MySQL 8.4 instead of PostgreSQL 16

- **Date:** 2026-11-17
- **Status:** Accepted, to be re-confirmed before Step 2
- **Context:** The build prompt specified PostgreSQL. The project owner chose
  MySQL 8.4.
- **Decision:** Use MySQL 8.4 for development and production.
- **Consequences:**
    - Step 2 (audit log): the prompt asks for `jsonb` columns. MySQL has `JSON`,
      which works but behaves differently. The tamper-protection plan (revoked
      UPDATE/DELETE rights plus triggers) is possible on MySQL, but must be tested
      on MySQL itself.
    - Step 4: PostgreSQL row-level security does not exist in MySQL. The second
      line of defense for sensitive tables must be redesigned (for example
      restricted database views and application-level scopes).
    - Revisit this decision before starting Step 2, while switching is cheap.

## D-004: RustFS replaces MinIO for local file storage

- **Date:** 2026-11-17
- **Status:** Accepted for development only
- **Context:** MinIO removed its free Docker images in September 2026. The
  command `docker pull minio/minio` now fails with "pull access denied".
- **Decision:** Use `rustfs/rustfs` as the S3-compatible store in Docker
  Compose. It is only used for local development.
- **Consequences:**
    - The RustFS project describes some of its S3 compatibility as preview. We
      have not yet tested uploads against it.
    - The image tag is `latest`. Pin an exact version once we depend on it.
    - The production storage choice (self-hosted store, or a government-approved
      cloud store) is still open and must be decided before Step 5, together with
      DICT hosting guidance.

## D-005: The app container does not use `env_file`

- **Date:** 2026-11-17
- **Status:** Accepted
- **Context:** Passing `.env` into the container as real system variables made
  Laravel's test runs ignore their own settings. Tests ran in "local" mode
  against the development MySQL database instead of in-memory SQLite.
- **Decision:** Remove `env_file` from the `app` service. Laravel reads `.env`
  directly from the mounted project folder.
- **Consequences:** Never add `env_file: .env` back to the `app` service. In
  production, settings must be supplied deliberately and separately from tests.

## D-006: Tests run on in-memory SQLite with forced settings

- **Date:** 2026-11-17
- **Status:** Accepted, with a known limit
- **Context:** The starter kit runs tests on SQLite in memory.
  `phpunit.xml` now sets `force="true"` on every `<env>` line so that outside
  settings can never override test settings.
- **Decision:** Keep SQLite for the starter tests.
- **Consequences:** SQLite cannot verify MySQL-specific features such as the
  audit-log triggers planned in Step 2. Before Step 2, add a separate MySQL test
  database and run those tests against it.

## D-007: Frontend is built on the host, not in Docker

- **Date:** 2026-11-17
- **Status:** Accepted for now
- **Context:** The PHP container has no Node.js. The Vite build also runs a PHP
  command (Wayfinder), and node_modules contains platform-specific binaries.
- **Decision:** Run `npm run build` on the developer's computer. CI builds it
  separately on GitHub.
- **Consequences:** Developers need Node and PHP installed locally for the
  frontend. A Node build stage in Docker should be added before production.

## D-008: Modular monolith under `app/Modules/`

- **Date:** 2026-11-17
- **Status:** Accepted
- **Decision:** One Laravel app. Business areas live in `app/Modules/`:
  Core, Auth, Audit, Organization, Employees, Attendance, Leave, Payroll,
  Recruitment, Performance, Reports.
- **Consequences:** Modules must not reach into each other's internals. Shared
  code goes in Core. Boundary rules should be checked automatically (for example
  with PHPStan or Pest architecture tests) once real code exists.

## D-009: PHPStan memory limit raised to 1 GB

- **Date:** 2026-11-17
- **Status:** Accepted
- **Context:** PHPStan crashed at the container's default 128 MB limit.
- **Decision:** `composer types:check` runs `phpstan analyse --memory-limit=1G`.

## D-010: Work happens on the `LGU_UI` branch

- **Date:** 2026-11-17
- **Status:** Accepted
- **Decision:** Step 0 changes are committed on `LGU_UI`. `main` is not changed
  until the CI run on GitHub is green and the owner has reviewed the result.

---

## Follow-ups (open items from Step 0)

- Raise the PHP requirement in `composer.json` from `^8.3` to `^8.4`.
- Restrict Nginx (port 8080) and Mailpit (port 8025) to this computer only, as
  was already done for RustFS.
- Replace placeholder fallbacks for MySQL, Redis and storage passwords in
  `compose.yaml` with required values, as was already done for storage.
- Confirm the first GitHub Actions run passes before treating Step 0 as done.
- Re-confirm D-003 (MySQL) before Step 2.
