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

**Commit:** `7a766f7`

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

**Commit:** `7a766f7`

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

**Commit:** `7a766f7`

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

**Commit:** `7b8d5c9`

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

**Commit:** `5c907b6`

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

**Commit:** `75aa7c3`

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

**Commit:** `75aa7c3`

### 2026-08-14 — Base SCSS structure and design tokens

**Stage:** Frontend setup — design system, before any feature work.

**Prompt:**

```text
Create the base SCSS structure in frontend/src/styles:

- _colors.scss
- _variables.scss
- _mixins.scss
- _typography.scss
- _themes.scss
- _reset.scss
- main.scss

Add semantic colors and CSS variables for light and dark themes. Set up base typography, a reset, and a few simple responsive mixins.

Connect everything through main.scss. Remove the default Vite styles, components, and images that are no longer needed. Component-specific styles should use <style scoped lang="scss">.

Verify TypeScript and the production build.
```

**Result:** The stylesheet layer was created following the structure documented in CLAUDE.md, with
the palette, non-colour tokens, mixins and typography under `src/styles/abstracts` and the themes,
reset and entry point directly under `src/styles`. Raw colour values exist only in the palette file;
`_themes.scss` maps them to semantic custom properties for background, surface, text, border,
primary, success, warning, danger, focus and shadows, in a light and a dark set that follows the
operating system until an explicit `data-theme` choice overrides it. A barrel file exposes the
tokens, mixins and typography to components while deliberately withholding the palette, so a
component cannot reach a hex value. Mixins cover mobile-first breakpoints, a focus ring and visually
hidden content. An `@` alias was added to Vite and to the TypeScript configuration so component
styles can import the abstracts from any depth. The Vite starter component, images and stylesheet
were removed and `App.vue` was reduced to a placeholder whose styles use `<style scoped lang="scss">`.
A forced full `vue-tsc` build reported no type errors, the production build passed, and the built CSS
was checked to contain both theme blocks while every component declaration referenced a semantic
custom property rather than a colour value.

**Commit:** `7d65fe1`

### 2026-08-14 — Theme toggle

**Stage:** Frontend setup — light and dark theme switching.

**Prompt:**

```text
Add a light and dark theme toggle component.

The toggle should:

- update data-theme on the root HTML element;
- save the choice in localStorage;
- restore the choice after a page reload;
- use the system theme when the user has not selected one;
- have a clear accessible label.

Add the toggle to App.vue and use scoped SCSS. Verify TypeScript and the production build.
```

**Result:** A `useTheme` composable holds the theme state at module level so every caller shares one
source of truth. It reads any stored choice on load, writes `data-theme` on the root element, and
removes the attribute when no choice exists, which is what makes the stylesheet fall back to the
system preference. A media query listener keeps the label correct when the operating system changes
theme while no explicit choice is stored, and every access to local storage is guarded so private
browsing degrades to a session-only toggle instead of failing. The `ThemeToggle` component renders a
sun or moon icon with a visually hidden label that names the action rather than the current state,
and uses scoped SCSS with the shared focus-ring and visually-hidden mixins. An inline script in
`index.html` applies a stored theme before the first paint so a reload does not flash the wrong
colours. A forced full `vue-tsc` build reported no type errors, the production build passed, and the
built output was checked to contain the pre-paint script, the attribute handling and the hidden
label styles.

**Commit:** `d0fc26b`

### 2026-08-14 — Login page

**Stage:** Frontend — login screen, before the API is connected.

**Prompt:**

```text
Create a polished responsive login page for Promo Bonus App.

Add:

- a clean layout with a login card;
- email and password fields;
- custom BaseInput and BaseButton components;
- a LoginForm component;
- a simple application logo or text mark;
- the theme toggle;
- loading and disabled button states.

Use the existing SCSS tokens, light and dark themes, and <style scoped lang="scss">. The page should look good on mobile and desktop and be accessible by keyboard.

Do not connect the form to the API yet. Also change the page title from frontend to Promo Bonus App.

Verify TypeScript and the production build.
Organize reusable UI components in src/components/ui, authentication components in src/components/auth, and the page in src/views/LoginView.vue. Keep App.vue simple.
```

