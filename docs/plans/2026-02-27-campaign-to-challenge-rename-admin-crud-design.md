# Campaign to Challenge Rename + Admin CRUD — Design

**Date:** 2026-02-27  
**Goal:** Rename "Campaign" to "Challenge" throughout the codebase, and add admin CRUD for challenges in the dashboard with quick unpublish/delete capabilities.

## 1. Scope

### Part A: Rename Refactor (Campaign → Challenge)

Full rename across the codebase to align backend terminology with user-facing UI.

### Part B: Admin Dashboard Feature

Moderation-focused admin interface for challenges with:
- Table view (filter, sort, paginate, bulk select)
- Quick unpublish (sets status to Draft)
- Soft delete with trashed view
- Bulk unpublish and bulk delete

## 2. Rename Refactor Details

### Database Migration

Create `rename_campaigns_to_challenges` migration:

1. Rename table `campaigns` → `challenges`
2. Rename foreign key columns:
   - `offers.campaign_id` → `offers.challenge_id`
   - `trades.campaign_id` → `trades.challenge_id`
3. Update polymorphic `itemable_type` values: `App\Models\Campaign` → `App\Models\Challenge`
4. Update polymorphic `commentable_type` values similarly
5. Add `deleted_at` column to `challenges` for soft deletes

Migration must be reversible.

### Backend Files

| From | To |
|------|-----|
| `App\Models\Campaign` | `App\Models\Challenge` |
| `App\Enums\CampaignStatus` | `App\Enums\ChallengeStatus` |
| `App\Enums\CampaignVisibility` | `App\Enums\ChallengeVisibility` |
| `App\Actions\CreateCampaign` | `App\Actions\CreateChallenge` |
| `App\Actions\UpdateCampaign` | `App\Actions\UpdateChallenge` |
| `App\Http\Controllers\CampaignController` | `App\Http\Controllers\ChallengeController` |
| `App\Http\Controllers\Api\CampaignApiController` | `App\Http\Controllers\Api\ChallengeApiController` |
| `App\Http\Requests\StoreCampaignRequest` | `App\Http\Requests\StoreChallengeRequest` |
| `App\Http\Requests\UpdateCampaignRequest` | `App\Http\Requests\UpdateChallengeRequest` |
| `Database\Factories\CampaignFactory` | `Database\Factories\ChallengeFactory` |
| `Database\Seeders\CampaignSeeder` | `Database\Seeders\ChallengeSeeder` |

### Routes

All `campaigns.*` routes rename to `challenges.*`:
- `campaigns.index` → `challenges.index`
- `campaigns.create` → `challenges.create`
- `campaigns.store` → `challenges.store`
- `campaigns.show` → `challenges.show`
- `campaigns.edit` → `challenges.edit`
- `campaigns.update` → `challenges.update`
- `campaigns.offers.store` → `challenges.offers.store`
- `api.campaigns.*` → `api.challenges.*`

URL paths change from `/campaigns` to `/challenges`.

### Frontend

- Update Wayfinder imports from `@/actions/CampaignController` to `@/actions/ChallengeController`
- Update prop names from `campaign` to `challenge` in Vue components
- Pages already at `resources/js/pages/challenges/` — minimal path changes

### Tests

Rename test files and update all references:
- `CampaignControllerTest` → `ChallengeControllerTest`
- `CreateCampaignTest` → `CreateChallengeTest`
- etc.

## 3. Admin Dashboard Feature

### Routes

Protected by `northcloud-admin` middleware under `/dashboard/challenges`:

| Method | URI | Action | Name |
|--------|-----|--------|------|
| GET | `/dashboard/challenges` | index | `dashboard.challenges.index` |
| GET | `/dashboard/challenges/trashed` | trashed | `dashboard.challenges.trashed` |
| GET | `/dashboard/challenges/{challenge}` | show | `dashboard.challenges.show` |
| POST | `/dashboard/challenges/{challenge}/unpublish` | unpublish | `dashboard.challenges.unpublish` |
| POST | `/dashboard/challenges/bulk-unpublish` | bulkUnpublish | `dashboard.challenges.bulk-unpublish` |
| DELETE | `/dashboard/challenges/{challenge}` | destroy | `dashboard.challenges.destroy` |
| POST | `/dashboard/challenges/bulk-delete` | bulkDelete | `dashboard.challenges.bulk-delete` |
| POST | `/dashboard/challenges/{challenge}/restore` | restore | `dashboard.challenges.restore` |
| DELETE | `/dashboard/challenges/{challenge}/force` | forceDelete | `dashboard.challenges.force-delete` |

### Controller

`App\Http\Controllers\Dashboard\ChallengeController`:

- `index()` — paginated challenges with filters (status, visibility, category, search), stats
- `show()` — challenge details with owner, items, offers/trades counts
- `trashed()` — soft-deleted challenges
- `unpublish()` — sets status to Draft
- `bulkUnpublish()` — bulk set status to Draft
- `destroy()` — soft delete
- `bulkDelete()` — bulk soft delete
- `restore()` — restore from trash
- `forceDelete()` — permanent delete

### Frontend Pages

In `resources/js/pages/dashboard/challenges/`:

| Page | Purpose |
|------|---------|
| `Index.vue` | Table with filters, stats, bulk actions, pagination |
| `Show.vue` | Challenge details (owner, items, offers/trades counts) |
| `Trashed.vue` | Soft-deleted challenges with restore/force-delete |

### Frontend Components

In `resources/js/components/admin/`:

| Component | Purpose |
|-----------|---------|
| `ChallengesTable.vue` | Table with columns: Title, Owner, Category, Status, Visibility, Created |
| `ChallengeStatusBadge.vue` | Badge for Draft/Active/Completed/Paused states |

Reuse existing: `StatCard`, `FiltersBar`, `DeleteConfirmDialog`, `BulkActionBar`

### Table Columns

| Column | Sortable | Notes |
|--------|----------|-------|
| Title | Yes | Link to show page |
| Owner | Yes | User name |
| Category | No | Category name badge |
| Status | Yes | ChallengeStatusBadge |
| Visibility | Yes | Public/Unlisted badge |
| Created | Yes | Formatted date |

### Stats Cards

- Total challenges
- Active
- Draft
- Paused

### Filters

- Status (dropdown: All, Draft, Active, Completed, Paused)
- Visibility (dropdown: All, Public, Unlisted)
- Category (dropdown from categories table)
- Search (text input, searches title)

## 4. Behavior

- **Unpublish:** Sets `status` to `ChallengeStatus::Draft`
- **Delete:** Soft delete (sets `deleted_at`)
- **Restore:** Clears `deleted_at`
- **Force delete:** Permanent deletion

## 5. Success Criteria

- All Campaign references renamed to Challenge
- Migration runs cleanly (up and down)
- All existing tests pass after rename
- Admin can view paginated challenges with filters
- Admin can unpublish (single and bulk)
- Admin can delete (single and bulk, soft)
- Admin can view trashed, restore, or permanently delete
- UI uses shadcn-vue components consistent with Articles/Users admin
