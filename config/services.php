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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'places_api_key' => env('GOOGLE_PLACES_API_KEY'),
    ],

    'geo' => [
        'driver' => env('GEO_DRIVER', 'osm'),
    ],

    'places' => [
        'driver' => env('PLACES_DRIVER', 'osm'),
    ],

    'osrm' => [
        'base_url' => env('OSRM_BASE_URL', 'https://router.project-osrm.org'),
    ],

    'tesseract' => [
        // No portable default across Windows/Linux - set explicitly per environment.
        // Windows (winget UB-Mannheim build): C:\Program Files\Tesseract-OCR\tesseract.exe
        // Debian/Docker (apt tesseract-ocr package): /usr/bin/tesseract
        'binary' => env('TESSERACT_BINARY'),
        // Directory containing *.traineddata files (needs at least deu.traineddata for
        // German menus). Leave unset on Linux where `apt install tesseract-ocr-deu`
        // already places it on Tesseract's own default search path; on Windows this
        // needs to point at a writable project-local copy (see README), since the
        // system install's own tessdata folder isn't writable without admin rights.
        'tessdata_dir' => env('TESSERACT_TESSDATA_DIR'),
    ],

];
