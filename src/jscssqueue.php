<?php

return [
    /* Scripts and styles to register.
     * 
     * This doesn't actually put them onto pages. You also have to
     * either globally enqueue this using this file or enqueue them
     * before your view is rendered. 
     */
    'register' => [
        'js' => [
            'jquery' => 'https://jquery.com/file.js'
        ],
        'css' => [
            'bootstrap' => [
                'url' => 'bootstrap.css',
                'version' => '1.2'
            ]
        ]
    ],
    /* Enqueue any styles and scripts, by their handle, that should
     * be included on all pages. 
     */
    'enqueue' => [
        'js' => [
            'jquery'
        ],
        'css' => [
            'bootstrap'
        ]
    ]
];
