<?php

namespace Spatie\QueryBuilder\Tests;

use DB;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Concerns\AssertsCollectionSorting;
use Spatie\QueryBuilder\Tests\Models\TestModel;

class SortTest extends TestCase
{
    use AssertsCollectionSorting;

    /** @var \Illuminate\Support\Collection */
    protected $models;
    protected $modelTableName;

    public function setUp()
    {
        parent::setUp();

        DB::enableQueryLog();

        $this->models = factory(TestModel::class, 5)->create();
        $this->modelTableName = $this->models->first()->getTable();
    }

    /** @test */
    public function it_can_sort_a_collection_ascending()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('name', 'asc')
            ->get();

        $this->assertQueryExecuted('select * from "test_models" order by "name" asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_sort_a_collection_descending()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('name', 'desc')
            ->get();

        $this->assertQueryExecuted('select * from "test_models" order by "name" desc');
        $this->assertSortedDescending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_guard_against_sorts_that_are_not_allowed()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('name', 'asc')
            ->allowedSorts('name')
            ->get();

        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_guard_against_unknown_order_type()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('name', 'unknown order type')
            ->allowedSorts('name')
            ->get();

        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_will_throw_an_exception_if_a_sort_property_is_not_allowed()
    {
        $this->expectException(InvalidSortQuery::class);

        $this
            ->createQueryFromSortRequest('name', 'asc')
            ->allowedSorts('id');
    }

    /** @test */
    public function an_invalid_sort_query_exception_contains_the_unknown_and_allowed_sorts()
    {
        $exception = new InvalidSortQuery(collect(['unknown sort']), collect(['allowed sort']));

        $this->assertEquals(['unknown sort'], $exception->unknownSorts->all());
        $this->assertEquals(['allowed sort'], $exception->allowedSorts->all());
    }

    /** @test */
    public function it_wont_sort_if_no_sort_query_parameter_is_given()
    {
        $builderQuery = QueryBuilder::for(TestModel::class, new Request()) 
            ->allowedSorts('name')
            ->toSql();

        $eloquentQuery = TestModel::query()->select("*")->toSql();

        $this->assertEquals($eloquentQuery, $builderQuery);
    }

    /** @test */
    public function it_uses_default_sort_parameter()
    {
        $sortedModels = QueryBuilder::for(TestModel::class, new Request()) 
            ->allowedSorts('name')
            ->defaultSort('name')
            ->get();

        $this->assertQueryExecuted('select * from "test_models" order by "name" asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_allow_multiple_sort_parameters()
    {
        DB::enableQueryLog();
        $sortedModels = $this
            ->createQueryFromSortRequest('name', 'asc')
            ->allowedSorts('id', 'name')
            ->get();

        $this->assertQueryExecuted('select * from "test_models" order by "name" asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_allow_multiple_sort_parameters_as_an_array()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('name', 'asc')
            ->allowedSorts(['id', 'name'])
            ->get();

        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_sort_by_multiple_columns()
    {
        factory(TestModel::class, 3)->create(['name' => 'foo']);

        $sortedModels = $this
            ->createQueryFromSortRequest('name,id', 'asc,desc')
            ->allowedSorts('name', 'id')
            ->get();

        $expected = TestModel::orderBy('name')->orderByDesc('id');
        $this->assertQueryExecuted('select * from "test_models" order by "name" asc, "id" desc');
        $this->assertEquals($expected->pluck('id'), $sortedModels->pluck('id'));
    }

    protected function createQueryFromSortRequest(string $sort, string $order): QueryBuilder
    {
        $request = new Request([
            'sort' => $sort,
            'order' => $order,
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }

    protected function assertQueryExecuted(string $query)
    {
        $queries = array_map(function ($queryLogItem) {
            return $queryLogItem['query'];
        }, DB::getQueryLog());

        $this->assertContains($query, $queries);
    }
}
