<?php

namespace DDD\Domain\Organizations\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrganizationRegisteredEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $organization;
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($organization, $user)
    {
        $this->organization = $organization;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('A new organization has registered on MetriFi')
            ->view('emails.organizationRegistered');
    }
}