**Result:** The frontend was organised into `src/components/ui` for the reusable controls, with the
existing theme toggle moved there, `src/components/auth` for `LoginForm`, and `src/views` for
`LoginView`, leaving `App.vue` as a single render of the page. `BaseInput` binds a generated id to
its label, exposes optional error text through `aria-describedby` and `aria-invalid`, and supports a
disabled state. `BaseButton` treats loading as implying disabled so a request cannot be submitted
twice, and pairs its spinner with a visually hidden status for screen readers. `LoginForm` keeps the
submit button disabled until both fields have content and emits the credentials upwards rather than
calling the API, while `LoginView` owns the loading state that will hold the request. The card
layout is mobile first with a text mark, the theme toggle, and colours taken entirely from the
semantic tokens so both themes work. The page title was changed to Promo Bonus App. A forced full
`vue-tsc` build reported no type errors, the production build passed, and the built output was
checked for the accessibility attributes, focus rings, disabled styles and the responsive
breakpoint.

**Commit:** `d8e776d`

### 2026-08-14 — Login connected to the API

**Stage:** Frontend — authentication against the real backend.

**Prompt:**

```text
Connect the existing login form to POST /api/login.

Create a separate typed axios client and authentication service in src/services. Read the backend URL from VITE_API_URL and add it to .env.example.

Add types for the player, login response, and API errors. Show the loading state during the request. For 422 responses, show the related field errors, and for invalid credentials, show a clear general error message.

After a successful login, save the token and temporarily show the player's name and balance instead of the form.

Do not implement promo codes or history yet. Verify failed and successful login against the real backend, TypeScript, and the production build.
```

**Follow-up correction prompt:**

```text
Create one local test player for manually verifying the login. Do not add a separate file for creating this player or store the login credentials in the repository. Show the test email and password only in the final response.
```

**Result:** A typed axios client in `src/services` reads its base URL from `VITE_API_URL`, attaches
the bearer token to later requests, and normalises both backend errors and transport failures into a
single shape carrying a code, a message and per-field messages. The authentication service performs
the login request and keeps the issued token. Types were added for the player, the login response
and API errors, and the environment variable itself was typed so a misspelling is a compile error.
The form shows the loading state, renders 422 messages against the fields they belong to, and shows
the backend's own message as a general alert for invalid credentials; after a successful login the
page shows the player's name and formatted balance instead of the form. `.env.example` documents the
variable and `.env` was added to the frontend ignore rules, which the Vite scaffold did not cover.
Against the running backend a wrong password returned `401` with `INVALID_CREDENTIALS`, malformed
input returned `422` with per-field errors, and a valid login returned `200` with the token and
player, whose token then authenticated `GET /api/me`. Cross-origin requests from the dev server were
confirmed to be allowed. A forced full `vue-tsc` build reported no type errors and the production
build passed. Promo codes and history were not implemented.

**Commit:** Not yet committed.

### 2026-08-14 — Frontend authentication flow

**Stage:** Frontend — session restore and sign out.

**Prompt:**

```text
Complete the frontend authentication flow.

When the application starts:

- read the stored token;
- attach it to axios;
- call GET /api/me;
- show a loading state while the session is checked;
- show the authenticated player when the token is valid;
- show the login form when the token is missing or invalid.

Add a logout button that calls POST /api/logout, clears the token, and returns to the login form.

Keep the shared authentication state in a separate composable and reuse the existing types, services, and components. Do not implement promo codes yet.

Verify the complete flow: login → page reload → /api/me → logout. Also verify TypeScript and the production build.
```

