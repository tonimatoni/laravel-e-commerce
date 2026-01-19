<?php

namespace Tests\Feature\Report;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailySalesDataAggregationTest extends TestCase
{
    use RefreshDatabase;

    protected ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = app(ReportService::class);
    }

    public function test_queries_sales_for_current_calendar_day(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);

        CartItem::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'created_at' => today(),
        ]);

        CartItem::factory()->create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'created_at' => today()->subDay(),
        ]);

        $report = $this->reportService->generateDailyReport();

        $this->assertEquals(today()->format('Y-m-d'), $report['date']);
        $this->assertEquals(2, $report['total_items_sold']);
    }

    public function test_groups_data_by_product_id(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['name' => 'Product 1', 'price' => 10.00]);
        $product2 = Product::factory()->create(['name' => 'Product 2', 'price' => 20.00]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'created_at' => today(),
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product2->id,
            'quantity' => 3,
            'created_at' => today(),
        ]);

        $report = $this->reportService->generateDailyReport();

        $this->assertCount(2, $report['products']);
        $this->assertEquals($product1->id, $report['products'][0]['product_id']);
        $this->assertEquals($product2->id, $report['products'][1]['product_id']);
    }

    public function test_calculates_total_units_sold_per_product(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);

        CartItem::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'created_at' => today(),
        ]);

        CartItem::factory()->create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'created_at' => today(),
        ]);

        $report = $this->reportService->generateDailyReport();

        $this->assertEquals(5, $report['products'][0]['total_units_sold']);
    }

    public function test_calculates_total_revenue_per_product(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);

        CartItem::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'created_at' => today(),
        ]);

        CartItem::factory()->create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'created_at' => today(),
        ]);

        $report = $this->reportService->generateDailyReport();

        $this->assertEquals(50.00, $report['products'][0]['revenue']);
    }

    public function test_calculates_total_revenue(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['price' => 10.00]);
        $product2 = Product::factory()->create(['price' => 20.00]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'created_at' => today(),
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product2->id,
            'quantity' => 3,
            'created_at' => today(),
        ]);

        $report = $this->reportService->generateDailyReport();

        $expectedRevenue = (2 * 10.00) + (3 * 20.00);
        $this->assertEquals($expectedRevenue, $report['total_revenue']);
    }

    public function test_eager_loads_product_details(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Test Product', 'price' => 10.00]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'created_at' => today(),
        ]);

        $report = $this->reportService->generateDailyReport();

        $this->assertEquals('Test Product', $report['products'][0]['product_name']);
    }

    public function test_prevents_n_plus_one_queries(): void
    {
        $user = User::factory()->create();
        $products = Product::factory()->count(5)->create();

        foreach ($products as $product) {
            CartItem::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'created_at' => today(),
            ]);
        }

        \DB::enableQueryLog();

        $this->reportService->generateDailyReport();

        $queries = \DB::getQueryLog();
        $productQueries = array_filter($queries, function ($query) {
            return str_contains($query['query'], 'products');
        });

        $this->assertCount(1, $productQueries, 'Should only have one query for products (eager loading)');
    }

    public function test_handles_empty_sales_data(): void
    {
        $report = $this->reportService->generateDailyReport();

        $this->assertEquals(today()->format('Y-m-d'), $report['date']);
        $this->assertEquals(0, $report['total_items_sold']);
        $this->assertEquals(0.0, $report['total_revenue']);
        $this->assertIsArray($report['products']);
        $this->assertCount(0, $report['products']);
    }

    public function test_accepts_custom_date_parameter(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);

        $customDate = Carbon::parse('2026-01-15');

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'created_at' => $customDate,
        ]);

        $report = $this->reportService->generateDailyReport($customDate);

        $this->assertEquals('2026-01-15', $report['date']);
        $this->assertEquals(5, $report['total_items_sold']);
    }
}
