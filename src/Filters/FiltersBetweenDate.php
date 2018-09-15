<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValueQuery;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValueCountQuery;


class FiltersBetweenDate extends FiltersBetween implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        $from = (new \DateTime($value[0]))->setTime(0,0,0,0);
        $to   = (new \DateTime($value[1]))->setTime(0,0,0,0);

        return parent::__invoke($query, [$from, $to], $property);

    }
}