**Result:** A `useAuth` composable holds the session at module level as a three state value of
checking, authenticated or guest, so every component sees the same session. On start up it reads the
stored token, attaches it to the client and confirms it with `GET /api/me` before anything is shown,
which prevents a stale token from flashing an authenticated screen. A token the server rejects with
`UNAUTHENTICATED` is discarded, while a transport failure keeps it so a later start can succeed. The
authentication service gained calls for the current player and for signing out, the latter forgetting
the token locally even when the request fails so a player is never stuck in a session they cannot
leave. The header was extracted into a shared component with a slot for page actions, the spinner was
extracted so the button and the session screen share one implementation, and the button gained a
secondary variant for signing out. `App.vue` now switches between the loading state, the
authenticated view and the login form. Against the running backend the full sequence was confirmed:
login returned `200` with a token, `GET /api/me` returned the player, `POST /api/logout` returned
`204`, and the same token afterwards returned `401`, as did an invented one. A forced full `vue-tsc`
build reported no type errors and the production build passed. Promo codes were not implemented.

**Commit:** Not yet committed.

### 2026-08-14 — Promo code claim form

**Stage:** Ticket 1 frontend — claiming a promo bonus.

**Prompt:**

```text
Add a promo code claim form to the dashboard and connect it to POST /api/promo/claim.

Create a separate promo service, types, and a PromoClaimForm component in src/components/promo.

The form should:

- accept a code containing 6–12 Latin letters or digits;
- show a loading state;
- prevent repeated submission while the request is running;
- show a success message with the credited bonus amount;
- show the clear error reason returned by the backend;
- update the player's balance on the dashboard after success.

Make the form responsive and reuse the existing components, SCSS tokens, and scoped styles. Do not add history yet.

For manual verification, create the required local promo codes with a one-off command without adding a separate file to the project. Verify malformed, unknown, expired, valid, and repeated codes. Also verify TypeScript and the production build.
```

**Follow-up correction prompt:**

```text
Fix only the PromoClaimForm validation behavior.

Currently, an invalid code such as ABC disables the button, so the backend 422 response and field error cannot be shown in the form.

Disable the button only when the field is empty or while loading. Submit any non-empty code to the backend so it remains the validation authority and the existing form can display errors.code.

Keep the format hint. Verify ABC through the form, TypeScript, and the production build. Do not change anything else.
```

**Result:** A promo service and its request and response types were added, and a `PromoClaimForm`
component in `src/components/promo` reuses the existing input, button and alert. The dashboard owns
the request state and the form stays presentational, matching how the login form already works. The
code is bound with `v-model` so the page can clear it after a successful claim. Submission is
blocked while a request runs, both through the disabled button and a guard in the handler. A
successful claim shows the credited amount as a success alert and updates the visible balance from
the value the backend returned, through a new `updateBalance` on the authentication composable.
Validation messages are rendered against the field and any other refusal shows the backend's own
reason. The input gained an optional hint, announced with the field, describing the accepted format.
Promo codes for verification were created with one-off commands rather than a file in the
repository. Against the running backend a malformed code returned `422` with a field message, an
unknown code `409` `PROMO_CODE_NOT_FOUND`, an expired code `409` `PROMO_CODE_EXPIRED`, a valid code
`201` crediting the bonus and returning the new balance, and repeating it `409`
`PROMO_CODE_ALREADY_CLAIMED`. A forced full `vue-tsc` build reported no type errors and the
production build passed. History was not implemented.

The first version also mirrored the accepted format on the client and kept the button disabled until
a code matched it, which meant a malformed code could never reach the backend and its field message
could never be shown. That was corrected: the button is now disabled only for an empty field or a
running request, any other code is sent, and the backend's `422` message is rendered against the
field. The format hint remains as guidance. Submitting `ABC` was confirmed to reach the backend and
return the field message, and the type check and production build were run again.

**Commit:** Not yet committed.

### 2026-08-14 — Promo claim history on the dashboard

**Stage:** Ticket 1 frontend — claim history, filters and pagination.

**Prompt:**

