<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;

    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice Mail',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice',
        );
    }

    public function build()
    {
        return $this
            ->subject('Your Domain Order Invoice - ' . $this->invoice->invoice_no)
            ->markdown('emails.invoice')
            ->with([
                'invoice' => $this->invoice,
                'order'   => $this->invoice->order
            ]);
    }

    public function attachments(): array
    {
        return [];
    }
}
