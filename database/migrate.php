<?php
// Menjalankan schema.sql tanpa psql:  php database/migrate.php
declare(strict_types=1);
require __DIR__ . '/../src/Database.php';

$config = require __DIR__ . '/../config/config.php';
$pdo = Database::connect($config);
$pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
echo "Skema berhasil diterapkan ke {$config['db']['name']}.\n";
