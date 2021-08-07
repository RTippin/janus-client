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
    'backend_ssl' => env('JANUS_BACKEND_SSL', true),
    'admin_secret' => env('JANUS_ADMIN_SECRET'),
    'api_secret' => env('JANUS_API_SECRET'),
    'video_room_secret' => env('JANUS_VIDEO_ROOM_SECRET'),
];
