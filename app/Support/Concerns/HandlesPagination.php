<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait HandlesPagination
{
    /**
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
     * @param  Builder<Model>  $query
     */
    private function paginateQuery(
        Builder $query,
        Request $request
    ): LengthAwarePaginator {
        return $query
            ->paginate($this->resolvePerPage($request))
            ->appends($request->query());
    }
}
