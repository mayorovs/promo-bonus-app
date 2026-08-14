# AI Prompts Log

## Project

Promo Bonus App — Laravel REST API and Vue frontend.

Started: 2026-08-13

## AI tools and roles

- Claude Code — project implementation and command execution.

## Logging rules

- Meaningful requests are recorded verbatim.
- Entries are kept in chronological order.
- Each entry records the stage, the exact prompt, a short result, any follow-up correction prompt,
  and the related commit.
- Routine confirmations, log-only prompts, and unrelated terminal troubleshooting are omitted.
- Secrets, tokens, credentials, and personal data are never included.

## Entries

### 2026-08-13 — Backend project setup

**Stage:** Project setup — Laravel backend and Docker environment, before Ticket 1.

**Prompt:**

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

**Follow-up correction prompt:**

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

**Result:** Laravel 13.17 created in `backend/`, running in an isolated Docker Compose project named
`promo-bonus-app` with a PHP 8.4 application container and a PostgreSQL 16 container. Sanctum was
installed for API token authentication and the `HasApiTokens` trait added to the `User` model. Tests
run against a separate PostgreSQL test database. Default migrations and the test suite passed, and
the unrelated project's containers were left untouched.

**Commit:** `6a45534`

### 2026-08-13 — Player balance field

**Stage:** Ticket 1 preparation — player balance on the `User` model.

**Prompt:**

```text
Add a balance field for the player. In this project, the `User` model represents the player.

Store the balance as an integer with a default value of `0`. Negative balances must not be allowed.

Add tests for this change and run them.

Do not add login, promo API, or frontend yet. Do not commit or push. At the end, show what was changed and which checks passed.
```

**Result:** The `users` table gained a non-nullable `bigint` `balance` column defaulting to `0`,
holding money in minor units, with a database check constraint that prevents a negative value. The
attribute is cast to integer and deliberately left out of the fillable attributes so it cannot be
mass assigned. Tests passed.

**Commit:** `94bc4df`

### 2026-08-13 — Player login endpoint

**Stage:** Ticket 1 preparation — authentication, before the promo API.

**Prompt:**

```text
Add `POST /api/login` for player login with email and password.

After a successful login, return a Sanctum token and the player information, including the balance. Return a clear error for invalid input or credentials.

Add and run tests for the login endpoint.
```

**Result:** `POST /api/login` validates through a Form Request and returns a Sanctum token together
with the player, including the balance as an integer in minor units. Invalid credentials return
`401` with the code `INVALID_CREDENTIALS`, and invalid input returns `422` with the code
`VALIDATION_FAILED`. This established the API-wide error contract of a stable machine-readable code
alongside a human-readable message. Tests passed.

**Commit:** `94bc4df`

### 2026-08-13 — Current player and logout endpoints

**Stage:** Ticket 1 preparation — authenticated session endpoints, before the promo API.

**Prompt:**

```text
Add protected `GET /api/me` and `POST /api/logout` endpoints.

`/api/me` should return the current player information, including the balance.

`/api/logout` should delete the current Sanctum token.

Replace the temporary `/api/user` route with `/api/me`. Add and run tests.
```

**Result:** Both routes were added behind the `auth:sanctum` middleware and the temporary
`/api/user` route was removed. `GET /api/me` returns the player resolved from the token under the
same `player` key that login uses. `POST /api/logout` deletes only the token that authenticated the
request and returns `204 No Content`. Protected-route `401` responses now carry the stable code
`UNAUTHENTICATED`. Tests passed.

**Commit:** `94bc4df`

### 2026-08-14 — Promo code model and table

**Stage:** Ticket 1 — promo code storage, before bonus claiming.

**Prompt:**

```text
Create a `PromoCode` model and database table.

A promo code should have a unique code, a positive bonus amount, and an expiration date. Store the amount as an integer.

Codes should be case-insensitive: `BONUS10` and `bonus10` are the same promo code. Store the code in uppercase.

Add tests. Do not implement bonus claiming yet.
```

**Result:** A `promo_codes` table and `PromoCode` model store a unique code of at most twelve
characters in canonical uppercase, a bonus amount as a positive integer in minor units, and a
required expiration timestamp. Case insensitivity is guaranteed by the database through a uniqueness
constraint combined with a check constraint that refuses any code which is not already uppercase, so
it does not depend on the model mutator alone. No claiming logic was added. Tests passed.

