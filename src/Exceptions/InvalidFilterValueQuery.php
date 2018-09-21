<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidFilterValueQuery extends InvalidQuery
{
    /** @var \Illuminate\Support\Collection */
    public $unknownTypes;

    /** @var \Illuminate\Support\Collection */
    public $allowedTypes;

    public function __construct(Collection $unknownTypes, Collection $allowedTypes)
    {
        $this->unknownTypes = $unknownTypes;
        $this->allowedTypes = $allowedTypes;

        $unknownTypes = $unknownTypes->implode(', ');
        $allowedTypes = $allowedTypes->implode(', ');

        $message = "Given filter value types(s) `{$unknownTypes}` are not allowed. Allowed type(s) are `{$allowedTypes}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function typesNotAllowed(Collection $unknownTypes, Collection $allowedTypes)
    {
        return new static(...func_get_args());
    }

}