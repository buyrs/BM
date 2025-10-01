<?php

namespace App\Mail;

use App\Models\Checklist;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestChecklistMail extends Mailable
{
    use Queueable, SerializesModels;

    public Checklist $checklist;
    public string $guestUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Checklist $checklist, string $guestUrl)
    {
        $this->checklist = $checklist;
        $this->guestUrl = $guestUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Checklist for Bail Mobilité Mission',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.guest-checklist',
            with: [
                'checklist' => $this->checklist,
                'guestUrl' => $this->guestUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
