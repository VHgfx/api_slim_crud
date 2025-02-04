<?php
# /config/loadEnv.php

function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        throw new Exception(".env file not found at $filePath");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

loadEnv(__DIR__ . '/.env');

// Determine environement ('local', 'dev', 'prod')
$env = getenv('APP_ENV') ?: 'dev';

$envFile = __DIR__ . "/.env.$env";
if (file_exists($envFile)) {
    loadEnv($envFile);
}

// Facultatif : Si .env.local existe, override tout le reste
$localEnvFile = __DIR__ . '/../.env.local';
if (file_exists($localEnvFile)) {
    loadEnv($localEnvFile);
}