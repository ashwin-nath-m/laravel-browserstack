<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | Your username and access key for BrowserStack.
    | https://www.browserstack.com/accounts/settings
    |
    */

    'username' => env('BROWSERSTACK_USERNAME'),

    'key' => env('BROWSERSTACK_ACCESS_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Browser
    |--------------------------------------------------------------------------
    |
    | The browser slug to run on BrowserStack.
    |
    */

    'browser' => env('BROWSERSTACK_BROWSER'),

    /*
    |--------------------------------------------------------------------------
    | Session
    |--------------------------------------------------------------------------
    |
    | Configuration to make BrowserStack run each tests in a different
    | session.
    |
    */

    'separate_sessions' => env('BROWSERSTACK_SEPARATE_SESSIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Capabilities
    |--------------------------------------------------------------------------
    |
    | The configuration for capabilities of the browser.
    | https://www.browserstack.com/automate/capabilities
    |
    */

    'capabilities' => [

        'acceptSslCerts' => env('BROWSERSTACK_ACCEPT_SSL', true),

        'browserstack.local' => env('BROWSERSTACK_LOCAL_TUNNEL', true),

        'browserstack.console' => env('BROWSERSTACK_CONSOLE', 'verbose'),

        'browserstack.timezone' => env('BROWSERSTACK_TIMEZONE', config('app.timezone')),

        'resolution' => env('BROWSERSTACK_RESOLUTION', '1920x1080'),

    ],
];
