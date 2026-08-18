<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Trait for building admin index views with consistent filtering and pagination patterns
 */
trait BuildsAdminIndexViews
{
    /**
     * Build an admin index view with standardized filtering and pagination
     *
     * @param  Builder<Model>  $query
     * @param  string  $viewName  Blade view name (e.g., 'admin.resources.index')
     * @param  array<string, mixed>  $data  Additional data to pass to view
     */
    protected function buildAdminIndexView(
        Builder $query,
        Request $request,
        string $viewName,
        array $data = [],
    ): View {
        $perPage = $this->resolvePerPage($request);
        $items = $query
            ->latest('id')
            ->paginate($perPage)
            ->appends($request->query());

        return view($viewName, [
            ...$data,
            'items' => $items,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Build admin index view with resource name (auto-pluralize in data key)
     *
     * Example: buildAdminIndex(..., 'users', 'admin.users.index')
     * Returns view with 'users' key containing paginated results
     *
     * @param  Builder<Model>  $query
     * @param  string  $resourceName  Singular name (e.g., 'user', 'session')
     * @param  string  $viewName  Blade view name
     * @param  array<string, mixed>  $data  Additional data for view
     */
    protected function buildAdminIndexWithResource(
        Builder $query,
        Request $request,
        string $resourceName,
        string $viewName,
        array $data = [],
    ): View {
        $pluralName = $this->pluralize($resourceName);
        $data[$pluralName] = $this->paginateQuery($query, $request);

        return view($viewName, $data);
    }

    /**
     * Extract keyword filter from request (common pattern)
     */
    protected function extractKeywordFilter(Request $request, string $key = 'q'): string
    {
        return trim((string) $request->query($key, ''));
    }

    /**
     * Extract status filter from request and convert to boolean
     */
    protected function extractBoolFilter(Request $request, string $key): ?bool
    {
        $value = $request->query($key);
        if ($value === null || $value === '') {
            return null;
        }

        return (bool) ((int) $value);
    }

    /**
     * Apply keyword filter to query (searches multiple columns)
     *
     * @param  Builder<Model>  $query
     * @param  string  $keyword  Search term
     * @param  array<int, string>  $columns  Columns to search
     */
    protected function applyKeywordFilter(
        Builder $query,
        string $keyword,
        array $columns,
    ): void {
        if ($keyword === '') {
            return;
        }

        $query->where(function ($inner) use ($keyword, $columns): void {
            foreach ($columns as $column) {
                $inner->orWhere($column, 'like', '%'.$keyword.'%');
            }
        });
    }

    /**
     * Apply exact match filter to query
     *
     * @param  Builder<Model>  $query
     * @param  string  $column  Column name
     * @param  mixed  $value  Value to match
     */
    protected function applyExactFilter(
        Builder $query,
        string $column,
        mixed $value,
    ): void {
        if ($value === null || $value === '') {
            return;
        }

        $query->where($column, $value);
    }

    /**
     * Pagination using HandlesPagination trait
     *
     * @param  Builder<Model>  $query
     */
    private function paginateQuery(
        Builder $query,
        Request $request,
    ): LengthAwarePaginator {
        return $query
            ->paginate($this->resolvePerPage($request))
            ->appends($request->query());
    }

    /**
     * Resolve per-page value from request (uses HandlesPagination defaults)
     *
     * @var array<int, int>
     */
    private array $paginationPerPageOptions = [10, 25, 50, 100];

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 10);

        return in_array(
            $perPage,
            $this->paginationPerPageOptions,
            true
        )
            ? $perPage
            : 10;
    }

    /**
     * Simplistic pluralization (add 's' for English)
     * Override in subclass if custom pluralization needed
     */
    protected function pluralize(string $singular): string
    {
        // Simple heuristic for common patterns
        return match (true) {
            str_ends_with($singular, 'y') && ! str_ends_with($singular, 'ay') && ! str_ends_with($singular, 'ey') => substr($singular, 0, -1).'ies',
            str_ends_with($singular, 's') || str_ends_with($singular, 'x') || str_ends_with($singular, 'z') => $singular.'es',
            str_ends_with($singular, 'o') => $singular.'es',
            default => $singular.'s',
        };
    }
}
