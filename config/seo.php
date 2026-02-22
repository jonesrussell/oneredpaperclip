<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default meta description
    |--------------------------------------------------------------------------
    |
    | Used when a page does not provide its own description (meta and Open Graph).
    |
    */

    'description' => env('SEO_DESCRIPTION', 'Trade up from one thing to something better. Start a campaign, get offers, and see how far you can go.'),

    /*
    |--------------------------------------------------------------------------
    | Default Open Graph / Twitter image
    |--------------------------------------------------------------------------
    |
    | Full URL to a default image for social sharing (e.g. 1200x630). When null,
    | falls back to the app URL plus /favicon.svg.
    |
    */

    'og_image' => env('OG_IMAGE_URL'),

];
