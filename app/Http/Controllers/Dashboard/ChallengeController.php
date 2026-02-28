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

        return Inertia::render('dashboard/admin/challenges/Index', [
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

        return Inertia::render('dashboard/admin/challenges/Show', [
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

        return Inertia::render('dashboard/admin/challenges/Trashed', [
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

    public function bulkRestore(Request $request): RedirectResponse
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);

        Challenge::onlyTrashed()->whereIn('id', $request->ids)->restore();

        return back()->with('success', count($request->ids).' challenges restored.');
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $challenge = Challenge::onlyTrashed()->findOrFail($id);
        $challenge->forceDelete();

        return back()->with('success', 'Challenge permanently deleted.');
    }

    public function bulkForceDelete(Request $request): RedirectResponse
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);

        Challenge::onlyTrashed()->whereIn('id', $request->ids)->forceDelete();

        return back()->with('success', count($request->ids).' challenges permanently deleted.');
    }
}
