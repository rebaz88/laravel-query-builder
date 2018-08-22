<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidJsonFilter extends InvalidQuery
{
    /** @var \Illuminate\Support\Collection */
    public $unknownFilters;

    /** @var \Illuminate\Support\Collection */
    public $allowedFilters;

    public function __construct(String $jsonString)
    {
        $message = "Invalid json filter string `{$jsonString}` is provided";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function invalidJsonString(String $jsonString)
    {
        return new static(...func_get_args());
    }
}
