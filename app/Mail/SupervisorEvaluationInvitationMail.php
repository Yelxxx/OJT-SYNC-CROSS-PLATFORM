<?php

namespace App\Mail;

use App\Models\Evaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupervisorEvaluationInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Evaluation $evaluation,
        public ?string $customNote = null
    ) {}

    public function envelope(): Envelope
    {
        $studentName = $this->evaluation->student->user->name;
        $companyName = $this->evaluation->jobPosting?->company?->name ?? 'Company';

        return new Envelope(
            subject: "[OJT Sync] Internship Performance Evaluation Request for {$studentName} ({$companyName})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.supervisor-evaluation-invitation',
            with: [
                'evaluationUrl' => route('evaluations.show', $this->evaluation->token),
                'student' => $this->evaluation->student,
                'job' => $this->evaluation->jobPosting,
                'company' => $this->evaluation->jobPosting?->company,
                'customNote' => $this->customNote,
            ]
        );
    }
}
