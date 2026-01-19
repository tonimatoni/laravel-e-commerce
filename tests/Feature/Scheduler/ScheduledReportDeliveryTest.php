<?php

namespace Tests\Feature\Scheduler;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class ScheduledReportDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_is_registered_in_scheduler(): void
    {
        $events = Schedule::events();
        $commandEvents = array_filter($events, function ($event) {
            return str_contains($event->command ?? '', 'sales:report:daily');
        });

        $this->assertNotEmpty($commandEvents, 'sales:report:daily command should be registered in scheduler');
    }

    public function test_scheduler_runs_command_daily_at_2359(): void
    {
        $events = Schedule::events();
        $commandEvent = collect($events)->first(function ($event) {
            return str_contains($event->command ?? '', 'sales:report:daily');
        });

        $this->assertNotNull($commandEvent, 'Command should be scheduled');

        $expression = $commandEvent->expression;
        $this->assertStringContainsString('59', $expression, 'Should run at 59 minutes');
        $this->assertStringContainsString('23', $expression, 'Should run at 23 hours');
    }

    public function test_scheduled_command_generates_report(): void
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

    public function test_scheduled_command_sends_email_automatically(): void
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
            return $mail->hasTo(config('mail.admin_email', 'admin@example.com'));
        });
    }

    public function test_scheduler_prevents_overlapping_executions(): void
    {
        $events = Schedule::events();
        $commandEvent = collect($events)->first(function ($event) {
            return str_contains($event->command ?? '', 'sales:report:daily');
        });

        $this->assertNotNull($commandEvent, 'Command should be scheduled');

        $reflection = new \ReflectionClass($commandEvent);
        $property = $reflection->getProperty('withoutOverlapping');
        $property->setAccessible(true);
        $withoutOverlapping = $property->getValue($commandEvent);

        $this->assertTrue($withoutOverlapping, 'Command should prevent overlapping executions');
    }

    public function test_scheduler_runs_in_background(): void
    {
        $events = Schedule::events();
        $commandEvent = collect($events)->first(function ($event) {
            return str_contains($event->command ?? '', 'sales:report:daily');
        });

        $this->assertNotNull($commandEvent, 'Command should be scheduled');

        $reflection = new \ReflectionClass($commandEvent);
        $property = $reflection->getProperty('runInBackground');
        $property->setAccessible(true);
        $runInBackground = $property->getValue($commandEvent);

        $this->assertTrue($runInBackground, 'Command should run in background');
    }
}
