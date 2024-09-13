<?php

namespace DDD\App\Services\Scraper;

interface ScraperInterface
{
    public function scrape(
        string $url,
    );
}
