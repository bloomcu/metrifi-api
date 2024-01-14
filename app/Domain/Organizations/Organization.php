<?php

namespace DDD\Domain\Organizations;

// Domains
use DDD\Domain\Connections\Connection;
use DDD\Domain\Base\Organizations\Organization as BaseOrganization;

class Organization extends BaseOrganization {
    /**
     * Connections associated with the organization.
     *
     * @return hasMany
     */
    public function connections()
    {
        return $this->hasMany(Connection::class);
    }
}
