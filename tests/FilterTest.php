<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValueQuery;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValueCountQuery;
use Spatie\QueryBuilder\Filter;
use Spatie\QueryBuilder\Filters\Filter as CustomFilter;
use Spatie\QueryBuilder\Filters\Filter as FilterInterface;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Models\TestModel;

class FilterTest extends TestCase
{
    /** @var \Illuminate\Support\Collection */
    protected $models;

    public function setUp()
    {
        parent::setUp();

        $this->models = factory(TestModel::class, 5)->create();
    }

    /** @test */
    public function it_can_filter_models_by_partial_property_by_default()
    {
        $models = $this
            ->createQueryFromFilterRequest([[
                'field' => 'name',
                'value' => $this->models->first()->name,
            ]])
            ->allowedFilters('name')
            ->get();

        $this->assertCount(1, $models);
    }

    /** @test */
    public function it_can_filter_partially_and_case_insensitive()
    {
        $models = $this
            ->createQueryFromFilterRequest([[
                'field' => 'name',
                'value' => $this->models->first()->name,
            ]])
            ->allowedFilters('name')
            ->get();

        $this->assertCount(1, $models);
    }

    /** @test */
    public function it_can_filter_results_based_on_the_partial_existence_of_a_property_in_an_array()
    {
        $model1 = TestModel::create(['name' => 'abcdef']);
        $model2 = TestModel::create(['name' => 'uvwxyz']);

        $results = $this
            ->createQueryFromFilterRequest([[
                'field' => 'name',
                'value' => 'abc,xyz',
            ]])
            ->allowedFilters('name')
            ->get();

        $this->assertCount(2, $results);
        $this->assertEquals([$model1->id, $model2->id], $results->pluck('id')->all());
    }

    /** @test */
    public function it_can_filter_models_and_return_an_empty_collection()
    {
        $models = $this
            ->createQueryFromFilterRequest([[
                'field' => 'name',
                'value' => 'None existing first name',
            ]])
            ->allowedFilters('name')
            ->get();

        $this->assertCount(0, $models);
    }

    /** @test */
    public function it_can_filter_results_based_on_the_existence_of_a_property_in_an_array()
    {
        $results = $this
            ->createQueryFromFilterRequest([[
                'field' => 'id',
                'value' => '1,2',
            ]])
            ->allowedFilters(Filter::exact('id'))
            ->get();

        $this->assertCount(2, $results);
        $this->assertEquals([1, 2], $results->pluck('id')->all());
    }

    /** @test */
    public function it_can_filter_and_match_results_by_exact_property()
    {
        $testModel = TestModel::first();

        $models = TestModel::where('id', $testModel->id)
            ->get();

        $modelsResult = $this
            ->createQueryFromFilterRequest([[
                'field' => 'id',
                'value' => $testModel->id,
            ]])
            ->allowedFilters(Filter::exact('id'))
            ->get();

        $this->assertEquals($modelsResult, $models);
    }

    /** @test */
    public function it_can_filter_and_reject_results_by_exact_property()
    {
        $testModel = TestModel::create(['name' => 'John Testing Doe']);

        $modelsResult = $this
            ->createQueryFromFilterRequest([[
                'field' => 'name',
                'value' => 'Testing',
            ]])
            ->allowedFilters(Filter::exact('name'))
            ->get();

        $this->assertCount(0, $modelsResult);
    }

    /** @test */
    public function it_can_filter_results_by_scope()
    {
        $testModel = TestModel::create(['name' => 'John Testing Doe']);

        $modelsResult = $this
            ->createQueryFromFilterRequest([[
                'field' => 'named',
                'value' => 'John Testing Doe',
            ]])
            ->allowedFilters(Filter::scope('named'))
            ->get();

        $this->assertCount(1, $modelsResult);
    }

