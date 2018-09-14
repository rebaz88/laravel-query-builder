<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValueQuery;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValueCountQuery;


class FiltersBetween implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        if (is_array($value)) {
            if(count($value) != 2)
            {
                throw InvalidFilterValueCountQuery::valueCountNotAllowed(count($value), 2);
            }

            $dateFrom  = date($value[0]);
            $dateTo  = date($value[1]);
            return $query->whereBetween($property, [$dateFrom, $dateTo]);
        }

        throw InvalidFilterValueQuery::typesNotAllowed(collect(getType($value)), collect(['Array']));
    }
}
