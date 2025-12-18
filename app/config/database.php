<?php
// PDO connection to external PostgreSQL
$host = "84.247.177.25";
$port = "5432";
$db   = "food_app";
$user = "postgres";
$pass = "uni3yweb2";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET timezone = 'UTC'");
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

