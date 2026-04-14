<?php
class Database
{
    private static $instance = null;
    private const HOST       = 'localhost';
    private const DB_NAME    = 'sistema_pos';
    private const USERNAME   = 'root';
    private const PASSWORD   = '';
    private const CHARSET    = 'utf8mb4';
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn     = "mysql:host=" . self::HOST . ";dbname=" . self::DB_NAME . ";charset=" . self::CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            self::$instance = new PDO($dsn, self::USERNAME, self::PASSWORD, $options);
        }
        return self::$instance;
    }
}
