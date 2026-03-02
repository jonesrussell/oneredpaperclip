# Challenge Slug URLs

## Problem

Challenge URLs use numeric IDs (`/challenges/1`) which are not SEO-friendly. They should use slugs (`/challenges/red-paperclip-to-house`).

## Design

### Migration

Add a nullable `slug` column with a unique index to the challenges table. Backfill existing rows by generating slugs from their titles, with numeric suffixes for duplicates.

### Model (`Challenge.php`)

- Add `slug` to `$fillable`
- Override `getRouteKeyName()` to return `'slug'` — all implicit route model binding switches to slug automatically
- Auto-generate slug from title in `booted()` using `Str::slug()`, same pattern as Category model
- Handle uniqueness: if `my-challenge` exists, try `my-challenge-2`, `my-challenge-3`, etc.
- Fallback: if title is null/empty, generate a random slug

### Factory

Add `'slug' => fake()->unique()->slug(3)` to `ChallengeFactory::definition()`.

### Seeder

No changes — `ChallengeSeeder` uses the `CreateChallenge` action which creates challenges via the model, so `booted()` auto-generates slugs.

### Frontend

After `php artisan wayfinder:generate`, Wayfinder will detect the route key change and generate helpers that accept slug strings. Update frontend references that pass `challenge.id` to route helpers to pass `challenge.slug` or the challenge object.

### Old URLs

Numeric ID URLs will 404. No redirect support needed.

### Tests

Update tests that reference challenges by ID in URLs to use slugs. The `route()` helper with a Challenge model instance will automatically use the slug via `getRouteKeyName()`.
