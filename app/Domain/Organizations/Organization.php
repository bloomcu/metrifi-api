<?php

namespace DDD\Domain\Organizations;

// Domains
use DDD\Domain\Integrations\Integration;
use DDD\Domain\Base\Organizations\Organization as BaseOrganization;

class Organization extends BaseOrganization {
    /**
     * Integrations associated with the organization.
     *
     * @return hasMany
     */
    public function integrations()
    {
        return $this->hasMany(Integration::class);
    }
}
