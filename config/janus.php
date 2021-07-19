<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Janus Server Configurations
    |--------------------------------------------------------------------------
    |
    */
    'server_endpoint' => env('JANUS_SERVER_ENDPOINT'),
    'server_admin_endpoint' => env('JANUS_SERVER_ADMIN_ENDPOINT'),
    'server_public_admin_endpoint' => env('JANUS_PUBLIC_SERVER_ADMIN_ENDPOINT'),
    'backend_ssl' => env('JANUS_BACKEND_SSL', true),
    'log_failures' => env('JANUS_LOG_ERRORS', true),
    'backend_debug' => env('JANUS_BACKEND_DEBUG', false),
    'admin_secret' => env('JANUS_ADMIN_SECRET'),
    'api_secret' => env('JANUS_API_SECRET'),
    'video_room_secret' => env('JANUS_VIDEO_ROOM_SECRET'),

    // Frontend servers / ice servers
    // This will set the config for our
    // janus server should you use it

    'main_servers' => [
        //"wss://example.com/janus-ws",
        //"https://example.com/janus",
    ],
    'ice_servers' => [
        //        [
        //            'urls' => 'stun:example.com:5349',
        //            'username' => 'user',
        //            'credential' => 'password'
        //        ],
    ],
];
