<?php
declare(strict_types=1);

/** Koneksi PostgreSQL (PDO). Tidak berisi query bisnis. */
final class Database
{
    public static function connect(array $config): PDO
    {
        $db = $config['db'];
        $pdo = new PDO(
            sprintf('pgsql:host=%s;port=%s;dbname=%s', $db['host'], $db['port'], $db['name']),
            $db['user'],
            $db['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        $pdo->exec("SET TIME ZONE '" . $config['timezone'] . "'");
        return $pdo;
    }
}
