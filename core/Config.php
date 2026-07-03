<?php
namespace App\Core;

class Config {
    public static function init() {
        session_start();
        define('BASE_PATH', __DIR__ . '/../');
        define('URL_ROOT', 'http://localhost/order-billing-system/');
    }
}