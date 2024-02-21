<?php

namespace DDD\App\Facades\GoogleAnalytics;

use Illuminate\Support\Facades\Facade;

class GoogleAnalyticsData extends Facade
{
   protected static function getFacadeAccessor()
   {
       return 'GoogleAnalyticsDataService';
   }
}