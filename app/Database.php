<?php
// app/Database.php
declare(strict_types=1);

class Database {
    private static ?PDO $pdo = null;

    public static function pdo(): PDO {
        if (self::$pdo === null) {
            // Use the db() function from config.php
            self::$pdo = db();
        }
        return self::$pdo;
    }
}
