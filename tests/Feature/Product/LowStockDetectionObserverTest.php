<?php

namespace Tests\Feature\Product;

use App\Jobs\NotifyLowStock;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LowStockDetectionObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_observer_dispatches_job_when_stock_falls_below_threshold(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
        ]);

        $product->update([
            'stock_quantity' => 3,
        ]);

        Queue::assertPushed(NotifyLowStock::class, function ($job) use ($product) {
            return $job->product->id === $product->id;
        });
    }

    public function test_observer_does_not_dispatch_job_when_stock_increases(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 3,
        ]);

        $product->update([
            'stock_quantity' => 10,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_observer_does_not_dispatch_job_when_stock_was_already_below_threshold(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 3,
        ]);

        $product->update([
            'stock_quantity' => 2,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_observer_does_not_dispatch_job_when_stock_quantity_unchanged(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'name' => 'Original Name',
        ]);

        $product->update([
            'name' => 'Updated Name',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_observer_does_not_dispatch_job_when_stock_is_zero(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
        ]);

        $product->update([
            'stock_quantity' => 0,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_observer_dispatches_job_at_exact_threshold(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
        ]);

        $product->update([
            'stock_quantity' => 5,
        ]);

        Queue::assertPushed(NotifyLowStock::class);
    }

    public function test_observer_uses_config_threshold(): void
    {
        config(['inventory.low_stock_threshold' => 10]);

        $product = Product::factory()->create([
            'stock_quantity' => 15,
        ]);

        $product->update([
            'stock_quantity' => 8,
        ]);

        Queue::assertPushed(NotifyLowStock::class);
    }
}
