<?php


declare(strict_types=1);

$envFile = __DIR__ . '/../.env';

if (!file_exists($envFile)) {
    return;
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {

    //salta commenti
    if (str_starts_with(trim($line), '#')) {
        continue;
    }

    if (!str_contains($line, '=')) {
        continue;
    }

    [$key, $value] = explode('=', $line, 2);

    $key   = trim($key);
    $value = trim($value);

    //rimuovo eventuali virgolette 
    $value = trim($value, '"\'');

    if (!array_key_exists($key, $_ENV)) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}