**Commit:** Not yet committed.

### 2026-08-14 — Promo claim history model and table

**Stage:** Ticket 1 — promo claim history storage, before the API and bonus crediting.

**Prompt:**

```text
Create a model and database table for promo code claim history.

A record should contain the player, the submitted code, the promo code when it exists, the bonus amount, the status, and the rejection reason.

Use the statuses `applied`, `rejected`, and `revoked`.

A player must not have more than one applied or revoked claim for the same promo code. Rejected attempts may be stored more than once.

Add and run tests. Do not implement the API or bonus crediting yet.
```

**Result:** A `promo_claims` table and `PromoClaim` model record the player, the submitted code, an
optional promo code reference, the bonus amount in minor units, the status, and the rejection
reason, with `PromoClaimStatus` and `PromoClaimRejectionReason` as PHP enums. The central rule is
enforced by a partial unique index on the player and promo code limited to the applied and revoked
statuses, so a player can hold at most one settled claim per promo code, a revoked claim keeps
occupying that slot and can never be claimed again, and rejected attempts are excluded and may
repeat. Check constraints additionally keep each row internally consistent: a rejected attempt
carries a reason and no amount, while an applied or revoked claim carries a real promo code, a
positive amount, and no reason. No API or crediting logic was added. Tests passed.

**Commit:** Not yet committed.

### 2026-08-14 — Promo code claim endpoint

**Stage:** Ticket 1 — bonus crediting.

**Prompt:**

```text
Implement `POST /api/promo/claim`.

Resolve the player only from the authentication token. The request should accept a required code containing 6–12 Latin letters and digits.

An invalid format should return `422` and should not be recorded in history. A nonexistent, expired, or already used code should return a separate clear error and be stored as a rejected attempt.

On success, credit the bonus to the player balance, create an applied history record, and return the bonus amount and updated balance.

Make sure repeated or concurrent requests cannot credit the same bonus twice.

Add and run tests. Do not implement the history endpoint, revocation, or frontend yet.
```

**Result:** `POST /api/promo/claim` sits behind `auth:sanctum` and takes only a code, validated as 6
to 12 Latin letters and digits by a Form Request, so a malformed code returns `422` before any
attempt is recorded. The business logic lives in a `ClaimPromoCode` action that runs in a
transaction, locks the player row, and returns the resulting history record rather than throwing, so
a refused attempt is committed instead of rolled back. A nonexistent, expired, or already settled
code is stored as a rejected attempt and answered with `409` under the distinct codes
`PROMO_CODE_NOT_FOUND`, `PROMO_CODE_EXPIRED`, and `PROMO_CODE_ALREADY_CLAIMED`. On success the bonus
is credited, an applied record is written, and the response returns the bonus amount and the updated
balance with `201`. Double crediting is prevented by the row lock plus the partial unique index,
with a revoked claim also blocking a new one. Tests passed.

**Commit:** Not yet committed.

### 2026-08-14 — Promo claim history endpoint

**Stage:** Ticket 1 — claim history listing.

**Prompt:**

```text
Implement `GET /api/promo/history` for the current player.

Return records from newest to oldest, with 10 records per page. Add an optional status filter accepting `applied`, `rejected`, or `revoked`. An invalid status should return `422`.

For each record, return the id, code, bonus amount, status, rejection reason, and creation date. Use Laravel’s standard pagination format.

Add tests for authentication, pagination, filtering, and ensuring that a player can only see their own history.
```

**Follow-up correction prompt:**

```text
Docker is running. Continue the current task.

Start only the `promo-bonus-app` Docker Compose project, then run the full test suite and Laravel Pint. Also verify the history route.

Report only the actual results. If a test fails, find the cause and fix it first.
```

**Result:** `GET /api/promo/history` sits behind `auth:sanctum` and lists only the authenticated
player's claims, ten per page, newest first, in Laravel's standard paginated resource format. Each
record exposes the id, the submitted code, the bonus amount in minor units, the status, the
rejection reason, and the creation date in UTC. The optional status filter is validated against the
status enum, so an unknown value returns `422`. Ordering breaks ties on the id so pagination cannot
repeat or skip a record. The full suite reported 110 passed with 349 assertions, Laravel Pint
reported no style issues across 58 files, and the route answered `401` without a token.

