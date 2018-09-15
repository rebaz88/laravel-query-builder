<?php

namespace Spatie\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter as CustomFilter;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Filters\FiltersGreaterThan;
use Spatie\QueryBuilder\Filters\FiltersLessThan;
use Spatie\QueryBuilder\Filters\FiltersBetween;
use Spatie\QueryBuilder\Filters\FiltersBetweenDate;
use Spatie\QueryBuilder\Filters\FiltersBetweenDateTime;
use Spatie\QueryBuilder\Filters\ExcludeFilter;
use Spatie\QueryBuilder\Filters\FiltersPartial;
use Spatie\QueryBuilder\Filters\FiltersScope;

class Filter
{
    /** @var string */
    protected $filterClass;

    /** @var string */
    protected $property;

    public function __construct(string $property, $filterClass)
    {
        $this->property = $property;
        $this->filterClass = $filterClass;
    }

    public function filter(Builder $builder, $value)
    {
        $filterClass = $this->resolveFilterClass();

        ($filterClass)($builder, $value, $this->property);
    }

    public static function exact(string $property): self
    {
        return new static($property, FiltersExact::class);
    }
    public static function gt(string $property): self
    {
        return new static($property, FiltersGreaterThan::class);
    }

    public static function lt(string $property): self
    {
        return new static($property, FiltersLessThan::class);
    }

    public static function between(string $property): self
    {
        return new static($property, FiltersBetween::class);
    }

    public static function betweenDate(string $property): self
    {
        return new static($property, FiltersBetweenDate::class);
    }

    public static function betweenDateTime(string $property): self
    {
        return new static($property, FiltersBetweenDateTime::class);
    }

    public static function exclude(string $property): self
    {
        return new static($property, ExcludeFilter::class);
    }

    public static function partial(string $property): self
    {
        return new static($property, FiltersPartial::class);
    }

    public static function scope(string $property): self
    {
        return new static($property, FiltersScope::class);
    }

    public static function custom(string $property, $filterClass): self
    {
        return new static($property, $filterClass);
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function isForProperty(string $property): bool
    {
        return $this->property === $property;
    }

    private function resolveFilterClass(): CustomFilter
    {
        if ($this->filterClass instanceof CustomFilter) {
            return $this->filterClass;
        }

        return new $this->filterClass;
    }
}
