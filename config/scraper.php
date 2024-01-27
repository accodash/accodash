<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Client and WebDriver settings
     |--------------------------------------------------------------------------
     |
     | Set up the Selenium client and WebDriver properties,
     | like the Selenium server URL and dimensions.
     |
     | * Tested for dimensions: w1920 h1080
     |
     */

    'client' => [
        'host' => 'http://localhost:4444',
        'width' => 1920,
        'height' => 1080,
        'trivago_url' => "https://www.trivago.com/"
    ],

    /*
     |--------------------------------------------------------------------------
     | Crawling settings
     |--------------------------------------------------------------------------
     |
     | Set up how the Crawler works
     |
     */

    'crawler' => [
        'timeouts' => [
            'short' => 2,
            'long' => 10
        ],
        'hotels_per_page' => 30,
        'number_of_photos' => 5
    ],

    /*
     |--------------------------------------------------------------------------
     | Scrapper command settings
     |--------------------------------------------------------------------------
     |
     | Set up how the Crawler works
     |
     */

    'command' => [
        'countries_api' => 'https://restcountries.com/v3.1/name/',
        'default_hotel_quantity' => 100,
        'min_number_of_directories' => 1,
        'min_number_of_files' => 3,
        'scraper_directory' => "./scraper_logs/",
        'buildings_filename' => "/buildings.txt",
        'amenities_filename' => "/amenities.txt",
        'images_filename' => "/images.txt"
    ]
];