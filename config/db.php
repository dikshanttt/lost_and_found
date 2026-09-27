<?php
/** CivicFind configuration and database connection. */

function load_project_env(): void {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    $envFile = dirname(__DIR__) . '/.env';
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

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = env_value('DB_HOST', 'localhost');
        $db   = env_value('DB_NAME', 'lost_found_hub');
        $user = env_value('DB_USER', 'root');
        $pass = env_value('DB_PASSWORD');
        $dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $pdo  = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
