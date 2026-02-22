# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**One Red Paperclip** — a trade-up platform where users create campaigns with a start item and goal item, receive offers from other users, and confirm trades that advance the campaign's current item toward the goal. Inspired by Kyle MacDonald's barter experiment.

## Commands

```bash
composer dev                # Start server + queue + Pail logs + Vite (all-in-one)
composer dev:ddev           # Same but no PHP server (use with DDEV; DDEV serves the app)
composer test               # Pint lint check + Pest tests
php artisan test --compact  # Run all tests
php artisan test --compact --filter=CampaignControllerTest  # Run specific test file
php artisan test --compact --filter="test campaign show"     # Run single test by name
vendor/bin/pint --dirty --format agent   # Format only changed PHP files
npm run lint                # ESLint fix
npm run format              # Prettier format resources/
npm run build               # Vite production build
php artisan wayfinder:generate  # Regenerate TypeScript route helpers after route changes
```

DDEV is available but optional — prefix commands with `ddev exec` if using it.

## Architecture

### Domain Model (the trade-up flow)

```
User creates Campaign (with start Item + goal Item)
  → Other users submit Offers (each has an offered Item targeting a campaign Item)
  → Campaign owner accepts/declines Offers (via OfferPolicy)
  → Accepted Offer creates a Trade (status: PendingConfirmation)
  → Both parties confirm the Trade (dual-confirmation via TradePolicy)
  → Completed Trade advances Campaign.current_item_id to the offered Item
```

### Key Models and Relationships

