<?php
require_once __DIR__ . '/env.php';

return [
    'store_location_name' => trim(fishshop_env('FISHSHOP_STORE_LOCATION_NAME', 'Fish Shop')),
    'store_address' => trim(fishshop_env('FISHSHOP_STORE_ADDRESS')),
    'store_latitude' => trim(fishshop_env('FISHSHOP_STORE_LATITUDE')),
    'store_longitude' => trim(fishshop_env('FISHSHOP_STORE_LONGITUDE')),
    'tile_url' => fishshop_env('FISHSHOP_MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
];
