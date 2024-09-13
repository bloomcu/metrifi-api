<?php

namespace DDD\App\Services\Screenshot;

interface ScreenshotInterface
{
    public function take(
        string $url,
        string $width,
        string $height,
    );
}
