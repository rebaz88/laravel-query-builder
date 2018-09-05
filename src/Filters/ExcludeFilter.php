<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

class ExcludeFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        return $query;
    }
}
