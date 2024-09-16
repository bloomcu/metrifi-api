<?php

namespace DDD\App\Services\Screenshot;

use DDD\App\Services\Screenshot\ScreenshotInterface;

class Thumbio implements ScreenshotInterface
{
    public function __construct(
        protected string $token,
    ) {}
    
    /**
     * Take a screenshot
     * 
     * Docs: https://www.thum.io/documentation/api/url
     * Test URL: https://image.thum.io/get/width/1200/crop/1200/png/noanimate/wait/3/https://www.google.com
     */
    public function take(string $url, string $width = '1200', string $height = '1200')
    {
        return 'https://image.thum.io/get/auth/' . $this->token . '/width/' . $width . '/crop/' . $height . '/png/noanimate/wait/10/' . $url;
    }
}
