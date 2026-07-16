<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared listing filters for the report / late / early / half / leave screens.
 *
 * Admins see every user's records and get an extra "User" filter; everyone
 * else only ever sees their own rows.
 */
trait ListFilters
{
    /**
     * Base query: admins see everything, other roles only their own records.
     */
    protected function baseListQuery(Request $request, string $modelClass): Builder
    {
        $user = $request->user();

        $query = $modelClass::query()->with('user');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    /** Admin-only: narrow to a single user. */
    protected function applyUserFilter(Builder $query, Request $request): void
    {
        if ($request->user()->isAdmin() && $request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
    }

    protected function applyDepartmentFilter(Builder $query, Request $request): void
    {
        if ($request->filled('department')) {
            $query->where('department_name', $request->input('department'));
        }
    }

    /** status=sent | draft */
    protected function applyStatusFilter(Builder $query, Request $request): void
    {
        if ($request->input('status') === 'sent') {
            $query->whereNotNull('sent_at');
        } elseif ($request->input('status') === 'draft') {
            $query->whereNull('sent_at');
        }
    }

    /** Single-date column between from..to. */
    protected function applyDateRange(Builder $query, Request $request, string $column): void
    {
        if ($request->filled('from')) {
            $query->whereDate($column, '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate($column, '<=', $request->input('to'));
        }
    }

    /** Records with a start/end span: keep anything overlapping from..to. */
    protected function applyDateOverlap(Builder $query, Request $request, string $start, string $end): void
    {
        if ($request->filled('from')) {
            $query->whereDate($end, '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate($start, '<=', $request->input('to'));
        }
    }

    /** Free-text search across the given columns. */
    protected function applySearch(Builder $query, Request $request, array $columns): void
    {
        if (! $request->filled('q')) {
            return;
        }

        $term = '%'.$request->input('q').'%';

        $query->where(function (Builder $q) use ($columns, $term) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', $term);
            }
        });
    }

    /** Dropdown data for the filter bar. */
    protected function filterOptions(Request $request): array
    {
        return [
            'users' => $request->user()->isAdmin()
                ? User::orderBy('name')->get(['id', 'name'])
                : null,
            'departments' => Department::orderBy('name')->pluck('name'),
        ];
    }

    /** Admins review data; they never file applications themselves. */
    protected function denyAdminEntry(Request $request): void
    {
        abort_if(
            $request->user()->isAdmin(),
            403,
            'Administrators review submissions; they do not file them.'
        );
    }
}
