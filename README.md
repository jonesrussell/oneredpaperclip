# One Red Paperclip

A Laravel application for running trade-up campaigns: create campaigns, list items, receive and manage offers, and confirm trades. Inspired by the “one red paperclip” barter experiment.

## Stack

- **Backend:** Laravel 12, PHP 8.4
- **Frontend:** Inertia.js v2, Vue 3, TypeScript, Tailwind CSS v4
- **Auth:** Laravel Fortify (login, registration, 2FA, profile)
- **Routes:** Laravel Wayfinder (type-safe route helpers)
- **Testing:** Pest v4
- **Local dev:** DDEV (optional) or `composer dev`

## Requirements

- PHP 8.2+
- Composer, Node.js, npm
- SQLite (default) or MySQL/MariaDB
- For DDEV: [DDEV](https://ddev.com/) installed

## Setup

### With DDEV (recommended)

```bash
ddev start
ddev composer install
cp .env.example .env
ddev artisan key:generate
ddev artisan migrate
ddev npm install
ddev npm run build
```

App: **https://oneredpaperclip.ddev.site** (or the URL DDEV prints).

To run the full dev stack (server, queue, logs, Vite) inside DDEV:

```bash
ddev exec composer dev
```

### Without DDEV

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

For local development with hot reload:

```bash
composer dev
```

This starts the PHP server, queue worker, Pail logs, and Vite.

## Commands

| Command | Description |
|--------|-------------|
| `composer dev` | Start server, queue, Pail, and Vite (or `ddev exec composer dev`) |
| `composer dev:ssr` | Same with Inertia SSR |
| `composer test` | Run Pint lint + Pest tests |
| `composer lint` | Format PHP with Pint |
| `php artisan test --compact` | Run tests only |
| `php artisan wayfinder:generate` | Regenerate route helpers after route changes |
| `npm run dev` / `npm run build` | Vite dev server / production build |
| `npm run lint` / `npm run format` | ESLint / Prettier |

## Project structure

- **Routes:** `routes/web.php` (campaigns, offers, trades), `routes/settings.php` (Fortify profile/settings)
- **Pages:** `resources/js/pages/` (Inertia/Vue: Welcome, Dashboard, campaigns, auth, settings)
- **Auth:** Fortify headless (login, register, 2FA, password reset, profile) — no Blade auth views

## Environment

Default `.env` uses SQLite and `QUEUE_CONNECTION=database`. For DDEV, point `DB_*` to the DDEV database if not using SQLite. Run the queue worker (via `composer dev` or `php artisan queue:listen`) when using queues.

## License

MIT
