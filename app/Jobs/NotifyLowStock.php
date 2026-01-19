<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyLowStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        public Product $product
    ) {}

    public function handle(): void
    {
        $adminEmail = config('mail.admin_email', 'admin@example.com');
        
        \Illuminate\Support\Facades\Mail::to($adminEmail)->send(
            new \App\Mail\LowStockNotification($this->product)
        );
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('NotifyLowStock job failed', [
            'product_id' => $this->product->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
