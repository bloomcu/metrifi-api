<?php

namespace DDD\Domain\Analyses\Enums;

enum AnalysisIssueEnum: string
{
    case UNEQUAL_STEPS = 'unequal_steps';
    // case OtherIssue = 'code';

    public function message(): string
    {
        return match ($this) {
            static::UNEQUAL_STEPS => 'One or more funnels do not have the same number of steps.',
            // static::OtherIssue => 'Other issue message',
            // default => ''
        };
    }
}
