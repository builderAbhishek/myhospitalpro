<?php
/**
 * Database Connection via PDO (MySQL Dedicated)
 * Centralized, Prepared Statements, UTF8mb4, Exception mode
 */

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
            $port = defined('DB_PORT') ? DB_PORT : '3306';
            $name = defined('DB_NAME') ? DB_NAME : 'mhp_database';
            $user = defined('DB_USER') ? DB_USER : 'root';
            $pass = defined('DB_PASS') ? DB_PASS : '';
            $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                die("<div style='font-family:sans-serif;padding:30px;background:#fef2f2;border:1px solid #f87171;color:#991b1b;border-radius:8px;max-width:600px;margin:50px auto;'>"
                    . "<h3 style='margin-top:0;'>Database Connection Error</h3>"
                    . "<p>Unable to connect to MySQL database <strong>" . htmlspecialchars($name) . "</strong>.</p>"
                    . "<p style='font-size:13px;color:#7f1d1d;'>Please ensure MySQL is running on <strong>{$host}:{$port}</strong> and the database has been imported from <code>database/mhp_database.sql</code>.</p>"
                    . "<p style='font-size:12px;color:#991b1b;'>Detail: " . htmlspecialchars($e->getMessage()) . "</p>"
                    . "</div>");
            }
        }
        return self::$instance;
    }
}

function get_db(): PDO {
    return Database::getInstance();
}
