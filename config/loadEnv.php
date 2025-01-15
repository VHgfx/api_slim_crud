<?php // /config/loadEnv.php

function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        throw new Exception(".env file not found at $filePath");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // # Pour commenter
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Split la ligne en key / value
        list($key, $value) = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        // Définition de la variable d'environnement
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

loadEnv(__DIR__ . '/../.env');