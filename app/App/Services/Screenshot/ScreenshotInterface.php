<?php

namespace DDD\App\Services\Screenshot;

interface ScreenshotInterface
{
    public function getScreenshot(
        string $url,
        string $wait = '0',
        string $width = '1200',
        string $height = '1200',
    );
}