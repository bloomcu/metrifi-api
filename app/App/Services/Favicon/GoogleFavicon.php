<?php

namespace DDD\App\Services\Favicon;

use DDD\App\Services\Favicon\FaviconInterface;

class GoogleFavicon implements FaviconInterface
{
    /**
     * Get favicon from Google
     * 
     * Source: https://dev.to/derlin/get-favicons-from-any-website-using-a-hidden-google-api-3p1e
     * Test URL: https://www.google.com/s2/favicons?domain=https://vetframe.com&sz=96
     */
    public function take(string $url, string $size = '96')
    {
        return 'https://www.google.com/s2/favicons?domain=' . $url . '&sz=' . $size;
    }
}
