<?php
namespace App\Core;

class Config {
    public static function init() {
        session_start();
        define('BASE_PATH', __DIR__ . '/../');
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        define('URL_ROOT', $protocol . '://' . $host . $uri . '/');
    }
}