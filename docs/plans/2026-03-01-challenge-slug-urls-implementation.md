# Challenge Slug URLs Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace numeric IDs in challenge URLs with SEO-friendly slugs (e.g. `/challenges/red-paperclip-to-house`).

**Architecture:** Add a `slug` column to the challenges table, auto-generate slugs from titles in the model's `booted()` method (same pattern as Category), override `getRouteKeyName()` so all implicit route model binding uses slug. Regenerate Wayfinder and update frontend references.

**Tech Stack:** Laravel 12, Eloquent, Pest, Inertia.js v2, Wayfinder, Vue 3

---

### Task 1: Add slug column migration

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_add_slug_to_challenges_table.php`

**Step 1: Create the migration**

Run: `php artisan make:migration add_slug_to_challenges_table --no-interaction`

**Step 2: Write the migration**

```php
public function up(): void
{
    Schema::table('challenges', function (Blueprint $table) {
        $table->string('slug')->nullable()->unique()->after('title');
    });

    // Backfill existing challenges with slugs
    $challenges = \App\Models\Challenge::withTrashed()->whereNull('slug')->get();
    foreach ($challenges as $challenge) {
        $baseSlug = \Illuminate\Support\Str::slug($challenge->title ?? 'challenge');
        if (empty($baseSlug)) {
            $baseSlug = 'challenge';
        }
        $slug = $baseSlug;
        $suffix = 2;
        while (\App\Models\Challenge::withTrashed()->where('slug', $slug)->where('id', '!=', $challenge->id)->exists()) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }
        $challenge->update(['slug' => $slug]);
    }

    // Now make it non-nullable
    Schema::table('challenges', function (Blueprint $table) {
        $table->string('slug')->nullable(false)->change();
    });
}

