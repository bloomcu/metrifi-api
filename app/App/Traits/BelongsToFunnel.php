<?php

namespace DDD\App\Traits;

use DDD\Domain\Funnels\Funnel;

trait BelongsToFunnel
{
    /**
     * Funnel this model belongs to.
     *
     * @return belongsTo
     */
    public function funnel()
    {
        return $this->belongsTo(Funnel::class);
    }
}
