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

### 2026-08-13 — Player balance field

**Stage:** Ticket 1 preparation — player balance on the `User` model, before the promo API.

**Exact prompt:**

```text
Add a balance field for the player. In this project, the `User` model represents the player.

Store the balance as an integer with a default value of `0`. Negative balances must not be allowed.

Add tests for this change and run them.

Do not add login, promo API, or frontend yet. Do not commit or push. At the end, show what was changed and which checks passed.
```

**Result:** Completed. The `users` table gained a non-nullable `bigint` `balance` column defaulting
to `0`, holding money in minor units. Negative balances are blocked by a database `CHECK`
constraint rather than by application code alone. The `balance` attribute was cast to `integer` and
deliberately left out of the model's fillable attributes, so it cannot be changed by mass assignment
of request data. The factory sets an explicit starting balance of `0`, which can be overridden for a
funded player. No login, promo API, or frontend code was added, and nothing was committed or pushed.

**Files or areas changed:**

- `backend/database/migrations/..._add_balance_to_users_table.php` — new migration adding the
  column and the `users_balance_non_negative` check constraint, with both dropped on rollback.
- `backend/app/Models/User.php` — added the `integer` cast for `balance` and a comment recording
  why the attribute is not fillable.
- `backend/database/factories/UserFactory.php` — added an explicit default balance of `0`.
- `backend/tests/Feature/PlayerBalanceTest.php` — new feature test covering the balance rules.

**Commands and validations performed:**

- `php artisan make:migration add_balance_to_users_table --table=users`.
- Inspected the framework source to confirm that model factories create instances inside
  `Model::unguarded()`, so a factory can still seed a non-fillable `balance`, and confirmed that no
  strict mass-assignment mode is enabled in the application service provider. Both facts determine
  how the tests must be written.
- `php artisan migrate --no-interaction` — the new migration applied successfully.
- `php artisan test` — **10 passed (10 assertions)** in 15.23s, covering: a new player starting at
  zero; the column default applying to an insert that omits the balance; integer storage and
  read-back; a balance of zero being allowed; the database rejecting a negative balance on insert;
  the database rejecting one on update through a raw query that bypasses Eloquent; the stored
  balance remaining unchanged after a rejected overdraw; and the balance being ignored when mass
  assigned.
- Queried `information_schema.columns` and `pg_constraint` directly to confirm the live schema:
  `balance` is `bigint`, `NOT NULL`, default `0`, and the constraint reads `CHECK ((balance >= 0))`.

**Problems discovered:**

- PostgreSQL has no unsigned integer types, and Laravel's `unsignedBigInteger` therefore provides no
  protection on this database. An explicit `CHECK` constraint was used instead so the guarantee is
  genuinely enforced by the database.
- In PostgreSQL a failed statement aborts the surrounding transaction, so an assertion made after a
  rejected update would fail with `current transaction is aborted`. The affected test was wrapped in
  a nested transaction, which issues a savepoint, before the suite was run.

**Follow-up corrections:** None required.

### 2026-08-13 — Player login endpoint

**Stage:** Ticket 1 preparation — authentication, before the promo API.

**Exact prompt:**

```text
Add `POST /api/login` for player login with email and password.

After a successful login, return a Sanctum token and the player information, including the balance. Return a clear error for invalid input or credentials.

Add and run tests for the login endpoint.
```

**Result:** Completed. `POST /api/login` validates the credentials through a Form Request, verifies
the password hash, and on success returns a Sanctum personal access token together with the player
represented by an API Resource, including the balance as an integer in minor units. Invalid
credentials return `401` with the stable code `INVALID_CREDENTIALS`; invalid input returns `422`
with the stable code `VALIDATION_FAILED` and the per-field errors. Because this is the first
endpoint, it also establishes the API-wide error contract: every error carries a machine-readable
`code` alongside a human-readable `message`.

**Files or areas changed:**

- `backend/app/Enums/ApiErrorCode.php` — new enum holding the stable error codes.
- `backend/app/Http/Requests/LoginRequest.php` — new Form Request validating the email and password.
- `backend/app/Http/Resources/PlayerResource.php` — new API Resource exposing id, name, email, and
  balance, and nothing else.
- `backend/app/Http/Controllers/LoginController.php` — new thin controller performing the credential
  check and issuing the token.
- `backend/routes/api.php` — registered the login route.
- `backend/bootstrap/app.php` — validation failures on API routes now render with the stable error
  code and message shape.
- `backend/tests/Feature/LoginTest.php` — new feature test covering the endpoint.

**Commands and validations performed:**

