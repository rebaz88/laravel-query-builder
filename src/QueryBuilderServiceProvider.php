<?php

namespace Spatie\QueryBuilder;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Spatie\QueryBuilder\Exceptions\InvalidJsonFilter;

class QueryBuilderServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/query-builder.php' => config_path('query-builder.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__ . '/../config/query-builder.php', 'query-builder');

        Request::macro('includes', function ($include = null) {
            $parameter = config('query-builder.parameters.include');
            $includeParts = $this->query($parameter);

            if (!is_array($includeParts)) {
                $includeParts = explode(',', strtolower($this->query($parameter)));
            }

            $includes = collect($includeParts)->filter();

            if (is_null($include)) {
                return $includes;
            }

            return $includes->contains(strtolower($include));
        });

        Request::macro('appends', function ($append = null) {
            $parameter = config('query-builder.parameters.append');
            $appendParts = $this->query($parameter);

            if (!is_array($appendParts)) {
                $appendParts = explode(',', strtolower($this->query($parameter)));
            }

            $appends = collect($appendParts)->filter();

            if (is_null($append)) {
                return $appends;
            }

            return $appends->contains(strtolower($append));
        });

        Request::macro('filters', function ($filter = null) {
            $filterParts = $this->query(
                config('query-builder.parameters.filter')
            );

            if (!$filterParts) {
                return collect();
            }

            $filters = json_decode($filterParts, true);
            if (!is_array($filters)) {
                throw InvalidJsonFilter::invalidJsonString($filterParts);
            }

            $filters = collect($filters)->map(function ($filter) {
                return [$filter["field"] => $filter["value"]];
            })->collapse();

            $filters = collect($filters)->filter(function ($filter) {
                return !is_null($filter);
            });

            $filtersMapper = function ($value) {
                if (is_array($value)) {
                    return collect($value)->map($this)->all();
                }

                if (str_contains($value, ',')) {
                    return explode(',', $value);
                }

                if ($value === 'true') {
                    return true;
                }

                if ($value === 'false') {
                    return false;
                }

                return $value;
            };

            $filters = $filters->map($filtersMapper->bindTo($filtersMapper));

            if (is_null($filter)) {
                return $filters;
            }

            return $filters->get(strtolower($filter));
        });

        Request::macro('fields', function ($default = null) {
            return collect($this->query(config('query-builder.parameters.fields'), $default));
        });

        Request::macro('order', function ($default = null) {
            return $this->query(config('query-builder.parameters.order'), $default);
        });

        Request::macro('orders', function ($default = null) {
            $orderParts = $this->order();

            if (!is_array($orderParts)) {
                $orderParts = explode(',', $orderParts);
            }

            $orders = collect($orderParts)->filter();

            if ($orders->isNotEmpty()) {
                return $orders;
            }

            return collect($default)->filter();
        });

        Request::macro('sort', function ($default = null) {
            return $this->query(config('query-builder.parameters.sort'), $default);
        });

        Request::macro('sorts', function ($default = null) {
            $sortParts = $this->sort();

            if (!is_array($sortParts)) {
                $sortParts = explode(',', $sortParts);
            }

            $sorts = collect($sortParts)->filter();

            if ($sorts->isNotEmpty()) {
                return $sorts;
            }

            return collect($default)->filter();
        });

    }
}
