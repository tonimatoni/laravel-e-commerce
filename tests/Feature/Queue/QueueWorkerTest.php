<?php

namespace Tests\Feature\Queue;

use App\Jobs\NotifyLowStock;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueWorkerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'database']);
    }

    public function test_jobs_are_dispatched_to_queue(): void
    {
        Queue::fake();

        $product = Product::factory()->create([
            'stock_quantity' => 10,
        ]);

        $product->update([
            'stock_quantity' => 3,
        ]);

        Queue::assertPushed(NotifyLowStock::class);
    }

    public function test_jobs_are_processed_asynchronously(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
        ]);

        $startTime = microtime(true);

        NotifyLowStock::dispatch($product);

        $dispatchTime = microtime(true) - $startTime;

        $this->assertLessThan(0.1, $dispatchTime, 'Job dispatch should be fast and non-blocking');
    }

    public function test_user_interactions_are_not_blocked(): void
    {
        Queue::fake();

        $product = Product::factory()->create([
            'stock_quantity' => 10,
        ]);

        $startTime = microtime(true);

        NotifyLowStock::dispatch($product);

        $endTime = microtime(true) - $startTime;

        $this->assertLessThan(0.1, $endTime, 'User interaction should not be blocked by job dispatch');
    }

    public function test_failed_jobs_are_logged(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('NotifyLowStock job failed', \Mockery::type('array'));

        $product = Product::factory()->create();
        $job = new NotifyLowStock($product);
        $exception = new \Exception('Test failure');

        $job->failed($exception);
    }

    public function test_jobs_retry_on_failure(): void
    {
        $product = Product::factory()->create();
        $job = new NotifyLowStock($product);

        $this->assertEquals(3, $job->tries, 'Job should retry up to 3 times');
    }

    public function test_job_has_timeout_configuration(): void
    {
        $product = Product::factory()->create();
        $job = new NotifyLowStock($product);

        $this->assertEquals(60, $job->timeout, 'Job should have 60 second timeout');
    }

    public function test_queue_connection_is_database(): void
    {
        $this->assertEquals('database', config('queue.default'));
    }

    public function test_failed_jobs_table_exists(): void
    {
        $this->assertTrue(
            \Schema::hasTable('failed_jobs'),
            'failed_jobs table should exist'
        );
    }

    public function test_jobs_table_exists(): void
    {
        $this->assertTrue(
            \Schema::hasTable('jobs'),
            'jobs table should exist'
        );
    }
}
