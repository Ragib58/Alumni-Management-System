<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Analytics cache TTL (seconds)
    |--------------------------------------------------------------------------
    | Dashboard aggregates are cached for this duration. Mutations that affect
    | the numbers (check-ins, payments) call AnalyticsService::flush().
    */
    'cache_ttl' => (int) env('ANALYTICS_CACHE_TTL', 300),

];
