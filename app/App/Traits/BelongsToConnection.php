<?php

namespace DDD\App\Traits;

use DDD\Domain\Connections\Connection;

trait BelongsToConnection
{
    /**
     * Connection this model belongs to.
     *
     * @return belongsTo
     */
    public function connection()
    {
        return $this->belongsTo(Connection::class);
    }
}
