<?php

namespace DDD\App\Services\Scraper;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use DDD\App\Services\Scraper\ScraperInterface;

class HttpScraper implements ScraperInterface
{
    public function __construct() {}
    
    /**
     * Scrape the html content of a webpage
     * 
     */
    public function scrape(string $url)
    {
        $response = Http::get($url);

        try {
            $response = Http::get($url);
            return $response->body();
        } catch (RequestException $e) {
            throw $e; // Rethrow the exception to fail the job
        }
    }
}
