<?php

namespace DDD\App\Facades\GoogleAnalytics;

use Illuminate\Support\Facades\Facade;

class GoogleAnalyticsAdmin extends Facade
{
   protected static function getFacadeAccessor()
   {
       return 'GoogleAnalyticsAdminService';
   }
}