    /** @test */
    public function it_can_filter_results_by_a_custom_filter_class()
    {
        $testModel = $this->models->first();

        $filterClass = new class implements FilterInterface
        {
            public function __invoke(Builder $query, $value, string $property): Builder
            {
                return $query->where('name', $value);
            }
        };

        $modelResult = $this
            ->createQueryFromFilterRequest([[
                'field' => 'custom_name',
                'value' => $testModel->name,
            ]])
            ->allowedFilters(Filter::custom('custom_name', get_class($filterClass)))
            ->first();

        $this->assertEquals($testModel->id, $modelResult->id);
    }

    /** @test */
    public function it_can_allow_multiple_filters()
    {
        $model1 = TestModel::create(['name' => 'abcdef']);
        $model2 = TestModel::create(['name' => 'abcdef']);

        $results = $this
            ->createQueryFromFilterRequest([[
                'field' => 'name',
                'value' => 'abc',
            ],
                [
                    'field' => 'id',
                    'value' => '6,7',
                ],
            ])
            // ->createQueryFromFilterRequest([
            // 'name' => 'abc',
            // 'id' => '6,7'
            // ])
            ->allowedFilters('name', Filter::exact('id'))
            ->get();

        $this->assertCount(2, $results);
        $this->assertEquals([$model1->id, $model2->id], $results->pluck('id')->all());
    }

    /** @test */
    public function it_can_allow_multiple_filters_as_an_array()
    {
        $model1 = TestModel::create(['name' => 'abcdef']);
        $model2 = TestModel::create(['name' => 'abcdef']);

        $results = $this
            ->createQueryFromFilterRequest([[
                'field' => 'name',
                'value' => 'abc',
            ]])
            ->allowedFilters(['name', Filter::exact('id')])
            ->get();

        $this->assertCount(2, $results);
        $this->assertEquals([$model1->id, $model2->id], $results->pluck('id')->all());
    }

    /** @test */
    public function it_can_filter_by_multiple_filters()
    {
        $model1 = TestModel::create(['name' => 'abcdef']);
        $model2 = TestModel::create(['name' => 'abcdef']);

        $results = $this
            ->createQueryFromFilterRequest([
                [
                    'field' => 'name',
                    'value' => 'abc',
                ],
                [
                    'field' => 'id',
                    'value' => "1,{$model1->id}",
                ],
            ])
            ->allowedFilters('name', Filter::exact('id'))
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals([$model1->id], $results->pluck('id')->all());
    }

    /** @test */
    public function it_guards_against_invalid_filters()
    {
        $this->expectException(InvalidFilterQuery::class);

        $this
            ->createQueryFromFilterRequest([[
                'field' => 'name',
                'value' => 'John',
            ]])
            ->allowedFilters('id');
    }

    /** @test */
    public function it_can_create_a_custom_filter_with_an_instantiated_filter()
    {
        $customFilter = new class('test1') implements CustomFilter
        {
            /** @var string */
            private $filter;

            public function __construct(string $filter)
            {
                $this->filter = $filter;
            }

            public function __invoke(Builder $query, $value, string $property): Builder
            {
                return $query;
            }
        };

        TestModel::create(['name' => 'abcdef']);

        $results = $this
            ->createQueryFromFilterRequest([[
                'field' => '*',
                'value' => '*',
            ]])
            ->allowedFilters('name', Filter::custom('*', $customFilter))
            ->get();

        $this->assertNotEmpty($results);
    }

    /** @test */
    public function an_invalid_filter_query_exception_contains_the_unknown_and_allowed_filters()
    {
        $exception = new InvalidFilterQuery(collect(['unknown filter']), collect(['allowed filter']));

        $this->assertEquals(['unknown filter'], $exception->unknownFilters->all());
        $this->assertEquals(['allowed filter'], $exception->allowedFilters->all());
    }

