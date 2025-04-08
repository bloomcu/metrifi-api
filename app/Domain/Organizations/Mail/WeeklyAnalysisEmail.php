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
        $organizationPotentialRounded = round($this->organization->assets['median']['potential']); 
        $organizationPotentialAnnualized = bcmul($organizationPotentialRounded, 13.04, 2); 
        $figure = number_format($organizationPotentialAnnualized);

        return $this->subject('Website update: You\'re losing out on $' . $figure . ' per year')
            ->view('emails.weeklyAnalysis');
    }
}
