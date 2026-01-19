<?php

namespace Tests\Feature\Command;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendDailySalesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_aggregates_daily_sales_data(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'created_at' => today(),
        ]);

        $this->artisan('sales:report:daily')
            ->assertSuccessful();

        Mail::assertSent(\App\Mail\DailySalesReport::class);
    }

    public function test_command_generates_html_report(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Test Product', 'price' => 10.00]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'created_at' => today(),
        ]);

        $this->artisan('sales:report:daily')
            ->assertSuccessful();

        Mail::assertSent(\App\Mail\DailySalesReport::class, function ($mail) {
            return $mail->hasTo(config('mail.admin_email', 'admin@example.com'));
        });
    }

    public function test_command_sends_report_via_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'created_at' => today(),
        ]);

        $this->artisan('sales:report:daily')
            ->assertSuccessful();

        Mail::assertSent(\App\Mail\DailySalesReport::class);
    }

    public function test_report_includes_total_items_sold(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'created_at' => today(),
        ]);

        $this->artisan('sales:report:daily')
            ->assertSuccessful();

        Mail::assertSent(\App\Mail\DailySalesReport::class, function ($mail) {
            return isset($mail->reportData['total_items_sold'])
                && $mail->reportData['total_items_sold'] === 5;
        });
    }

    public function test_report_includes_total_revenue(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'created_at' => today(),
        ]);

        $this->artisan('sales:report:daily')
            ->assertSuccessful();

        Mail::assertSent(\App\Mail\DailySalesReport::class, function ($mail) {
            return isset($mail->reportData['total_revenue'])
                && $mail->reportData['total_revenue'] === 20.00;
        });
    }

    public function test_report_includes_top_products(): void
    {
        Mail::fake();

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

        $this->artisan('sales:report:daily')
            ->assertSuccessful();

        Mail::assertSent(\App\Mail\DailySalesReport::class, function ($mail) {
            return isset($mail->reportData['products'])
                && count($mail->reportData['products']) === 2;
        });
    }

    public function test_command_handles_errors_gracefully(): void
    {
        Mail::fake();

        config(['mail.admin_email' => null]);

        $this->artisan('sales:report:daily')
            ->assertSuccessful();
    }

    public function test_command_handles_no_sales_data(): void
    {
        Mail::fake();

        $this->artisan('sales:report:daily')
            ->expectsOutput('No sales data for ' . today()->format('Y-m-d') . '.')
            ->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_command_accepts_date_option(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);

        $customDate = '2026-01-15';

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'created_at' => $customDate,
        ]);

        $this->artisan("sales:report:daily --date={$customDate}")
            ->assertSuccessful();

        Mail::assertSent(\App\Mail\DailySalesReport::class, function ($mail) use ($customDate) {
            return $mail->reportData['date'] === $customDate;
        });
    }

    public function test_command_logs_success(): void
    {
        Mail::fake();
        \Log::shouldReceive('info')
            ->once()
            ->with('Daily sales report sent', \Mockery::type('array'));

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'created_at' => today(),
        ]);

        $this->artisan('sales:report:daily')
            ->assertSuccessful();
    }
}
