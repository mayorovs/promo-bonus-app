# AI Prompts Log

## Project

Promo Bonus App — Laravel REST API and Vue frontend.

Started: 2026-08-13

## AI tools and roles

- Claude Code — project implementation and command execution.

## Logging rules

- Meaningful implementation prompts are recorded verbatim.
- Follow-up prompts and correction requests are recorded as separate entries.
- Entries are kept in chronological order.
- Each entry includes the result, validation performed, and related commit.
- Routine confirmations and unrelated terminal troubleshooting are omitted.
- Secrets, tokens, credentials, and personal data are never included.

## Entries

Command paths in this log are written relative to the repository root.

### 2026-08-13 — Backend project setup (initial prompt)

**Stage:** Project setup — Laravel backend and Docker environment, before Ticket 1.

**Exact prompt:**

```text
Let’s start with the backend project setup.

Read CLAUDE.md and TASK.md before making changes.

PHP and Composer are not installed locally, but Docker is available. Create a Laravel application in the `backend/` directory using Docker. Use PostgreSQL as the database and configure Laravel Sanctum for API token authentication.

For this step:
- initialize only the Laravel backend and its Docker environment;
- keep all existing root documentation files;
- do not implement login, promo codes, claims, history, or revocation yet;
- do not create the Vue frontend yet;
- do not commit or push changes.

After setup:
- start the containers;
- run the default migrations;
- run the Laravel tests;
- verify that the application starts correctly.

At the end, list what was created, all commands that were run, and the results of migrations and tests. If Docker is unavailable or setup fails, stop and explain the exact error instead of trying unrelated system changes.
```

**Result:** Not completed under this prompt. Environment checks were performed and the Docker
daemon was brought up, but the work was interrupted by the user before the Laravel project was
created. Two setup decisions were made and stated: use a hand-written Docker Compose file instead
of Laravel Sail, because Sail's entrypoint assumes WSL2 and a Unix user/group lookup that is
unreliable on native Windows; and run the test suite against PostgreSQL instead of SQLite, because
later tickets depend on partial unique indexes and real row locking.

**Files or areas changed:** None.

**Commands and validations performed:**

- `docker version`, `docker compose version` — Compose v5.0.2 present, but the daemon was not
  running.
- Verified Docker Desktop was installed, then started it and polled `docker info` until ready —
  Docker Engine 29.2.0.
- Bind-mount check via `docker run --rm alpine` — failed under Git Bash before reaching Docker.

**Problems discovered:**

- The Docker daemon was not running at the start of the step.
- Git Bash rewrote container-side paths, so `-w /app` reached Docker as a Windows path and the run
  aborted with `the working directory '...' is invalid, it needs to be an absolute path`. Docker
  commands were switched to PowerShell, which does not perform this conversion.
- Starting Docker Desktop automatically started containers belonging to an unrelated local project.

**Follow-up corrections:** The user interrupted the step over the unrelated containers and issued
the follow-up prompt recorded in the next entry, adding repository-path confirmation, an isolated
Compose project name, and a prohibition on touching unrelated Docker resources.

### 2026-08-13 — Backend project setup (continuation after interruption)

**Stage:** Project setup — Laravel backend and Docker environment, before Ticket 1.

**Exact prompt:**

```text
Continue the previous setup task.

I interrupted the process because starting Docker Desktop automatically attempted to start containers from an unrelated existing project.

Before making changes, confirm that:
- the current working directory is the `promo-bonus-app` repository;
- `git rev-parse --show-toplevel` points to this repository;
- no files or Docker Compose configuration from other projects will be used.

Do not start, stop, modify, or delete any existing unrelated containers, images, networks, or volumes.

Use an isolated Docker Compose project name: `promo-bonus-app`.

After confirming the repository path, continue creating the Laravel backend only inside this repository’s `backend/` directory, following the scope of my previous prompt.
```

