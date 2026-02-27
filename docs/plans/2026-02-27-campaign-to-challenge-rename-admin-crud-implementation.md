# Campaign to Challenge Rename + Admin CRUD — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Rename "Campaign" to "Challenge" throughout the codebase and add admin CRUD for challenges in the dashboard.

**Architecture:** Two-phase approach: (1) Database migration and backend rename, (2) Admin dashboard feature. Each phase has tests to verify correctness before proceeding.

**Tech Stack:** Laravel 12, Inertia.js v2, Vue 3, TypeScript, shadcn-vue, Pest 4

---

## Phase 1: Rename Campaign → Challenge

### Task 1: Create Database Migration

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_rename_campaigns_to_challenges.php`

**Step 1: Generate migration**

```bash
php artisan make:migration rename_campaigns_to_challenges --no-interaction
```

**Step 2: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename table
        Schema::rename('campaigns', 'challenges');

        // Add soft deletes
        Schema::table('challenges', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Rename foreign key columns
        Schema::table('offers', function (Blueprint $table) {
            $table->renameColumn('campaign_id', 'challenge_id');
            $table->renameColumn('for_campaign_item_id', 'for_challenge_item_id');
        });

        Schema::table('trades', function (Blueprint $table) {
            $table->renameColumn('campaign_id', 'challenge_id');
        });

        // Update polymorphic types
        DB::table('items')
            ->where('itemable_type', 'App\\Models\\Campaign')
            ->update(['itemable_type' => 'App\\Models\\Challenge']);

        DB::table('comments')
            ->where('commentable_type', 'App\\Models\\Campaign')
            ->update(['commentable_type' => 'App\\Models\\Challenge']);
    }

    public function down(): void
    {
        // Revert polymorphic types
        DB::table('items')
            ->where('itemable_type', 'App\\Models\\Challenge')
            ->update(['itemable_type' => 'App\\Models\\Campaign']);

        DB::table('comments')
            ->where('commentable_type', 'App\\Models\\Challenge')
            ->update(['commentable_type' => 'App\\Models\\Campaign']);

        // Revert foreign key columns
        Schema::table('trades', function (Blueprint $table) {
            $table->renameColumn('challenge_id', 'campaign_id');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->renameColumn('challenge_id', 'campaign_id');
            $table->renameColumn('for_challenge_item_id', 'for_campaign_item_id');
        });

        // Remove soft deletes
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Rename table back
        Schema::rename('challenges', 'campaigns');
    }
};
```

**Step 3: Run migration**

```bash
php artisan migrate
```

Expected: Migration completes successfully.

**Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: add migration to rename campaigns to challenges"
```

---

### Task 2: Rename Enums

**Files:**
- Rename: `app/Enums/CampaignStatus.php` → `app/Enums/ChallengeStatus.php`
- Rename: `app/Enums/CampaignVisibility.php` → `app/Enums/ChallengeVisibility.php`

**Step 1: Rename CampaignStatus**

```bash
mv app/Enums/CampaignStatus.php app/Enums/ChallengeStatus.php
```

**Step 2: Update ChallengeStatus content**

```php
<?php

namespace App\Enums;

enum ChallengeStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Paused = 'paused';
}
```

**Step 3: Rename CampaignVisibility**

```bash
mv app/Enums/CampaignVisibility.php app/Enums/ChallengeVisibility.php
```

**Step 4: Update ChallengeVisibility content**

```php
<?php

namespace App\Enums;

enum ChallengeVisibility: string
{
    case Public = 'public';
    case Unlisted = 'unlisted';
}
```

**Step 5: Commit**

```bash
git add app/Enums/
git commit -m "refactor: rename Campaign enums to Challenge"
```

---

### Task 3: Rename Model

**Files:**
- Rename: `app/Models/Campaign.php` → `app/Models/Challenge.php`
- Modify: `app/Models/Offer.php`
- Modify: `app/Models/Trade.php`
- Modify: `app/Models/User.php` (if has campaigns relationship)

**Step 1: Rename Campaign model file**

```bash
mv app/Models/Campaign.php app/Models/Challenge.php
```

**Step 2: Update Challenge model**

```php
<?php

