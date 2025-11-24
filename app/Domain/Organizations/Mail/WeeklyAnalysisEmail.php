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
        // Refresh the organization model to ensure we have the latest assets data
        // This prevents race conditions where assets are updated after the job is dispatched
        $this->organization->refresh();
        
        $organizationPotentialRounded = round($this->organization->assets['median']['potential']); 
        $organizationPotentialAnnualized = bcmul($organizationPotentialRounded, 13.04, 2); 
        $figure = number_format($organizationPotentialAnnualized);

        return $this->subject('Intel: ' . $this->organization->domain . ' missing $' . $figure . ' per year')
            ->view('emails.weeklyAnalysis');
    }
}
