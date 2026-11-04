<?php
require_once __DIR__ . '/env.php';

// Public Firebase Web config is read from .env. Never expose service-account keys here.
return [
    'apiKey' => fishshop_env('FISHSHOP_FIREBASE_API_KEY'),
    'authDomain' => fishshop_env('FISHSHOP_FIREBASE_AUTH_DOMAIN'),
    'projectId' => fishshop_env('FISHSHOP_FIREBASE_PROJECT_ID'),
    'storageBucket' => fishshop_env('FISHSHOP_FIREBASE_STORAGE_BUCKET'),
    'messagingSenderId' => fishshop_env('FISHSHOP_FIREBASE_MESSAGING_SENDER_ID'),
    'appId' => fishshop_env('FISHSHOP_FIREBASE_APP_ID'),
    'measurementId' => fishshop_env('FISHSHOP_FIREBASE_MEASUREMENT_ID'),
];