public function down(): void
{
    Schema::table('challenges', function (Blueprint $table) {
        $table->dropColumn('slug');
    });
}
```

**Step 3: Run the migration**

Run: `php artisan migrate`
Expected: Migration runs successfully

**Step 4: Commit**

```
git add database/migrations/*add_slug_to_challenges_table*
git commit -m "feat: add slug column to challenges table with backfill"
```

---

### Task 2: Add slug support to Challenge model

**Files:**
- Modify: `app/Models/Challenge.php`

**Step 1: Write a test for slug auto-generation**

Add to `tests/Feature/ChallengeControllerTest.php`:

```php
test('challenge slug is auto-generated from title', function () {
    $challenge = Challenge::factory()->for($this->user)->create(['title' => 'Red Paperclip to House']);

    expect($challenge->slug)->toBe('red-paperclip-to-house');
});

test('challenge slug handles duplicates with suffix', function () {
    Challenge::factory()->for($this->user)->create(['title' => 'My Challenge']);
    $second = Challenge::factory()->for($this->user)->create(['title' => 'My Challenge']);

    expect($second->slug)->toBe('my-challenge-2');
});

test('challenge slug is generated when title is null', function () {
    $challenge = Challenge::factory()->for($this->user)->create(['title' => null]);

    expect($challenge->slug)->not->toBeNull();
    expect($challenge->slug)->not->toBeEmpty();
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="challenge slug"`
Expected: FAIL — slug column exists from Task 1 but no auto-generation logic yet

**Step 3: Implement slug support in Challenge model**

In `app/Models/Challenge.php`:

1. Add `use Illuminate\Support\Str;` at top
2. Add `'slug'` to `$fillable` array
3. Add `getRouteKeyName()` method
4. Add `booted()` method with slug generation

```php
public function getRouteKeyName(): string
{
    return 'slug';
}

protected static function booted(): void
{
    static::saving(function (Challenge $challenge) {
        if (empty($challenge->slug)) {
            $baseSlug = Str::slug($challenge->title ?? '');
            if (empty($baseSlug)) {
                $baseSlug = 'challenge-' . Str::random(8);
            }
            $slug = $baseSlug;
            $suffix = 2;
            while (static::withTrashed()->where('slug', $slug)->where('id', '!=', $challenge->id ?? 0)->exists()) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }
            $challenge->slug = $slug;
        }
    });
}
```

**Step 4: Update ChallengeFactory**

In `database/factories/ChallengeFactory.php`, add `slug` to `definition()`:

```php
'slug' => fake()->unique()->slug(3, false),
```

This must go in the `definition()` return array. The `slug()` Faker method doesn't exist — use this instead:

```php
'slug' => \Illuminate\Support\Str::slug(fake()->unique()->sentence(3)),
```

**Step 5: Run slug tests**

Run: `php artisan test --compact --filter="challenge slug"`
Expected: 3 PASS

**Step 6: Run full challenge tests**

Run: `php artisan test --compact --filter=ChallengeControllerTest`
Expected: Some tests may fail because `route('challenges.show', $challenge)` now generates slug URLs, and tests that create challenges via `Challenge::create()` (without the model `booted()` event being fired for slug) may have issues. Note these failures — they'll be fixed in Task 3.

**Step 7: Commit**

```
git add app/Models/Challenge.php database/factories/ChallengeFactory.php tests/Feature/ChallengeControllerTest.php
git commit -m "feat: add slug auto-generation and route key to Challenge model"
```

---

### Task 3: Fix existing tests for slug routing

**Files:**
- Modify: `tests/Feature/ChallengeControllerTest.php`
- Modify: `tests/Feature/AdminChallengeControllerTest.php`
- Modify: `tests/Feature/Api/ChallengeApiTest.php`
- Modify: `tests/Feature/SitemapTest.php`

**Step 1: Identify failing tests**

Run: `php artisan test --compact`
Note all failures. The main issue is that tests using `Challenge::create()` directly (instead of factories) don't trigger the `saving` event properly for slug generation, AND `route()` calls now expect slugs.

For tests using `Challenge::create()` directly: add a `'slug'` key to the create data, or switch to factories.

For any tests referencing `challenge.id` in URL assertions: the URLs now use slugs.

**Step 2: Fix each failing test**

For `Challenge::create()` calls, add a slug field:
```php
$challenge = Challenge::create([
    // ...existing fields...
    'slug' => 'my-challenge',
]);
```

Or better, switch to using factories which will auto-generate slugs:
```php
$challenge = Challenge::factory()->for($user)->for($category)->create([
    'title' => 'My challenge',
    'status' => 'active',
    'visibility' => 'public',
]);
```

**Step 3: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests PASS

**Step 4: Commit**

```
git add tests/
git commit -m "test: fix tests for slug-based challenge routing"
```

---

### Task 4: Regenerate Wayfinder and update frontend

**Files:**
- Modify: `resources/js/routes/challenges/index.ts` (auto-generated)
- Modify: `resources/js/actions/App/Http/Controllers/ChallengeController.ts` (auto-generated)
- Modify: `resources/js/components/ChallengeCard.vue`
- Modify: `resources/js/pages/challenges/Show.vue`
- Modify: `resources/js/pages/challenges/Edit.vue`
- Modify: `resources/js/pages/Welcome.vue`
- Modify: `resources/js/components/admin/ChallengesTable.vue`
- Modify: `resources/js/pages/dashboard/admin/challenges/Show.vue`
- Modify: `resources/js/pages/dashboard/admin/challenges/Index.vue`
- Modify: `resources/js/pages/dashboard/admin/challenges/Trashed.vue`

**Step 1: Regenerate Wayfinder**

Run: `php artisan wayfinder:generate`

After regeneration, check what Wayfinder now expects for the `challenge` parameter in `resources/js/routes/challenges/index.ts`. With `getRouteKeyName()` returning `'slug'`, Wayfinder should generate helpers that accept `{challenge: string}` (slug) or an object with a `slug` property.

**Step 2: Update ChallengeCard**

In `resources/js/components/ChallengeCard.vue`, the challenge type needs a `slug` field and the route call needs updating:

Add `slug: string;` to the challenge type definition.

Change:
```vue
:href="challenges.show({ challenge: challenge.id }).url"
```
To:
```vue
:href="challenges.show({ challenge: challenge.slug }).url"
```

**Step 3: Update Show.vue**

In `resources/js/pages/challenges/Show.vue`:

Add `slug?: string;` to the Challenge type.

The share URL (line 72):
```ts
return `${window.location.origin}/challenges/${props.challenge.id}`;
```
Change to:
```ts
return `${window.location.origin}/challenges/${props.challenge.slug}`;
```

The edit link (line 299-301) passes `challenge.id` — change to `challenge.slug` (or let Wayfinder resolve from the object if it supports that).

**Step 4: Update Edit.vue**

In `resources/js/pages/challenges/Edit.vue`:

Add `slug?: string;` to the challenge type (check existing type).

Update hardcoded breadcrumb URLs (lines 61-63):
```ts
{ title: 'Challenge', href: `/challenges/${props.challenge.slug}` },
{ title: 'Edit', href: `/challenges/${props.challenge.slug}/edit` },
```

Update the route helper calls:
```ts
router.visit(challenges.show.url({ challenge: props.challenge.slug }));
```
```vue
:action="challenges.update.url({ challenge: challenge.slug })"
```

**Step 5: Update Welcome.vue**

Check `resources/js/pages/Welcome.vue` line 353 — if it passes `challenge.id` to any route helper, change to `challenge.slug`. The `:key="challenge.id"` in v-for is fine (it's not a URL).

**Step 6: Update admin pages**

Admin pages (`ChallengesTable.vue`, admin `Show.vue`, admin `Index.vue`, `Trashed.vue`) use hardcoded URL patterns like `` `${routePrefix}/${challenge.id}/unpublish` ``. These need updating to use `challenge.slug`:

- `resources/js/components/admin/ChallengesTable.vue` — `showUrl(challenge.id)` → `showUrl(challenge.slug)`
- `resources/js/pages/dashboard/admin/challenges/Show.vue` — `${routePrefix}/${props.challenge.id}/unpublish` → use slug
- `resources/js/pages/dashboard/admin/challenges/Index.vue` — similar pattern
- `resources/js/pages/dashboard/admin/challenges/Trashed.vue` — `${routePrefix}/${challenge.id}/restore` → use slug

**Important:** Admin routes also use `{challenge}` binding, so they'll resolve by slug too. But admin pages display `challenge.id` in the table — that's fine, IDs are still useful for admin display.

**Step 7: Run linters**

Run: `npm run lint && npm run format`

**Step 8: Build and test**

Run: `npm run build`
Run: `php artisan test --compact`
Expected: All pass

**Step 9: Commit**

```
git add resources/js/
git commit -m "feat: update frontend to use challenge slugs in URLs"
```

---

### Task 5: Update API controllers and remaining references

**Files:**
- Check: `app/Http/Controllers/Api/ChallengeApiController.php`
- Check: `app/Http/Controllers/Dashboard/ChallengeController.php`

**Step 1: Verify controllers work with slug binding**

Since all controllers use implicit route model binding (`Challenge $challenge`), and `getRouteKeyName()` now returns `'slug'`, they should work automatically. No code changes needed in controllers.

Verify by running: `php artisan test --compact`

**Step 2: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 3: Final full test suite**

Run: `php artisan test --compact`
Expected: All 209+ tests PASS

**Step 4: Commit if needed**

Only if Pint made changes.
