<?php
return [

    'url' => env('MEDIA_URL'),

    'signkey' => env('MEDIA_KEY'),

    'folder' => 'media',

    'placeholder' => 'default',

    'defaults' => [
        'q' => 100,
        'w' => 1800,
        'fit' => 'max',
    ],

    'presets' => [
        'main' => ['w' => 800, 'h' => null, 'fit' => 'contain'],
        'thumb' => ['w' => 350, 'h' => 350, 'fit' => 'crop'],
    ],

];
