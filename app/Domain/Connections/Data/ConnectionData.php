<?php

namespace DDD\Domain\Connections\Data;

class ConnectionData
{
    public $uid;
    public $token;

    public function __construct(
        $uid = null,
        $token = null
    ) {
        $this->uid = $uid;
        $this->token = $token;
    }
}
