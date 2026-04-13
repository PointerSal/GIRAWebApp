<?php
// ============================================================
//  app/Core/Database.php
// ============================================================
class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // Offset dinamico: +02:00 ora legale (mar-ott), +01:00 ora solare
            $offset = (new DateTimeZone('Europe/Rome'))
                ->getOffset(new DateTime('now', new DateTimeZone('UTC')));
            $hours  = sprintf('%+03d:%02d', intdiv($offset, 3600), abs($offset % 3600) / 60);
            self::$instance->exec("SET time_zone = '$hours'");
        }
        return self::$instance;
    }
}