    /** @test */
    public function it_can_filter_by_greater_than()
    {

        $modelsResult = $this
            ->createQueryFromFilterRequest([[
                'field' => 'id',
                'value' => '3',
            ]])
            ->allowedFilters(Filter::gt('id'))
            ->get();

        $this->assertCount(2, $modelsResult);
    }

    /** @test */
    public function it_can_filter_by_less_than()
    {

        $modelsResult = $this
            ->createQueryFromFilterRequest([[
                'field' => 'id',
                'value' => '3',
            ]])
            ->allowedFilters(Filter::lt('id'))
            ->get();

        $this->assertCount(2, $modelsResult);
    }

    /** @test */
    public function it_can_filter_by_between()
    {
        $model1 = TestModel::create(['name' => 'abcdef', 'age'=>'10']);
        $model2 = TestModel::create(['name' => 'uvwxyz', 'age'=>'20']);

        $modelsResult = $this
            ->createQueryFromFilterRequest([[
                'field' => 'age',
                'value' => '15,30',
            ]])
            ->allowedFilters(Filter::between('age'))
            ->get();

        $this->assertCount(1, $modelsResult);
    }

    /** @test */
    public function it_can_filter_by_between_date()
    {
        $model1 = TestModel::create(['name' => 'abcdef', 'date'=>'2000-02-01 02:00:00']);
        $model2 = TestModel::create(['name' => 'ghijkl', 'date'=>'2000-02-03 02:00:00']);
        $model3 = TestModel::create(['name' => 'uvwxyz', 'date'=>'2000-02-05 03:00:00']);

        $modelsResult = $this
            ->createQueryFromFilterRequest([[
                'field' => 'date',
                'value' => '2000-02-02,2000-02-04',
            ]])
            ->allowedFilters(Filter::betweenDate('date'))
            ->get();

        $this->assertCount(1, $modelsResult);
    }

    /** @test */
    public function it_can_filter_by_between_date_time()
    {
        $model1 = TestModel::create(['name' => 'abcdef', 'date_time'=>'2000-02-02 02:00:00']);
        $model2 = TestModel::create(['name' => 'ghijkl', 'date_time'=>'2000-02-04 02:00:00']);
        $model3 = TestModel::create(['name' => 'uvwxyz', 'date_time'=>'2000-02-04 03:00:00']);

        $modelsResult = $this
            ->createQueryFromFilterRequest([[
                'field' => 'date_time',
                'value' => '2000-02-03 00:00:00,2000-02-04 02:00:00',
            ]])
            ->allowedFilters(Filter::betweenDateTime('date_time'))
            ->get();

        $this->assertCount(1, $modelsResult);
    }

    /** @test */
    public function it_guards_against_invalid_filter_between_value_types()
    {
        $this->expectException(InvalidFilterValueQuery::class);

        $this
            ->createQueryFromFilterRequest([[
                'field' => 'date_time',
                'value' => '1980-01-01',
            ]])
            ->allowedFilters(Filter::between('date_time'))
            ->get();
    }

    /** @test */
    public function it_guards_against_invalid_filter_between_value_length()
    {
        $this->expectException(InvalidFilterValueCountQuery::class);

        $this
            ->createQueryFromFilterRequest([[
                'field' => 'date_time',
                'value' => '1980-01-01,1980-01-01,1980-01-01',
            ]])
            ->allowedFilters(Filter::between('date_time'))
            ->get();
    }


    /** @test */
    public function it_can_exclude_filter()
    {
        $modelsResult = $this
            ->createQueryFromFilterRequest([[
                'field' => 'id',
                'value' => '1',
            ],[
                'field' => 'name',
                'value' => 'abcd',
            ]])
            ->allowedFilters(Filter::exact('id'), Filter::exclude('name'))
            ->get();

        $this->assertCount(1, $modelsResult);
    }

    protected function createQueryFromFilterRequest(array $filterRules): QueryBuilder
    {
        $filters = collect($filterRules);
        $request = new Request([
            'filterRules' => "$filters",
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }
}