namespace App\Models;

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Challenge extends Model
{
    /** @use HasFactory<\Database\Factories\ChallengeFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'status',
        'visibility',
        'title',
        'story',
        'current_item_id',
        'goal_item_id',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Item::class, 'itemable');
    }

    public function currentItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Item::class, 'current_item_id');
    }

    public function goalItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Item::class, 'goal_item_id');
    }

    public function startItem(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Item::class, 'itemable')->where('role', 'start');
    }

    public function offers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function trades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    protected function casts(): array
    {
        return [
            'status' => ChallengeStatus::class,
            'visibility' => ChallengeVisibility::class,
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ChallengeStatus::Active);
    }

    public function scopeNotDraft(Builder $query): Builder
    {
        return $query->where('status', '!=', ChallengeStatus::Draft);
    }

    public function scopePublicVisibility(Builder $query): Builder
    {
        return $query->where('visibility', ChallengeVisibility::Public);
    }
}
```

**Step 3: Update Offer model**

Replace `campaign_id` with `challenge_id`, `for_campaign_item_id` with `for_challenge_item_id`, and `campaign()` relationship with `challenge()`:

```php
protected $fillable = [
    'challenge_id',
    'from_user_id',
    'offered_item_id',
    'for_challenge_item_id',
    'message',
    'status',
    'expires_at',
];

public function challenge(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(Challenge::class);
}

public function forChallengeItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(Item::class, 'for_challenge_item_id');
}
```

**Step 4: Update Trade model**

Replace `campaign_id` with `challenge_id` and `campaign()` with `challenge()`:

```php
protected $fillable = [
    'challenge_id',
    'offer_id',
    'position',
    'offered_item_id',
    'received_item_id',
    'status',
    'confirmed_by_offerer_at',
    'confirmed_by_owner_at',
];

public function challenge(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(Challenge::class);
}
```

**Step 5: Update User model** (if campaigns relationship exists)

Search for `campaigns()` relationship and rename to `challenges()`.

**Step 6: Commit**

```bash
git add app/Models/
git commit -m "refactor: rename Campaign model to Challenge"
```

---

### Task 4: Rename Actions

**Files:**
- Rename: `app/Actions/CreateCampaign.php` → `app/Actions/CreateChallenge.php`
- Rename: `app/Actions/UpdateCampaign.php` → `app/Actions/UpdateChallenge.php`
- Rename: `app/Actions/SuggestCampaignText.php` → `app/Actions/SuggestChallengeText.php`

**Step 1: Rename and update CreateChallenge**

```bash
mv app/Actions/CreateCampaign.php app/Actions/CreateChallenge.php
```

Update class name to `CreateChallenge`, update imports from `Campaign` to `Challenge`, and update all references.

**Step 2: Rename and update UpdateChallenge**

```bash
mv app/Actions/UpdateCampaign.php app/Actions/UpdateChallenge.php
```

Update class name to `UpdateChallenge`, update imports and references.

**Step 3: Rename and update SuggestChallengeText**

```bash
mv app/Actions/SuggestCampaignText.php app/Actions/SuggestChallengeText.php
```

Update class name to `SuggestChallengeText`, update imports and references.

**Step 4: Commit**

```bash
git add app/Actions/
git commit -m "refactor: rename Campaign actions to Challenge"
```

---

### Task 5: Rename Form Requests

**Files:**
- Rename: `app/Http/Requests/StoreCampaignRequest.php` → `app/Http/Requests/StoreChallengeRequest.php`
- Rename: `app/Http/Requests/UpdateCampaignRequest.php` → `app/Http/Requests/UpdateChallengeRequest.php`
- Rename: `app/Http/Requests/CampaignAiSuggestRequest.php` → `app/Http/Requests/ChallengeAiSuggestRequest.php`

**Step 1: Rename each file**

```bash
mv app/Http/Requests/StoreCampaignRequest.php app/Http/Requests/StoreChallengeRequest.php
mv app/Http/Requests/UpdateCampaignRequest.php app/Http/Requests/UpdateChallengeRequest.php
mv app/Http/Requests/CampaignAiSuggestRequest.php app/Http/Requests/ChallengeAiSuggestRequest.php
```

**Step 2: Update class names and imports in each file**

**Step 3: Commit**

```bash
git add app/Http/Requests/
git commit -m "refactor: rename Campaign form requests to Challenge"
```

---

### Task 6: Rename Controllers

**Files:**
- Rename: `app/Http/Controllers/CampaignController.php` → `app/Http/Controllers/ChallengeController.php`
- Rename: `app/Http/Controllers/Api/CampaignApiController.php` → `app/Http/Controllers/Api/ChallengeApiController.php`

**Step 1: Rename each file**

```bash
mv app/Http/Controllers/CampaignController.php app/Http/Controllers/ChallengeController.php
mv app/Http/Controllers/Api/CampaignApiController.php app/Http/Controllers/Api/ChallengeApiController.php
```

**Step 2: Update class names, imports, and all Campaign references to Challenge**

**Step 3: Commit**

```bash
git add app/Http/Controllers/
git commit -m "refactor: rename Campaign controllers to Challenge"
```

---

### Task 7: Update Routes

**Files:**
- Modify: `routes/web.php`

**Step 1: Update all route definitions**

Replace:
- `CampaignController` → `ChallengeController`
- `CampaignApiController` → `ChallengeApiController`
- `campaigns` (URL paths) → `challenges`
- `campaigns.*` (route names) → `challenges.*`
- Route model binding `{campaign}` → `{challenge}`

**Step 2: Run route list to verify**

```bash
php artisan route:list --name=challenges
```

Expected: All challenge routes listed.

**Step 3: Commit**

```bash
git add routes/
git commit -m "refactor: rename campaign routes to challenges"
```

---

### Task 8: Rename Factory and Seeder

**Files:**
- Rename: `database/factories/CampaignFactory.php` → `database/factories/ChallengeFactory.php`
- Rename: `database/seeders/CampaignSeeder.php` → `database/seeders/ChallengeSeeder.php`

**Step 1: Rename factory**

```bash
mv database/factories/CampaignFactory.php database/factories/ChallengeFactory.php
```

Update class name to `ChallengeFactory`, update model reference to `Challenge::class`.

**Step 2: Rename seeder**

```bash
mv database/seeders/CampaignSeeder.php database/seeders/ChallengeSeeder.php
```

Update class name to `ChallengeSeeder`, update all Campaign references.

**Step 3: Update DatabaseSeeder if it references CampaignSeeder**

**Step 4: Commit**

```bash
git add database/factories/ database/seeders/
git commit -m "refactor: rename Campaign factory and seeder to Challenge"
```

---

### Task 9: Update Frontend (Vue pages and Wayfinder)

**Files:**
- Modify: `resources/js/pages/challenges/*.vue` (update prop names)
- Modify: Any components importing from `@/actions/CampaignController`
- Modify: `routes/web.php` Welcome route (featuredCampaigns → featuredChallenges)

**Step 1: Regenerate Wayfinder**

```bash
php artisan wayfinder:generate
```

**Step 2: Update Vue pages**

In each Vue file under `resources/js/pages/challenges/`:
- Update prop names from `campaign` to `challenge`
- Update Wayfinder imports from `CampaignController` to `ChallengeController`

**Step 3: Update Welcome.vue**

Update `featuredCampaigns` prop to `featuredChallenges`.

**Step 4: Run lint and format**

```bash
npm run lint
npm run format
```

**Step 5: Commit**

```bash
git add resources/js/
git commit -m "refactor: update Vue pages for Challenge rename"
```

---

### Task 10: Rename Tests

**Files:**
- Rename: `tests/Feature/CampaignControllerTest.php` → `tests/Feature/ChallengeControllerTest.php`
- Rename: `tests/Feature/CampaignAiSuggestTest.php` → `tests/Feature/ChallengeAiSuggestTest.php`
- Rename: `tests/Feature/CampaignTest.php` → `tests/Feature/ChallengeTest.php`
- Rename: `tests/Feature/CreateCampaignTest.php` → `tests/Feature/CreateChallengeTest.php`
- Rename: `tests/Feature/Api/CampaignApiTest.php` → `tests/Feature/Api/ChallengeApiTest.php`

**Step 1: Rename each test file**

```bash
mv tests/Feature/CampaignControllerTest.php tests/Feature/ChallengeControllerTest.php
mv tests/Feature/CampaignAiSuggestTest.php tests/Feature/ChallengeAiSuggestTest.php
mv tests/Feature/CampaignTest.php tests/Feature/ChallengeTest.php
mv tests/Feature/CreateCampaignTest.php tests/Feature/CreateChallengeTest.php
mv tests/Feature/Api/CampaignApiTest.php tests/Feature/Api/ChallengeApiTest.php
```

**Step 2: Update all Campaign references to Challenge in each test file**

Update:
- Model imports
- Factory usage
- Route names
- Variable names

**Step 3: Run tests**

```bash
php artisan test --compact
```

Expected: All tests pass.

**Step 4: Commit**

```bash
git add tests/
git commit -m "refactor: rename Campaign tests to Challenge"
```

---

### Task 11: Update CLAUDE.md

**Files:**
- Modify: `CLAUDE.md`

**Step 1: Replace Campaign references with Challenge**

Update the Domain Model section and all other mentions of Campaign to use Challenge consistently.

**Step 2: Remove the "Naming Convention: Campaign vs Challenge" section**

This is no longer needed since we're using Challenge everywhere.

**Step 3: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: update CLAUDE.md for Campaign→Challenge rename"
```

---

### Task 12: Run Full Test Suite and Pint

**Step 1: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

**Step 2: Run full test suite**

```bash
php artisan test --compact
```

Expected: All tests pass.

**Step 3: Commit any Pint fixes**

```bash
git add -A
git commit -m "style: apply Pint formatting after rename"
```

---

## Phase 2: Admin Dashboard Feature

### Task 13: Create Admin ChallengeController

**Files:**
- Create: `app/Http/Controllers/Dashboard/ChallengeController.php`

**Step 1: Create controller**

```bash
php artisan make:controller Dashboard/ChallengeController --no-interaction
```

**Step 2: Implement controller**

```php
<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ChallengeStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Challenge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChallengeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Challenge::query()
            ->with(['user', 'category'])
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->search.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('visibility'), fn ($q) => $q->where('visibility', $request->visibility))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id));

        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortColumn, $sortDirection);

        $challenges = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Challenge::count(),
            'active' => Challenge::where('status', ChallengeStatus::Active)->count(),
            'draft' => Challenge::where('status', ChallengeStatus::Draft)->count(),
            'paused' => Challenge::where('status', ChallengeStatus::Paused)->count(),
        ];

        return Inertia::render('dashboard/challenges/Index', [
            'challenges' => $challenges,
            'filters' => $request->only(['search', 'status', 'visibility', 'category_id', 'sort', 'direction']),
            'stats' => $stats,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'columns' => [
                ['name' => 'title', 'label' => 'Title', 'sortable' => true],
                ['name' => 'user', 'label' => 'Owner', 'sortable' => false],
                ['name' => 'category', 'label' => 'Category', 'sortable' => false],
                ['name' => 'status', 'label' => 'Status', 'sortable' => true],
                ['name' => 'visibility', 'label' => 'Visibility', 'sortable' => true],
                ['name' => 'created_at', 'label' => 'Created', 'sortable' => true],
            ],
        ]);
    }

    public function show(Challenge $challenge): Response
    {
        $challenge->load(['user', 'category', 'currentItem.media', 'goalItem.media']);
        $challenge->loadCount(['offers', 'trades']);

        return Inertia::render('dashboard/challenges/Show', [
            'challenge' => $challenge,
        ]);
    }

    public function trashed(Request $request): Response
    {
        $challenges = Challenge::onlyTrashed()
            ->with(['user', 'category'])
            ->orderBy('deleted_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('dashboard/challenges/Trashed', [
            'challenges' => $challenges,
        ]);
    }

    public function unpublish(Challenge $challenge): RedirectResponse
    {
        $challenge->update(['status' => ChallengeStatus::Draft]);

        return back()->with('success', 'Challenge unpublished.');
    }

    public function bulkUnpublish(Request $request): RedirectResponse
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'exists:challenges,id']]);

        Challenge::whereIn('id', $request->ids)->update(['status' => ChallengeStatus::Draft]);

        return back()->with('success', count($request->ids).' challenges unpublished.');
    }

    public function destroy(Challenge $challenge): RedirectResponse
    {
        $challenge->delete();

        return back()->with('success', 'Challenge deleted.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'exists:challenges,id']]);

        Challenge::whereIn('id', $request->ids)->delete();

        return back()->with('success', count($request->ids).' challenges deleted.');
    }

    public function restore(int $id): RedirectResponse
    {
        $challenge = Challenge::onlyTrashed()->findOrFail($id);
        $challenge->restore();

        return back()->with('success', 'Challenge restored.');
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $challenge = Challenge::onlyTrashed()->findOrFail($id);
        $challenge->forceDelete();

        return back()->with('success', 'Challenge permanently deleted.');
    }
}
```

**Step 3: Commit**

```bash
git add app/Http/Controllers/Dashboard/
git commit -m "feat: add Dashboard ChallengeController for admin"
```

---

### Task 14: Add Admin Routes

**Files:**
- Modify: `routes/web.php`

**Step 1: Add admin routes**

Add inside the authenticated group, protected by `northcloud-admin` middleware:

```php
use App\Http\Controllers\Dashboard\ChallengeController as DashboardChallengeController;

Route::middleware(['auth', 'verified', 'northcloud-admin'])->prefix('dashboard/challenges')->name('dashboard.challenges.')->group(function () {
    Route::get('/', [DashboardChallengeController::class, 'index'])->name('index');
    Route::get('/trashed', [DashboardChallengeController::class, 'trashed'])->name('trashed');
    Route::get('/{challenge}', [DashboardChallengeController::class, 'show'])->name('show');
    Route::post('/{challenge}/unpublish', [DashboardChallengeController::class, 'unpublish'])->name('unpublish');
    Route::post('/bulk-unpublish', [DashboardChallengeController::class, 'bulkUnpublish'])->name('bulk-unpublish');
    Route::delete('/{challenge}', [DashboardChallengeController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-delete', [DashboardChallengeController::class, 'bulkDelete'])->name('bulk-delete');
    Route::post('/{id}/restore', [DashboardChallengeController::class, 'restore'])->name('restore');
    Route::delete('/{id}/force', [DashboardChallengeController::class, 'forceDelete'])->name('force-delete');
});
```

**Step 2: Verify routes**

```bash
php artisan route:list --name=dashboard.challenges
```

**Step 3: Commit**

```bash
git add routes/web.php
git commit -m "feat: add admin routes for dashboard challenges"
```

---

### Task 15: Create ChallengeStatusBadge Component

**Files:**
- Create: `resources/js/components/admin/ChallengeStatusBadge.vue`

**Step 1: Create component**

```vue
<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

interface Props {
    status: 'draft' | 'active' | 'completed' | 'paused';
}

defineProps<Props>();

const statusConfig = {
    draft: { label: 'Draft', variant: 'secondary' as const },
    active: { label: 'Active', variant: 'default' as const },
    completed: { label: 'Completed', variant: 'outline' as const },
    paused: { label: 'Paused', variant: 'destructive' as const },
};
</script>

<template>
    <Badge :variant="statusConfig[status].variant">
        {{ statusConfig[status].label }}
    </Badge>
</template>
```

**Step 2: Commit**

```bash
git add resources/js/components/admin/ChallengeStatusBadge.vue
git commit -m "feat: add ChallengeStatusBadge component"
```

---

### Task 16: Create ChallengesTable Component

**Files:**
- Create: `resources/js/components/admin/ChallengesTable.vue`

**Step 1: Create component**

```vue
<script setup lang="ts">
import { ArrowDown, ArrowUp, Edit, Eye, EyeOff, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import ChallengeStatusBadge from './ChallengeStatusBadge.vue';

interface ColumnDefinition {
    name: string;
    label: string;
    sortable?: boolean;
}

interface Challenge {
    id: number;
    title: string;
    status: 'draft' | 'active' | 'completed' | 'paused';
    visibility: 'public' | 'unlisted';
    created_at: string;
    user?: { id: number; name: string } | null;
    category?: { id: number; name: string } | null;
    [key: string]: unknown;
}

interface PaginatedChallenges {
    data: Challenge[];
    [key: string]: unknown;
}

interface Props {
    challenges: PaginatedChallenges;
    columns: ColumnDefinition[];
    filters?: {
        sort?: string;
        direction?: 'asc' | 'desc';
        [key: string]: unknown;
    };
    selectedIds?: number[];
    showUrl: (id: number) => string;
    indexUrl: string;
}

const props = withDefaults(defineProps<Props>(), {
    selectedIds: () => [],
});

const emit = defineEmits<{
    delete: [challenge: Challenge];
    'update:selected': [ids: number[]];
    unpublish: [challenge: Challenge];
    sort: [column: string, direction: string];
}>();

const allIds = computed(() => props.challenges?.data?.map((c) => c.id) ?? []);
const selectedIdsSet = computed(() => new Set(props.selectedIds));

const checkedStates = computed(() => {
    const states: Record<number, boolean> = {};
    props.challenges?.data?.forEach((challenge) => {
        states[challenge.id] = selectedIdsSet.value.has(challenge.id);
    });
    return states;
});

const isAllSelected = computed(() => {
    if (allIds.value.length === 0) return false;
    return allIds.value.every((id) => selectedIdsSet.value.has(id));
});

const isSomeSelected = computed(() => {
    return props.selectedIds.length > 0 && !isAllSelected.value;
});

const toggleSelectAll = (checked: boolean | 'indeterminate') => {
    const shouldSelect = checked === true || checked === 'indeterminate';
    let newSelectedIds: number[];
    if (shouldSelect) {
        const newIds = allIds.value.filter((id) => !props.selectedIds.includes(id));
        newSelectedIds = [...props.selectedIds, ...newIds];
    } else {
        newSelectedIds = props.selectedIds.filter((id) => !allIds.value.includes(id));
    }
    emit('update:selected', newSelectedIds);
};

const toggleSelect = (id: number) => {
    let newSelectedIds: number[];
    if (props.selectedIds.includes(id)) {
        newSelectedIds = props.selectedIds.filter((i) => i !== id);
    } else {
        newSelectedIds = [...props.selectedIds, id];
    }
    emit('update:selected', newSelectedIds);
};

const handleSort = (column: string) => {
    const newDirection =
        props.filters?.sort === column && props.filters?.direction === 'asc' ? 'desc' : 'asc';
    emit('sort', column, newDirection);
};

const getSortIcon = (column: string) => {
    if (props.filters?.sort !== column) return null;
    return props.filters?.direction === 'asc' ? ArrowUp : ArrowDown;
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const isActive = (challenge: Challenge) => challenge.status === 'active';
</script>

<template>
    <div class="rounded-md border">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th class="w-12 px-4 py-3 text-left text-sm font-medium">
                            <Checkbox
                                :model-value="isAllSelected"
                                :indeterminate="isSomeSelected"
                                @update:model-value="toggleSelectAll"
                            />
                        </th>
                        <th
                            v-for="col in columns"
                            :key="col.name"
                            class="px-4 py-3 text-left text-sm font-medium"
                            :class="{ 'cursor-pointer hover:bg-muted': col.sortable }"
                            @click="col.sortable && handleSort(col.name)"
                        >
                            <div class="flex items-center gap-1">
                                {{ col.label }}
                                <component
                                    :is="getSortIcon(col.name)"
                                    v-if="col.sortable && getSortIcon(col.name)"
                                    class="h-3 w-3"
                                />
                            </div>
                        </th>
                        <th class="px-4 py-3 text-right text-sm font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="challenge in challenges?.data ?? []"
                        :key="challenge.id"
                        class="border-b transition-colors hover:bg-muted/50"
                    >
                        <td class="px-4 py-3">
                            <Checkbox
                                :model-value="checkedStates[challenge.id]"
                                @update:model-value="() => toggleSelect(challenge.id)"
                            />
                        </td>
                        <td v-for="col in columns" :key="col.name" class="px-4 py-3">
                            <!-- Title -->
                            <div v-if="col.name === 'title'" class="max-w-md">
                                <a
                                    :href="showUrl(challenge.id)"
                                    class="line-clamp-2 text-sm font-medium transition-colors hover:text-primary"
                                >
                                    {{ challenge.title }}
                                </a>
                            </div>

                            <!-- Owner -->
                            <span v-else-if="col.name === 'user'" class="text-sm">
                                {{ challenge.user?.name ?? 'Unknown' }}
                            </span>

                            <!-- Category -->
                            <template v-else-if="col.name === 'category'">
                                <Badge v-if="challenge.category" variant="outline" class="text-xs">
                                    {{ challenge.category.name }}
                                </Badge>
                            </template>

                            <!-- Status -->
                            <template v-else-if="col.name === 'status'">
                                <ChallengeStatusBadge :status="challenge.status" />
                            </template>

                            <!-- Visibility -->
                            <template v-else-if="col.name === 'visibility'">
                                <Badge
                                    :variant="challenge.visibility === 'public' ? 'default' : 'secondary'"
                                    class="text-xs"
                                >
                                    {{ challenge.visibility === 'public' ? 'Public' : 'Unlisted' }}
                                </Badge>
                            </template>

                            <!-- Created -->
                            <span v-else-if="col.name === 'created_at'" class="text-sm text-muted-foreground">
                                {{ formatDate(challenge.created_at) }}
                            </span>

                            <!-- Generic fallback -->
                            <span v-else class="text-sm text-muted-foreground">
                                {{ challenge[col.name] ?? '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    v-if="isActive(challenge)"
                                    variant="ghost"
                                    size="sm"
                                    title="Unpublish"
                                    @click="emit('unpublish', challenge)"
                                >
                                    <EyeOff class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="sm" as="a" :href="showUrl(challenge.id)">
                                    <Eye class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="sm" @click="emit('delete', challenge)">
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!challenges?.data || challenges.data.length === 0">
                        <td :colspan="columns.length + 2" class="px-4 py-12 text-center text-muted-foreground">
                            <div class="flex flex-col items-center gap-2">
                                <p class="text-sm">No challenges found.</p>
                                <p class="text-xs">Try adjusting your filters.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
```

**Step 2: Commit**

```bash
git add resources/js/components/admin/ChallengesTable.vue
git commit -m "feat: add ChallengesTable component"
```

---

### Task 17: Create Index Page

**Files:**
- Create: `resources/js/pages/dashboard/challenges/Index.vue`

**Step 1: Create page** (follow pattern from `dashboard/articles/Index.vue`)

The page should include:
- Header with title
- Stats cards (Total, Active, Draft, Paused)
- Filters bar (search, status, visibility, category)
- Bulk action bar when items selected
- ChallengesTable
- Pagination
- Delete confirm dialog

**Step 2: Run lint and build**

```bash
npm run lint
npm run build
```

**Step 3: Commit**

```bash
git add resources/js/pages/dashboard/challenges/
git commit -m "feat: add dashboard challenges Index page"
```

---

### Task 18: Create Show Page

**Files:**
- Create: `resources/js/pages/dashboard/challenges/Show.vue`

**Step 1: Create page**

Display challenge details: title, owner, category, status, visibility, story, current item, goal item, offers count, trades count.

**Step 2: Commit**

```bash
git add resources/js/pages/dashboard/challenges/Show.vue
git commit -m "feat: add dashboard challenges Show page"
```

---

### Task 19: Create Trashed Page

**Files:**
- Create: `resources/js/pages/dashboard/challenges/Trashed.vue`

**Step 1: Create page**

Simple table of soft-deleted challenges with restore and force-delete buttons.

**Step 2: Commit**

```bash
git add resources/js/pages/dashboard/challenges/Trashed.vue
git commit -m "feat: add dashboard challenges Trashed page"
```

---

### Task 20: Add Tests for Admin Feature

**Files:**
- Create: `tests/Feature/Dashboard/ChallengeControllerTest.php`

**Step 1: Create test file**

```bash
php artisan make:test Dashboard/ChallengeControllerTest --pest --no-interaction
```

**Step 2: Write tests**

Test cases:
- `test admin can view challenges index`
- `test non-admin cannot view challenges index`
- `test admin can view challenge details`
- `test admin can unpublish challenge`
- `test admin can bulk unpublish challenges`
- `test admin can delete challenge`
- `test admin can bulk delete challenges`
- `test admin can view trashed challenges`
- `test admin can restore challenge`
- `test admin can force delete challenge`

**Step 3: Run tests**

```bash
php artisan test --compact --filter=ChallengeControllerTest
```

Expected: All tests pass.

**Step 4: Commit**

```bash
git add tests/Feature/Dashboard/
git commit -m "test: add admin dashboard challenge controller tests"
```

---

### Task 21: Final Verification

**Step 1: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

**Step 2: Run full test suite**

```bash
php artisan test --compact
```

Expected: All tests pass.

**Step 3: Build frontend**

```bash
npm run build
```

**Step 4: Commit any final fixes**

```bash
git add -A
git commit -m "chore: final cleanup for Campaign→Challenge rename and admin CRUD"
```

---

## Summary

**Phase 1 (Tasks 1-12):** Rename Campaign → Challenge across database, models, enums, actions, controllers, routes, frontend, and tests.

**Phase 2 (Tasks 13-21):** Build admin dashboard feature with table view, filters, unpublish, soft delete, trashed view, and tests.

Total: 21 tasks, each with bite-sized steps.
