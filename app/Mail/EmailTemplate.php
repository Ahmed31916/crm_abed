<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailTemplate extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $content;
    public $fromEmail;
    public $fromName;

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $content, $fromEmail, $fromName)
    {
        $this->subject = $subject;
        $this->content = $content;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject($this->subject)
                    ->from($this->fromEmail, $this->fromName)
                    ->view('emails.notification')
                    ->with([
                        'subject' => $this->subject,
                        'content' => $this->content,
                    ]);
    }
}