**Result:** Completed. Laravel 13.17 (PHP `^8.3`, PHPUnit 12) was created in `backend/` and runs in
an isolated Docker Compose project named `promo-bonus-app`, consisting of a PHP 8.4 CLI application
container and a PostgreSQL 16 container. Laravel Sanctum was installed for API token authentication
and the `HasApiTokens` trait was added to the `User` model. Tests are configured to run against a
dedicated PostgreSQL test database rather than SQLite. The unrelated project's containers stayed in
their existing stopped state and were never started, stopped, or modified; the published host port
for PostgreSQL was deliberately moved off the default so the unrelated database containers remain
usable. Login, promo codes, claims, history, revocation, and the Vue frontend were not implemented,
and nothing was committed or pushed.

**Files or areas changed:**

- `backend/` — new Laravel 13.17 application.
- `backend/Dockerfile` — PHP 8.4 CLI image with the `pdo_pgsql` and `zip` extensions plus Composer.
- `backend/docker-compose.yml` — `app` and `db` services with a pinned project name, a health check
  on PostgreSQL, and configurable host ports.
- `backend/.dockerignore` — excludes `vendor/`, `.git`, and storage caches from the build context.
- `backend/docker/postgres/init/01-create-test-database.sh` — creates the separate test database on
  first boot.
- `backend/routes/api.php` and the Sanctum personal access tokens migration — generated by
  `install:api`.
- `backend/.env` and `backend/.env.example` — switched to the PostgreSQL connection and given
  host-port variables. Credentials are local development values only.
- `backend/phpunit.xml` — tests point at the PostgreSQL test database; only the connection and
  database name are overridden, so no credentials are stored in a committed file.
- `backend/app/Models/User.php` — added the `HasApiTokens` trait.
- `backend/composer.json`, `backend/composer.lock` — added `laravel/sanctum`.
- Removed `backend/database/database.sqlite`, an artifact of the installer that the PostgreSQL
  setup supersedes and that Laravel's `.gitignore` does not cover.

**Commands and validations performed:**

- Confirmed the working directory and that `git rev-parse --show-toplevel` pointed at this
  repository on branch `main`, and that the repository contained no Docker or Compose files that
  could belong to another project.
- Surveyed existing Docker state read-only with `docker ps`, `docker ps -a`, and
  `docker compose ls -a`; confirmed all unrelated containers were already stopped.
- Checked that the intended host ports were free before publishing them.
- Verified the bind mount was readable and writable with a throwaway `alpine` container.
- `docker run --rm composer:2 create-project laravel/laravel backend --no-interaction --prefer-dist`
  — Laravel 13.17 installed.
- `docker compose -p promo-bonus-app -f backend/docker-compose.yml up -d --build` — image built,
  dedicated network and volume created, database reported healthy.
- Listed databases through `psql` — both the development and the test database were present,
  confirming the init script ran.
- `docker compose ... exec app php artisan install:api --no-interaction` — Sanctum installed, API
  routes published, migrations applied.
- `docker compose ... exec app php artisan migrate --no-interaction` — `Nothing to migrate`.
- `docker compose ... exec app php artisan migrate:status` — all four migrations reported `Ran` in
  batch 1: users, cache, jobs, and personal access tokens.
- `docker compose ... exec app php artisan test` — **2 passed (2 assertions)** in 10.49s.
- `docker compose ... exec app php artisan db:show` with the test database name overridden —
  confirmed Laravel connects to the test database, which reported 0 tables as expected.
- HTTP checks: `GET /up` returned 200, `GET /` returned 200, and `GET /api/user` without a token
  returned 401 with `{"message":"Unauthenticated."}`, confirming the Sanctum guard and the JSON
  error contract.
- `git status` — a single untracked `backend/` directory, nothing staged, nothing committed.

**Problems discovered:**

- The default Laravel installer configured SQLite and created a SQLite database file; both were
  replaced by the PostgreSQL configuration.
- The default test suite does not touch the database, so a passing run does not by itself prove the
  test database is reachable. This was verified separately with `db:show`.
- The application's `vendor/` directory now sits inside a cloud-synced folder, which can cause sync
  churn and occasional file locks. Excluding it from synchronisation was left to the user as a
  system-level setting.
- Docker Desktop is configured to start containers on launch, which is what started the unrelated
  project's containers. This setting was reported to the user and deliberately left unchanged.

**Follow-up corrections:** None required.