```text
Add promo claim history to the dashboard and connect it to GET /api/promo/history.

Extend the promo service and types for history records and pagination. Create separate history components in src/components/promo.

The history should show:

- code;
- date;
- bonus amount;
- status;
- rejection reason when present.

Add filters for All, Applied, Rejected, and Revoked. Use the backend pagination of 10 records with Previous/Next buttons and the current page number.

Show loading, empty, and loading-error states. After a successful promo claim, automatically refresh the first history page.

Also update the shared BaseSpinner component. Instead of a circular spinner, it should display three dots in a horizontal row. The dots should animate smoothly one after another, inherit their color from the parent, and work correctly inside buttons and larger loading states. Respect prefers-reduced-motion and do not create another loader component.

Make the list responsive and accessible, using the existing SCSS tokens and scoped styles. Do not add the revoke button yet.

Verify history, filters, every loader usage, TypeScript, and the production build.
```

**Result:** Generic pagination types were added alongside the existing API error types, and promo
specific types now cover a claim record, its status and rejection reason, and the history query. The
promo service gained a history call whose omitted parameters simply list everything. Four focused
components were added: filters as a group of pressed buttons, one record, the list with its loading,
empty and error states, and pagination with Previous and Next around the current page number. A
reusable status badge was added and the record maps a status to its wording and colour, showing the
rejection reason underneath when present and a dash where no money moved. The dashboard owns the
history state, resets to the first page whenever the filter changes, and reloads the first page after
a successful claim while keeping the chosen filter rather than silently resetting it. The shared
loader now renders three dots that pulse in sequence, scaled from the same custom property the button
and the full page loader already set and coloured by `currentColor`, with an explicit rule that
leaves them steady rather than frozen mid animation when reduced motion is preferred; no second
loader component was introduced. Against the running backend the default page returned ten of fifteen
records across two pages, the second page returned the remaining five, and the three filters returned
one applied, thirteen rejected across two pages, and one revoked. A forced full `vue-tsc` build
reported no type errors and the production build passed. The revoke button was not added.

**Commit:** Not yet committed.

### 2026-08-14 — Promo bonus revocation in the history

**Stage:** Ticket 2 frontend — revoking a credited bonus.

**Prompt:**

```text
Add promo bonus revocation to the history and connect it to PATCH /api/promo/{claimId}/revoke.

Show a Revoke button only for records with the applied status. After it is clicked, show a clean confirmation with Cancel and Confirm revoke buttons.

While the request is running, prevent repeated actions and show a loading state. After a successful revocation:

- update the balance from the backend response;
- refresh the history;
- show the revoked status;
- remove the revoke button.

Show clear backend errors for an already revoked claim, a rejected claim, or insufficient balance.

Extend the existing promo service and types. Do not use the standard window.confirm—make the confirmation part of the existing UI with scoped SCSS.

Verify successful revocation, a repeated attempt, the balance update, TypeScript, and the production build.
```

**Result:** The promo service and types were extended with the revocation call and its response. A
revoke button appears only on records whose status is applied, and activating it replaces the button
with an inline confirmation built from the existing components and scoped styles rather than the
browser dialog. The confirmation spells out the consequence, naming the amount that will be taken
back and that the code cannot be claimed again. Because the activated button unmounts as the
confirmation opens and closes, focus is moved deliberately to Cancel and back again, so a keyboard
user is not dropped to the top of the document; Cancel rather than the destructive action receives
focus. Only one revocation may run at a time: the dashboard tracks which record is in flight, that
record's confirm button shows the loading state, and every other record's revoke button is disabled.
On success the balance is taken from the response and the current history page is reloaded, so the
record shows the revoked status and loses its button. A refusal is shown against the record it
belongs to, carrying the backend's own reason. Against the running backend a balance of 130000 rose
to 131000 on claiming, a revocation returned the revoked status with 130000 and the balance matched,
repeating it returned `409` `PROMO_CLAIM_ALREADY_REVOKED` while leaving the balance untouched,
revoking a rejected attempt returned `409` `PROMO_CLAIM_NOT_REVOCABLE`, and an unknown claim returned
`404`. Insufficient balance is covered by the backend suite rather than by moving demo data. A forced
full `vue-tsc` build reported no type errors and the production build passed.

