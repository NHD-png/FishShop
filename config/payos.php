<?php
require_once __DIR__ . '/env.php';

return [
    'client_id' => fishshop_env('PAYOS_CLIENT_ID'),
    'api_key' => fishshop_env('PAYOS_API_KEY'),
    'checksum_key' => fishshop_env('PAYOS_CHECKSUM_KEY'),
    'base_url' => rtrim(fishshop_env('FISHSHOP_PUBLIC_URL', 'http://localhost/FishShop'), '/'),
];
