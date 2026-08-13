# CLAUDE.md

## Project overview

This repository contains a take-home project for an AI-assisted Laravel/Vue developer position.

The application implements promo-code bonus claiming and revocation for authenticated players.

Repository structure:

- `backend/` — Laravel REST API
- `frontend/` — Vue 3 frontend
- `TASK.md` — original assignment and acceptance criteria
- `AI_PROMPTS_LOG.md` — chronological log of AI prompts, iterations, and corrections
- `README.md` — project setup and usage instructions

`TASK.md` is the source of truth. Do not change its requirements.

## Working process

- Work only on the task explicitly requested in the current prompt.
- Do not implement future steps unless they are specifically requested.
- Before editing, inspect the relevant existing files and briefly state the intended changes.
- Make small, focused changes.
- Avoid unrelated refactoring and unnecessary abstractions.
- Reuse existing components, services, types, styles, and patterns.
- Do not add dependencies unless they provide a clear benefit.
- Do not copy proprietary code, assets, components, or styles from other projects.
- All implementation in this repository must be original.
- After making changes, list the modified files and commands/tests that were run.
- Never claim that a command or test passed unless it was actually executed.
- If a requirement is ambiguous, explain the assumption before implementing it.

## Git rules

- Do not change Git user identity, credentials, remotes, or repository configuration.
- Do not commit or push unless explicitly requested.
- Never use force push, reset, or destructive Git commands.
- Stage only files related to the current task.
- Use small commits with Conventional Commit messages.
- Do not add AI attribution or `Co-Authored-By` trailers to commits.

## Security and data rules

- Never commit `.env`, API tokens, credentials, secrets, or personal data.
- Provide safe example values through `.env.example`.
- Never trust player IDs, balance values, bonus amounts, or statuses received from the frontend.
- The authenticated player must always be obtained from the authentication token.
- Every operation must verify resource ownership.
- Never expose another player’s claims or balance.
- Balance-changing operations must prevent duplicate execution, race conditions, and negative balances.
- Store timestamps in UTC.
- Store monetary values as integers in minor units, never as floating-point values.

## Backend rules

- Use Laravel conventions and REST semantics.
- Use Laravel Sanctum for token authentication.
- Keep controllers thin.
- Use Form Request classes for request validation.
- Use API Resources for consistent JSON responses.
- Put promo claim and revoke business logic in focused Action or Service classes.
- Use PHP enums for statuses and rejection reasons where appropriate.
- Use database transactions for every balance-changing operation.
- Use row locking and database-level unique constraints where necessary.
- Application checks alone are not sufficient protection against concurrent requests.
- A player must never receive the same promo bonus twice.
- A revoked promo claim must not become claimable again.
- A repeated revoke must return a clear error and must not modify the balance.
- A revoke must never make the player balance negative.
- Record business-level rejected promo attempts so they can be returned by the history endpoint.
- Return stable error codes and clear human-readable messages.
- Use suitable HTTP status codes for validation, authentication, missing resources, and business conflicts.
- Add feature tests for successful cases, validation failures, authorization, ownership, duplicate claims, expired promo codes, rejected attempts, repeated revocation, insufficient balance, filtering, and pagination.
- Use factories and seeders for demo and test data.

## Frontend rules

- Use Vue 3 with TypeScript.
- Use Composition API with `<script setup lang="ts">`.
- Keep the SFC order: `<script>`, `<template>`, `<style>`.
- Use axios for API requests.
- Keep API requests in typed service modules, not directly inside templates.
- Define TypeScript interfaces for API requests, responses, pagination, claims, errors, and authentication data.
- Keep page components focused on orchestration.
- Extract focused components for forms, history items, filters, status messages, and shared controls.
- Use props down and events up.
- Do not add global state management unless the application genuinely requires it.
- Prevent duplicate submissions while requests are loading.
- Show clear loading, success, empty, and error states.
- Preserve the backend error reason when displaying failures to the user.
- Use stable IDs as `v-for` keys.
- Use semantic HTML, labels, keyboard-accessible controls, and visible focus states.
- Make the interface responsive using a mobile-first approach.

## Frontend component rules

Create reusable UI components only when they are actually used or clearly reduce duplication.

Preferred shared components include:

- `AppButton`
- `AppInput`
- `AppSelect`
- `AppAlert`
- `AppSpinner`
- `StatusBadge`

Do not create a large generic component library for this small project.

Feature-specific components should remain inside the promo feature where appropriate.

## SCSS and design system

Use SCSS for all styling.

Recommended structure:

- `src/styles/abstracts/_colors.scss`
- `src/styles/abstracts/_variables.scss`
- `src/styles/abstracts/_mixins.scss`
- `src/styles/abstracts/_typography.scss`
- `src/styles/_themes.scss`
- `src/styles/_reset.scss`
- `src/styles/main.scss`

Styling rules:

- Keep raw color values in the color palette files.
- Components must use semantic variables such as background, surface, text, border, primary, success, warning, and danger.
- Do not place repeated raw hex colors inside components.
- Define reusable typography mixins for headings, body text, labels, and captions.
- Define only useful mixins, such as responsive breakpoints, focus rings, and visually hidden content.
- Use consistent spacing, radius, shadow, and transition tokens.
- Global styles are allowed only for reset, tokens, themes, and base typography.
- Component styles must use `<style scoped lang="scss">`.
- Support light and dark themes through semantic CSS custom properties.
- The dark-theme choice should be persisted locally.
- Do not use inline styles unless a value must be calculated dynamically.

## API and UI consistency

- Keep API response contracts predictable and documented.
- Do not duplicate backend business rules on the frontend.
- Frontend validation may improve UX, but backend validation remains authoritative.
- After claim or revoke, update both the visible balance and relevant history item.
- Do not silently ignore API errors.
- The confirmation step for revocation must clearly describe the action.

## Documentation and AI prompt log

Keep `AI_PROMPTS_LOG.md` chronological.

After every meaningful implementation, review, or correction prompt, automatically append an entry to `AI_PROMPTS_LOG.md` before giving the final response.

Each entry must contain:

- the project stage or ticket;
- the exact user prompt without changes;
- a brief summary of the completed result;
- the files or areas changed;
- the commands and validations actually performed;
- any problems discovered;
- any follow-up corrections, if required.

Do not record:

- short confirmations or routine status messages;
- prompts whose only purpose is updating the log;
- secrets, credentials, email addresses, or absolute local paths;
- internal AI reasoning.

Never modify or paraphrase the exact text of previously recorded prompts.

Do not include private system information in the log.

Keep `README.md` updated with:

- requirements;
- installation;
- environment setup;
- database migration and seeding;
- backend and frontend start commands;
- demo credentials;
- test commands;
- architectural decisions;
- API overview.