<?php
require_once __DIR__ . '/env.php';

// Optional direct OAuth flow. Current Google buttons use Firebase instead.
return [
    'client_id' => fishshop_env('FISHSHOP_GOOGLE_CLIENT_ID'),
    'client_secret' => fishshop_env('FISHSHOP_GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => fishshop_env('FISHSHOP_GOOGLE_REDIRECT_URI', 'http://localhost/FishShop/google_callback.php'),
];
