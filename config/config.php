<?php
/**
 * Application Configuration & Environment Loader
 */

function load_project_env(): void {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    $envFile = dirname(__DIR__) . '/.env';
    if (!is_readable($envFile)) {
        $envFile = dirname(__DIR__) . '/.env.example';
    }
    if (!is_readable($envFile)) return;

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '' || getenv($key) !== false) continue;
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

function env_value(string $key, string $default = ''): string {
    load_project_env();
    $value = getenv($key);
    return $value === false ? $default : $value;
}
