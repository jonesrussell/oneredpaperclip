# One Red Paperclip

A trade-up platform where users create campaigns with a start item and goal item, receive offers from other users, and confirm trades that advance the campaign toward the goal. Inspired by Kyle MacDonald's “one red paperclip” barter experiment.

## Stack

- **Backend:** Laravel 12, PHP 8.4, MariaDB
- **Frontend:** Inertia.js v2, Vue 3, TypeScript, Tailwind CSS v4
- **UI:** shadcn-vue (new-york-v4 style), Lucide icons
- **Auth:** Laravel Fortify (login, registration, 2FA, profile)
- **Routes:** Laravel Wayfinder (type-safe route helpers)
- **Testing:** Pest v4
- **Local dev:** DDEV (optional) or `composer dev`

## Design

“Swap Shop” aesthetic — a vibrant trading marketplace inspired by Depop and Duolingo, targeting young adults (18–35).

- **Fonts:** DM Sans (body), Fredoka (display headings), JetBrains Mono (stats/data)
- **Colors:** Warm cream backgrounds with brand red, electric mint, sunny yellow, hot coral, sky blue accents
- **Mobile-first:** Bottom tab bar on mobile, sidebar on desktop
- **Gamification:** Circular progress rings, milestone timelines, campaign cards with category accent strips

## Requirements

- PHP 8.4+
- Composer, Node.js, npm
- MariaDB (or MySQL)
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
- **Actions:** `app/Actions/` — business logic (CreateCampaign, AcceptOffer, DeclineOffer, ConfirmTrade)
- **Layouts:**
  - `PublicLayout` — public pages (Welcome, campaign browsing): sticky header, bottom tab bar, decorative blobs
  - `AppLayout` — authenticated pages: sidebar on desktop, bottom tab bar on mobile
  - `AuthLayout` — auth pages: centered card
- **Pages:** `resources/js/pages/` (Welcome, Dashboard, campaigns/Index/Create/Show, auth, settings)
- **Components:** `resources/js/components/` (CampaignCard, ProgressRing, MilestoneTimeline, BottomTabBar, shadcn-vue primitives)
- **Auth:** Fortify headless (login, register, 2FA, password reset, profile) — no Blade auth views

## Environment

Default `QUEUE_CONNECTION=database`. MariaDB for the database. Run the queue worker (via `composer dev` or `php artisan queue:listen`) when using queues.

## License

MIT
