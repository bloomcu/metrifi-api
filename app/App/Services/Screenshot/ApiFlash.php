<?php

namespace DDD\App\Services\Screenshot;

use DDD\App\Services\Screenshot\ScreenshotInterface;

class ApiFlash implements ScreenshotInterface
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
    public function getScreenshot(
        string $url, 
        string $wait = '0',
        string $width = '1400', 
        string $height = '1400'
    ){
        return 'https://api.apiflash.com/v1/urltoimage?access_key=' . $this->accesskey . '&url=https://' . $url . '&format=jpeg&width=' . $width . '&fresh=true&full_page=true&delay=5&scroll_page=true&response_type=image&no_cookie_banners=true&no_ads=true&no_tracking=true&wait_until=page_loaded';
    }
}
