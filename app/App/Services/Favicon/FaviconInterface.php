<?php

namespace DDD\App\Services\Favicon;

interface FaviconInterface
{
    public function take(
        string $url,
        string $size,
    );
}
