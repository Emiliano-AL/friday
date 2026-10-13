<?php

namespace App\Mail;

use App\Models\ProjectInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public ProjectInvitation $invitation, public string $token) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->invitation->invitedBy->name} te invitó a {$this->invitation->project->title} en Friday",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.project-invitation',
            with: [
                'inviterName' => $this->invitation->invitedBy->name,
                'projectTitle' => $this->invitation->project->title,
                'validDays' => ProjectInvitation::VALID_DAYS,
                'url' => route('invitations.show', $this->token),
            ],
        );
    }
}
