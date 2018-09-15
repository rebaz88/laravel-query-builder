<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValueQuery;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValueCountQuery;


class FiltersBetweenDateTime implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        if (!is_array($value)) {
            throw InvalidFilterValueQuery::typesNotAllowed(collect(getType($value)), collect(['Array']));
        }

        if(count($value) != 2)
        {
            throw InvalidFilterValueCountQuery::valueCountNotAllowed(count($value), 2);
        }

        $from = new \DateTime($value[0]);
        $to   = new \DateTime($value[1]);

        return $query->whereBetween($property, [$from, $to]);
    }
}
