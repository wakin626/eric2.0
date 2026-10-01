<?php
namespace App\Core;

use PDO;
use PDOException;

class BaseModel {
    /**
     * Canonical inventory site code written by QC, receiving, MRP and MO
     * allocation. Replaces the legacy 'MAIN' placeholder so stock always
     * lands on the same row the MO/MRP screens read from.
     */
    const DEFAULT_SITE_CODE = '001';

    private static $host = 'localhost';
    private static $dbname = 'manufacturing_mgmt';
    private static $username = 'root';
    private static $password = '';
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4";
                self::$connection = new PDO($dsn, self::$username, self::$password);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
        return self::$connection;
    }

    public static function beginTransaction() {
        self::getConnection()->beginTransaction();
    }

    public static function commit() {
        self::getConnection()->commit();
    }

    public static function rollback() {
        self::getConnection()->rollBack();
    }
}