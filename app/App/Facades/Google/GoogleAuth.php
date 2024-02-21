<?php

namespace DDD\App\Facades\Google;

use Illuminate\Support\Facades\Facade;

class GoogleAuth extends Facade
{
   protected static function getFacadeAccessor()
   {
       return 'GoogleAuthService';
   }
}