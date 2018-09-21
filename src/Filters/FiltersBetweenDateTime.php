<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValueQuery;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValueCountQuery;


class FiltersBetweenDateTime extends FiltersBetween implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        $from = new \DateTime($value[0]);
        $to   = new \DateTime($value[1]);

        return parent::__invoke($query, [$from, $to], $property);

    }
}
