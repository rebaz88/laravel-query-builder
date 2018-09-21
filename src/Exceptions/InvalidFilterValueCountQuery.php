<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidFilterValueCountQuery extends InvalidQuery
{
    /** @var \Illuminate\Support\Collection */
    public $invalidCount;

    /** @var \Illuminate\Support\Collection */
    public $allowedCount;

    public function __construct(int $invalidCount, int $allowedCount)
    {
        $this->invalidCount = $invalidCount;
        $this->allowedCount = $allowedCount;

        $message = "Given filter value count `{$invalidCount}` is not allowed. Allowed filter value count is `{$allowedCount}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }


    public static function valueCountNotAllowed(int $invalidCount, int $allowedCount)
    {
        return new static(...func_get_args());
    }
}