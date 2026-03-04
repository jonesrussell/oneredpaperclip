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

    'description' => env('SEO_DESCRIPTION', 'Trade up from one thing to something better. Start a challenge, get offers, and see how far you can go.'),

    /*
    |--------------------------------------------------------------------------
    | Default meta title (50-60 chars for social)
    |--------------------------------------------------------------------------
    |
    | Used when a page does not provide its own title (meta and Open Graph).
    |
    */

    'default_title' => env('SEO_TITLE', 'One Red Paperclip — Trade up from one thing to something better'),

    /*
    |--------------------------------------------------------------------------
    | Default Open Graph / Twitter image
    |--------------------------------------------------------------------------
    |
    | Full URL to a default image for social sharing (e.g. 1200x630). When null,
    | the app uses default_og_image_path (absolute URL).
    |
    */

    'og_image' => env('OG_IMAGE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Default OG image path (when og_image is null)
    |--------------------------------------------------------------------------
    |
    | Path relative to public/ for the default OG image (1200x630). Used to
    | build an absolute URL when OG_IMAGE_URL is not set.
    |
    */

    'default_og_image_path' => 'images/og-default.png',

    /*
    |--------------------------------------------------------------------------
    | Twitter handles
    |--------------------------------------------------------------------------
    |
    | Twitter @username for the site and content creator. Used in Twitter Card
    | meta tags (twitter:site and twitter:creator).
    |
    */

    'twitter_site' => env('TWITTER_SITE'),

    'twitter_creator' => env('TWITTER_CREATOR'),

];
