<?php

namespace DDD\App\Services\Screenshot;

use DDD\App\Services\Screenshot\ScreenshotInterface;

class ScreenshotOne implements ScreenshotInterface
{
    public function __construct(
        protected string $accesskey,
    ) {}
    
    /**
     * Take a screenshot
     * 
     * Docs: https://screenshotone.com/docs/getting-started/
     * Playground: https://dash.screenshotone.com/playground
     */
    public function take(string $url, string $width = '1200', string $height = '1200')
    {
        return 'https://api.screenshotone.com/take?access_key=' . $this->accesskey . '&url=' . $url . '&full_page=false&viewport_width=' . $width . '&viewport_height=' . $height . '&device_scale_factor=1&format=jpg&image_quality=80&block_ads=true&block_cookie_banners=true&block_banners_by_heuristics=false&block_trackers=true&delay=20&timeout=60';
    }
}
