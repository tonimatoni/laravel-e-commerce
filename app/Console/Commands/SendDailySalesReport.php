<?php

namespace App\Console\Commands;

use App\Mail\DailySalesReport;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailySalesReport extends Command
{
    protected $signature = 'sales:report:daily {--date= : Specific date (Y-m-d format)}';

    protected $description = 'Generate and send daily sales report via email';

    public function handle(ReportService $reportService): int
    {
        try {
            $date = $this->option('date')
                ? Carbon::parse($this->option('date'))
                : today();

            $this->info("Generating daily sales report for {$date->format('Y-m-d')}...");

            $reportData = $reportService->generateDailyReport($date);

            if (empty($reportData['products'])) {
                $this->info('No sales data for ' . $date->format('Y-m-d') . '.');
                return Command::SUCCESS;
            }

            $adminEmail = config('mail.admin_email', 'admin@example.com');

            Mail::to($adminEmail)->send(
                new DailySalesReport($reportData)
            );

            $this->info("Daily sales report sent successfully to {$adminEmail}.");

            Log::info('Daily sales report sent', [
                'date' => $reportData['date'],
                'total_items_sold' => $reportData['total_items_sold'],
                'total_revenue' => $reportData['total_revenue'],
                'recipient' => $adminEmail,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to send daily sales report: ' . $e->getMessage());

            Log::error('Daily sales report failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