**Commit:** Not yet committed.

### 2026-08-14 — Dashboard layout and Load more pagination

**Stage:** Ticket 2 frontend — dashboard layout and history paging.

**Prompt:**

```text
Update the dashboard layout and history pagination without changing any other functionality.

On large screens, use a two-column layout:

- a left column around 360px wide containing the player balance card and the promo claim form below it;
- a right column using the remaining width for the larger history card.

The dashboard should use the same maximum width as the header to remove the excessive empty space on both sides. Align both columns at the top and keep consistent spacing between the cards.

On tablets and phones, return to a single-column layout in this order: balance, promo claim form, history.

Also replace the Previous/Next pagination with a single Load more button.

Initially show the first 10 records. Each click on Load more should request the next backend page and append its records to the existing list.

While the next page is loading:

- show the three-dot loader;
- prevent repeated clicks;
- keep the already loaded records visible;
- hide the button after the last page.

When the status filter changes, clear the previous records and load the first page for the new filter. Do not use automatic infinite scroll.

Use CSS Grid, the existing breakpoints, SCSS tokens, and scoped styles. Do not change the API, promo claim form, authentication logic, or data formats.

Verify the desktop, tablet, and mobile layout, filters, Load more behavior, TypeScript, and the production build.
```

**Result:** The dashboard now uses a CSS grid that is a single column by default and becomes a fixed
left column beside a flexible right column at the existing large breakpoint, with both columns
aligned to the top and one spacing token used between and inside them. The two cards of the left
column are wrapped together, so the single column order on smaller screens follows the source order
of balance, claim form and history without any reordering rules. The shared maximum width token was
widened so the dashboard ends at the same edge as the header while still leaving the right column
wider than the sidebar; the header on the login screen therefore spans wider too. The Previous and
Next control was removed and replaced by a single Load more button rendered under the records, which
requests the next backend page and appends it. A separate loading flag distinguishes the first page,
which shows the full loader, from an appended page, which keeps the existing records on screen and
shows the three dot loader inside the button while blocking further clicks. The button disappears
once the last page has been loaded, and a failure while appending is shown under the records rather
than replacing them. Changing the filter discards what was loaded and requests the first page again.
Against the running backend the sequence was confirmed for every filter: fifteen records over two
pages with the button shown then hidden, rejected over two pages, and applied and revoked on a single
page with the button hidden immediately. A forced full `vue-tsc` build reported no type errors and the
production build passed.

**Commit:** Not yet committed.

### 2026-08-14 — Custom email validation on the login form

**Stage:** Frontend — login form input validation.

**Prompt:**

```text
Add custom email validation to the login form.

Disable the browser's native validation messages for the form, but keep the input type="email".

If the email format is invalid, show this message below the field:
"Enter a valid email address."

Show the error after the field loses focus or after a form submission attempt. Do not send the API request while the email is invalid. Clear the error when the user corrects the email.

Keep handling validation errors returned by the backend. Do not change the design or other parts of the form.

Verify TypeScript and the production build.
```

**Result:** The form carries `novalidate` so the browser's own bubbles no longer appear, while the
field keeps its email type for the right keyboard and semantics. A shape check drives the message,
which is held back until the field has lost focus or a submission has been attempted, so nothing
appears while the address is still being typed. Submitting with an invalid address marks the attempt
and returns without emitting, so no request is sent. Because the message is computed from the current
value it disappears as soon as the address becomes valid. A backend message for the same field still
shows whenever the format itself is acceptable. The blur was observed through `focusout`, since
`blur` does not bubble to the component's root element, which kept the change inside the form. The
stylesheet came out byte for byte the same size as the previous build, confirming the design was
untouched. A forced full `vue-tsc` build reported no type errors and the production build passed.

**Commit:** Not yet committed.
