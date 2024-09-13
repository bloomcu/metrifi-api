<?php

namespace DDD\App\Services\Favicon;

use DDD\App\Services\Favicon\FaviconInterface;

class IconHorse implements FaviconInterface
{
    /**
     * Get favicon from icon.horse
     * 
     * Docs: https://icon.horse/usage
     * Test URL: https://icon.horse/icon/vetframe.com
     */
    public function take(string $url, string $size = 'small')
    {
        return 'https://icon.horse/icon/' . $url;
    }
}
