<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailySalesReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $reportData
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Daily Sales Report - ' . $this->reportData['date'],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.daily-sales-report',
            with: $this->reportData,
        );
    }
}
