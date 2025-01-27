<?php

namespace DDD\Domain\Organizations\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyAnalysisEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $period;
    public $organization;
    public $dashboards;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($period, $organization, $dashboards)
    {
        $this->period = $period;
        $this->organization = $organization;
        $this->dashboards = $dashboards;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Weekly website analysis for ' . $this->organization->domain)
            ->view('emails.weeklyAnalysis');
    }
}