**Commit:** Not yet committed.

### 2026-08-14 — Promo bonus revocation endpoint

**Stage:** Ticket 2 — revoking a wrongly credited bonus.

**Prompt:**

```text
Implement the backend for revoking a promo bonus: PATCH /api/promo/{claimId}/revoke.

A player may revoke only their own successfully applied promo claim. Subtract the original bonus amount from the balance and return the updated balance and claim status.

Trying to revoke an already revoked claim, a rejected claim, or another player's claim should return a clear error.

Add tests.
```

**Result:** `PATCH /api/promo/{claimId}/revoke` sits behind `auth:sanctum` and is handled by a
`RevokePromoClaim` action that runs in a transaction and locks the player row in the same order as
crediting. The claim is looked up scoped to the authenticated player, so another player's claim is
indistinguishable from one that does not exist and both answer `404` with `PROMO_CLAIM_NOT_FOUND`.
An already revoked claim returns `409` with `PROMO_CLAIM_ALREADY_REVOKED`, a rejected attempt
returns `409` with `PROMO_CLAIM_NOT_REVOCABLE`, and a balance too low to absorb the reversal returns
`409` with `INSUFFICIENT_BALANCE` while changing nothing. On success the status moves to revoked
through a compare and swap on the applied state, the original bonus amount is subtracted, and the
response returns the new status and balance. A refused revocation persists nothing, unlike a refused
claim. The full suite reported 126 passed with 416 assertions and Laravel Pint reported no style
issues across 62 files.

**Commit:** Not yet committed.

### 2026-08-14 — Frontend scaffolding and Docker service

**Stage:** Frontend setup — Vue 3, TypeScript and Vite, before any feature work.

**Prompt:**

```text
Create a frontend directory with Vue 3, TypeScript, and Vite. Use the Composition API with <script setup lang="ts">.

Install axios and sass. Add a frontend service to this project's Docker configuration and run it on localhost:5173.

Verify that the starter page loads and the production build passes. Do not implement login or promo code features yet.
```

**Result:** A Vue 3.5 and TypeScript project was scaffolded in `frontend/` with Vite 8, and axios and
sass were installed. Because the Docker configuration now orchestrates two applications, the compose
file was moved from `backend/` to the repository root, with the service build contexts and volumes
repointed at `./backend` and `./frontend`; a root `.env.example` documents the host-side ports and a
root `.gitignore` keeps a real `.env` out of version control. The new frontend service runs the Vite
dev server on `localhost:5173` and keeps `node_modules` in a named Docker volume rather than in the
cloud-synced project folder. A minimal SCSS entry point was added and imported so the sass toolchain
is exercised by both the dev server and the build. The dev server returned the Vue application with
`200`, the compiled SCSS was served, the production build passed including `vue-tsc` type checking,
and the backend suite still reported 126 passed with 416 assertions after the compose move. No login
or promo features were implemented.

**Commit:** Not yet committed.

### 2026-08-14 — Frontend dependencies available to the editor

**Stage:** Frontend setup — editor tooling.

**Prompt:**

```text
Fix the red import errors in local VS Code.

Currently, node_modules is available only inside Docker, so the editor cannot resolve Vue or the image asset types. Update the Docker configuration so the dependencies are also available in frontend/node_modules.

Make sure node_modules remains ignored by Git. After the change, verify TypeScript and the production build.
```

**Result:** The named volume that had been hiding `node_modules` from the host was removed from the
frontend service, so dependencies now install into `frontend/node_modules` inside the bind mount and
the editor can resolve them. No type declaration file was needed: the scaffold already points
`tsconfig.app.json` at `vite/client`, which declares the image asset modules, and that package only
had to become visible on the host. Git still ignores the directory through `frontend/.gitignore`,
confirmed by listing every untracked file and finding no entry under it. A forced full `vue-tsc`
build reported no type errors, the production build passed, and the dev server still answered `200`.

**Commit:** Not yet committed.
