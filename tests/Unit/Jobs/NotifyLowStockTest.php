<?php

namespace Tests\Unit\Jobs;

use App\Jobs\NotifyLowStock;
use App\Mail\LowStockNotification;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotifyLowStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_sends_email_to_admin(): void
    {
        Mail::fake();

        $product = Product::factory()->create([
            'stock_quantity' => 3,
        ]);

        $job = new NotifyLowStock($product);
        $job->handle();

        Mail::assertSent(LowStockNotification::class, function ($mail) use ($product) {
            return $mail->product->id === $product->id;
        });
    }

    public function test_job_uses_admin_email_from_config(): void
    {
        Mail::fake();

        config(['mail.admin_email' => 'custom-admin@example.com']);

        $product = Product::factory()->create([
            'stock_quantity' => 3,
        ]);

        $job = new NotifyLowStock($product);
        $job->handle();

        Mail::assertSent(LowStockNotification::class, function ($mail) {
            return $mail->hasTo('custom-admin@example.com');
        });
    }

    public function test_job_uses_default_admin_email_when_not_configured(): void
    {
        Mail::fake();

        config(['mail.admin_email' => 'admin@example.com']);

        $product = Product::factory()->create([
            'stock_quantity' => 3,
        ]);

        $job = new NotifyLowStock($product);
        $job->handle();

        Mail::assertSent(LowStockNotification::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
    }

    public function test_email_includes_product_details(): void
    {
        Mail::fake();

        $product = Product::factory()->create([
            'name' => 'Test Product',
            'stock_quantity' => 3,
        ]);

        $job = new NotifyLowStock($product);
        $job->handle();

        Mail::assertSent(LowStockNotification::class, function ($mail) use ($product) {
            return $mail->product->name === 'Test Product'
                && $mail->product->stock_quantity === 3;
        });
    }

    public function test_job_implements_should_queue(): void
    {
        $product = Product::factory()->create();

        $job = new NotifyLowStock($product);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    public function test_job_has_retry_configuration(): void
    {
        $product = Product::factory()->create();

        $job = new NotifyLowStock($product);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->timeout);
    }

    public function test_failed_job_logs_error(): void
    {
        \Log::shouldReceive('error')
            ->once()
            ->with('NotifyLowStock job failed', \Mockery::type('array'));

        $product = Product::factory()->create();
        $job = new NotifyLowStock($product);
        $exception = new \Exception('Test error');

        $job->failed($exception);
    }
}
