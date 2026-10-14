<?php

declare(strict_types=1);

$databaseHost = getenv('DB_HOST') ?: 'mysql';
$databasePort = getenv('DB_PORT') ?: '3306';
$databaseName = getenv('DB_DATABASE') ?: 'hris_db';
$databaseUsername = getenv('DB_USERNAME') ?: 'hris_app';
$databasePassword = getenv('DB_PASSWORD') ?: '';

try {
    $connection = new PDO(
        "mysql:host={$databaseHost};port={$databasePort};dbname={$databaseName};charset=utf8mb4",
        $databaseUsername,
        $databasePassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ],
    );

    $connectionStatus = 'Connected';
    $httpStatus = 200;
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $connectionStatus = 'Unavailable';
    $httpStatus = 503;
}

http_response_code($httpStatus);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Native PHP Docker Starter</title>
</head>
<body>
    <main>
        <h1>Native PHP Docker Starter</h1>
        <p>PHP-FPM is running behind Nginx.</p>
        <p>MySQL connection: <strong><?= htmlspecialchars($connectionStatus, ENT_QUOTES, 'UTF-8') ?></strong></p>
        <p>Database: <code><?= htmlspecialchars($databaseName, ENT_QUOTES, 'UTF-8') ?></code></p>
    </main>
</body>
</html>