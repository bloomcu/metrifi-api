<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'apify' => [
        'token' => env('APIFY_TOKEN'),
        'cheerioActor' => env('APIFY_CHEERIO_ACTOR'),
        'puppeteerActor' => env('APIFY_PUPPETEER_ACTOR'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'project_id' => env('GOOGLE_PROJECT_ID'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        'javascript_origins' => env('GOOGLE_JAVASCRIPT_ORIGINS'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
    ],

    'grok' => [
        'api_key' => env('GROK_API_KEY'),
    ],

    'magicpatterns' => [
        'api_key' => env('MAGICPATTERNS_API_KEY'),
    ],

    'thumbio' => [
        'token' => env('THUMBIO_TOKEN'),
    ],

    'screenshotone' => [
        'accesskey' => env('SCREENSHOTONE_ACCESSKEY'),
    ],

    'apiflash' => [
        'accesskey' => env('APIFLASH_ACCESSKEY'),
    ],
];