- `php artisan test` — **20 passed (48 assertions)** in 20.17s, of which 10 are new and cover:
  successful login returning a token, player, and balance; the issued token authenticating a
  subsequent request; the balance arriving as an integer in minor units; the password never
  appearing in the response; a wrong password rejected with `401`; an unknown email rejected with a
  byte-identical response so registered addresses cannot be discovered; no token being persisted
  when credentials are invalid; and the email, password, and email-format validation failures each
  returning `422` with the stable code.
- `php artisan route:list --path=api` — confirmed `POST api/login` resolves to the controller.
- Live request against the running container: `POST /api/login` with an empty body returned `422`
  with the code `VALIDATION_FAILED` and per-field errors, confirming the contract end to end and
  not only under the test harness.

**Problems discovered:**

- Laravel's common convention is to report bad credentials as a validation error, which yields
  `422`. That conflicts with the requirement to use suitable status codes, since this is an
  authentication failure rather than malformed input, so the endpoint returns `401` instead.
- The framework's default validation response carries no machine-readable identifier. A renderer was
  added so validation errors follow the same contract as every other API error.
- Session-based `Auth::attempt` was avoided in favour of an explicit lookup and hash check, so that
  a token endpoint does not create session state as a side effect.
- The endpoint currently has no brute-force protection. Rate limiting was deliberately not added
  because it was outside the requested scope; it was raised with the user as a recommendation.

**Follow-up corrections:** None required.

### 2026-08-13 — Current player and logout endpoints

**Stage:** Ticket 1 preparation — authenticated session endpoints, before the promo API.

**Exact prompt:**

```text
Add protected `GET /api/me` and `POST /api/logout` endpoints.

`/api/me` should return the current player information, including the balance.

`/api/logout` should delete the current Sanctum token.

Replace the temporary `/api/user` route with `/api/me`. Add and run tests.
```

**Result:** Completed. Both routes sit behind the `auth:sanctum` middleware. `GET /api/me` returns
the player resolved from the token, wrapped under the same `player` key that login uses, so the
frontend has one shape for the player everywhere. `POST /api/logout` deletes only the token that
authenticated the request and returns `204 No Content`, leaving any other session of the same player
signed in. The temporary `/api/user` closure route was removed. The `401` response on protected
routes was also given the stable error code `UNAUTHENTICATED`, so every API error now carries a
machine-readable code.

**Files or areas changed:**

- `backend/app/Http/Controllers/MeController.php` — new controller returning the authenticated
  player.
- `backend/app/Http/Controllers/LogoutController.php` — new controller revoking the current token.
- `backend/routes/api.php` — replaced the temporary route with an `auth:sanctum` group containing
  the two new routes.
- `backend/app/Enums/ApiErrorCode.php` — added the `UNAUTHENTICATED` case.
- `backend/bootstrap/app.php` — authentication failures on API routes now render with the stable
  code and message shape.
- `backend/tests/Feature/MeTest.php`, `backend/tests/Feature/LogoutTest.php` — new feature tests.
- `backend/tests/Feature/LoginTest.php` — the existing token test now calls the new route.

**Commands and validations performed:**

- `php artisan test` — **33 passed (98 assertions)** in 23.49s, of which 13 are new and cover: the
  current player and balance being returned; authentication being required; an unknown token being
  rejected; the response belonging to the token owner and never another player; the password and
  remember token never being exposed; the balance reflecting later changes; logout deleting the
  requesting token; the token no longer authenticating afterwards; only the current token being
  revoked while another session survives; another player's tokens being untouched; and a full login,
  me, logout, and rejected-reuse cycle.
- `php artisan route:list --path=api` — confirmed `POST api/login`, `POST api/logout`, and
  `GET|HEAD api/me`, with the temporary route gone.
- Live requests against the running container: `GET /api/me` without a token returned `401` with the
  code `UNAUTHENTICATED`, and the removed `GET /api/user` returned `404`.

**Problems discovered:**

- Sanctum's testing helper authenticates with a transient token that has no delete method, so a
  logout test written with it would fail at runtime. The tests issue real personal access tokens
  instead, which also exercises the genuine flow.
- The framework's request guard caches the resolved user for the lifetime of the application
  instance and does not clear it when the request is replaced. Two requests inside one test would
  therefore reuse the first user, hiding the effect of logout. The affected tests forget the
  resolved guards between requests, which matches production, where every request is served by a
  fresh instance.
- Authentication failures were the only API error without a machine-readable code, which would have
  left the frontend parsing a bare message for the most common failure on protected routes. The
  stable code was added so the contract is uniform.

**Follow-up corrections:** None required.