- **Campaign** — central entity. Has `status` (CampaignStatus enum), `visibility` (CampaignVisibility enum), belongs to User and Category. References `current_item_id` and `goal_item_id` on the items table.
- **Item** — polymorphic (`itemable_type`/`itemable_id`). Role enum: Start, Goal, Offered. Attached to campaigns or offers.
- **Offer** — links a user's offered Item to a campaign's current Item. Status enum: Pending, Accepted, Declined, Withdrawn, Expired.
- **Trade** — created from an accepted Offer. Dual-confirmation design (`confirmed_by_offerer_at`, `confirmed_by_owner_at`). Status enum: PendingConfirmation, Completed, Disputed. Unique constraint on `(campaign_id, position)`.
- **Comment** — polymorphic, supports nested replies via `parent_id`.
- **Follow** — polymorphic followable.
- **Media** — custom polymorphic media model (not Spatie).
- **Notification** — custom model (not Laravel's built-in notification system).

### Action Classes (`app/Actions/`)

Business logic lives in Action classes, not controllers:
- `CreateCampaign` — creates campaign + start/goal items, links item IDs
- `UpdateCampaign` — updates campaign details
- `CreateOffer` — creates offer with offered item targeting a campaign item
- `AcceptOffer` — creates Trade, marks offer accepted, sends notification
- `DeclineOffer` — marks offer declined, sends notification
- `ConfirmTrade` — records confirmation timestamp; when both confirm, completes trade and advances campaign

Controllers delegate to these actions. Follow this pattern for new business logic.

### Enums (`app/Enums/`)

All status fields use backed string enums: `CampaignStatus`, `CampaignVisibility`, `ItemRole`, `OfferStatus`, `TradeStatus`. Use these enums (not raw strings) in code.

### Authorization

- `OfferPolicy` — accept/decline: caller must be campaign owner, offer must be Pending
- `TradePolicy` — confirm: caller must be offerer or campaign owner

### Frontend

- **UI library:** shadcn-vue (new-york-v4 style, reka-ui primitives, lucide icons)
- **Design:** "Swap Shop" aesthetic — vibrant marketplace feel (Depop meets Duolingo), warm cream backgrounds, fun accent colors
- **Fonts:** DM Sans (body), Fredoka (display headings), JetBrains Mono (stats/data) — loaded via Bunny Fonts
- **Design tokens:** CSS variables in `resources/css/app.css` — `--brand-red`, `--electric-mint`, `--sunny-yellow`, `--hot-coral`, `--soft-lavender`, `--sky-blue`, `--paper`, `--ink`
- **Layouts:**
  - `PublicLayout` — public pages (Welcome, campaign browsing): sticky header, decorative blobs, mobile bottom tab bar, footer
  - `AppLayout` (wraps `AppSidebarLayout`) — authenticated pages: sidebar on desktop, bottom tab bar on mobile
  - `AdminLayout` — admin/dashboard pages (NorthCloud articles, users)
  - `AuthLayout` — auth pages: centered card with accent blobs
  - Settings has its own `Layout`
- **Custom components:**
  - `CampaignCard` — reusable card with category accent strip, progress ring, trade count badge
  - `ProgressRing` — SVG circular progress indicator, color changes by completion (coral/yellow/mint)
  - `MilestoneTimeline` — horizontal trade timeline with completed/current/future nodes
  - `BottomTabBar` — mobile navigation (Home, Explore, Create[elevated], Activity, Profile)
- **Button variants:** default, brand, destructive, outline, secondary, ghost, link, success (mint), social (sky blue)
- **Pages:** `resources/js/pages/` — Inertia Vue components
- **Wayfinder:** TypeScript route helpers generated at `resources/js/actions/` and `resources/js/routes/`. Import routes from `@/actions/` (controllers) or `@/routes/` (named routes).
- **ESLint enforces:** alphabetical imports, `import type` for type-only imports, prettier compatibility

### JSON API (`routes/web.php` → `/api/*`)

Parallel JSON API under `api.*` route names using `Api\CampaignApiController`, `Api\OfferApiController`, `Api\TradeApiController`. Same session auth as web — intended for WebMCP / agent access, not a separate Sanctum-based API.

### Middleware

Configured in `bootstrap/app.php`:
- `HandleAppearance` — dark/light theme from `appearance` cookie
- `HandleInertiaRequests` — shares `auth.user` and `sidebarOpen` (from `sidebar_state` cookie)

### Auth

Fortify headless with Inertia views. Features: registration, password reset, email verification, 2FA (TOTP with confirmation). Rate limiters: 5/min for login and 2FA. Views registered in `FortifyServiceProvider`.

### Database

MariaDB. Default queue connection is `database`. Categories seeded with 9 predefined values. `CampaignSeeder` creates sample campaigns using the `CreateCampaign` action.

### Validation

Form Requests use **array-style** rules (not pipe-delimited strings). Check `StoreCampaignRequest` for the pattern.

### NorthCloud (`jonesrussell/northcloud-laravel`)

- **Admin:** Dashboard Articles and Users (CRUD) at `/dashboard/articles` and `/dashboard/users`; protected by `northcloud-admin` middleware (requires `User.is_admin` or custom policy). Sidebar shows "Admin" group with Articles/Users when `page.props.northcloud.navigation` is present.
- **Config:** `config/northcloud.php` — Redis channels, models, navigation, SendGrid (optional). Env: `NORTHCLOUD_CHANNELS`, `NORTHCLOUD_REDIS_*`, `NORTHCLOUD_SENDGRID_AS_DEFAULT`, etc. (see `.env.example`).
- **Article feed (optional):** To ingest from North Cloud Redis pipeline, set Redis connection (e.g. `NORTHCLOUD_REDIS_HOST`) and run `php artisan articles:subscribe` (long-running). Production: optional systemd user service `oneredpaperclip-northcloud-subscribe.service`; enable with `systemctl --user enable oneredpaperclip-northcloud-subscribe.service` when using the feed.
- **First admin:** Set `is_admin = 1` for a user in the DB (or run a one-off tinker/seed) so someone can access the dashboard.

## Known Gaps

These are referenced in code but don't exist yet:
- `ItemMediaController` (referenced in `routes/web.php` and Wayfinder)
- No model factories for Campaign, Item, Offer, Trade (only `UserFactory` exists; tests use `Model::create()` directly)

## Design Documentation

- `docs/plans/2026-02-20-swap-shop-redesign-design.md` — Swap Shop design system (colors, typography, components, page layouts)
- `docs/plans/2026-02-20-swap-shop-implementation.md` — implementation plan for the redesign
- `docs/plans/2025-02-18-tradeup-design.md` — original TradeUp product architecture and data model
- `docs/plans/2025-02-18-tradeup-mvp-implementation.md` — original MVP implementation plan

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.1
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA) - v2
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `wayfinder-development` — Activates whenever referencing backend routes in frontend components. Use when importing from @/actions or @/routes, calling Laravel routes from TypeScript, or working with Wayfinder route functions.
- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `inertia-vue-development` — Develops Inertia.js v2 Vue client-side applications. Activates when creating Vue pages, forms, or navigation; using &lt;Link&gt;, &lt;Form&gt;, useForm, or router; working with deferred props, prefetching, or polling; or when user mentions Vue with Inertia, Vue pages, Vue forms, or Vue navigation.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.
- `developing-with-fortify` — Laravel Fortify headless authentication backend development. Activate when implementing authentication features including login, registration, password reset, email verification, two-factor authentication (2FA/TOTP), profile updates, headless auth, authentication scaffolding, or auth guards in Laravel applications.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

=== inertia-laravel/v2 rules ===

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scrolling (merging props + `WhenVisible`), lazy loading on scroll, polling, prefetching.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== wayfinder/core rules ===

# Laravel Wayfinder

Wayfinder generates TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

- IMPORTANT: Activate `wayfinder-development` skill whenever referencing backend routes in frontend components.
- Invokable Controllers: `import StorePost from '@/actions/.../StorePostController'; StorePost()`.
- Parameter Binding: Detects route keys (`{post:slug}`) — `show({ slug: "my-post" })`.
- Query Merging: `show(1, { mergeQuery: { page: 2, sort: null } })` merges with current URL, `null` removes params.
- Inertia: Use `.form()` with `<Form>` component or `form.submit(store())` with useForm.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.

=== laravel/fortify rules ===

# Laravel Fortify

- Fortify is a headless authentication backend that provides authentication routes and controllers for Laravel applications.
- IMPORTANT: Always use the `search-docs` tool for detailed Laravel Fortify patterns and documentation.
- IMPORTANT: Activate `developing-with-fortify` skill when working with Fortify authentication features.

</laravel-boost-guidelines>
