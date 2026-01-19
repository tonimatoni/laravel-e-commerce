<?php

namespace Tests\Unit\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = app(ReportService::class);
    }

    public function test_generate_daily_report_aggregates_data_by_product(): void
    {
        $date = Carbon::today();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product1 = Product::factory()->create(['price' => 10.00]);
        $product2 = Product::factory()->create(['price' => 20.00]);

        CartItem::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'created_at' => $date,
        ]);

        CartItem::factory()->create([
            'user_id' => $user2->id,
            'product_id' => $product1->id,
            'quantity' => 3,
            'created_at' => $date,
        ]);

        CartItem::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'created_at' => $date,
        ]);

        $report = $this->reportService->generateDailyReport($date);

        $this->assertEquals($date->format('Y-m-d'), $report['date']);
        $this->assertEquals(6, $report['total_items_sold']);
        $this->assertEquals(70.00, $report['total_revenue']);
        $this->assertCount(2, $report['products']);

        $product1Data = collect($report['products'])->firstWhere('product_id', $product1->id);
        $this->assertEquals(5, $product1Data['total_units_sold']);
        $this->assertEquals(50.00, $product1Data['revenue']);
    }

    public function test_generate_daily_report_uses_today_when_date_not_provided(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'created_at' => Carbon::today(),
        ]);

        $report = $this->reportService->generateDailyReport();

        $this->assertEquals(Carbon::today()->format('Y-m-d'), $report['date']);
        $this->assertEquals(2, $report['total_items_sold']);
    }

    public function test_generate_daily_report_excludes_other_dates(): void
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);

        CartItem::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'created_at' => $today,
        ]);

        CartItem::factory()->create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'created_at' => $yesterday,
        ]);

        $report = $this->reportService->generateDailyReport($today);

        $this->assertEquals(2, $report['total_items_sold']);
        $this->assertEquals(20.00, $report['total_revenue']);
    }

    public function test_generate_daily_report_returns_empty_when_no_sales(): void
    {
        $date = Carbon::today();

        $report = $this->reportService->generateDailyReport($date);

        $this->assertEquals($date->format('Y-m-d'), $report['date']);
        $this->assertEquals(0, $report['total_items_sold']);
        $this->assertEquals(0, $report['total_revenue']);
        $this->assertEmpty($report['products']);
    }

    public function test_generate_daily_report_calculates_revenue_correctly(): void
    {
        $date = Carbon::today();
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 15.50]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'created_at' => $date,
        ]);

        $report = $this->reportService->generateDailyReport($date);

        $this->assertEquals(46.50, $report['total_revenue']);
    }

    public function test_generate_daily_report_groups_multiple_items_same_product(): void
    {
        $date = Carbon::today();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);

        CartItem::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'created_at' => $date,
        ]);

        CartItem::factory()->create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'created_at' => $date,
        ]);

        $report = $this->reportService->generateDailyReport($date);

        $this->assertCount(1, $report['products']);
        $productData = $report['products'][0];
        $this->assertEquals(5, $productData['total_units_sold']);
        $this->assertEquals(50.00, $productData['revenue']);
    }
}
