<?php
require_once __DIR__ . '/env.php';

return [
    'cloud_name' => trim(fishshop_env('CLOUDINARY_CLOUD_NAME')),
    'api_key' => trim(fishshop_env('CLOUDINARY_API_KEY')),
    'api_secret' => trim(fishshop_env('CLOUDINARY_API_SECRET')),
    'folder' => trim(fishshop_env('CLOUDINARY_FOLDER', 'fishshop'), '/ '),
];
