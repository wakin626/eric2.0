<?php
require_once __DIR__ . '/core/Config.php';
\App\Core\Config::init();

spl_autoload_register(function ($class) {
    $paths = [
        'C:\xampp\htdocs\eric2.0\core' => 'App\Core',
        'C:\xampp\htdocs\eric2.0\app\helpers' => 'App\Helpers',
        'C:\xampp\htdocs\eric2.0\admin\controllers' => 'App\Controllers',
        'C:\xampp\htdocs\eric2.0\admin\models' => 'App\Models',
        'C:\xampp\htdocs\eric2.0\auth\controllers' => 'App\Controllers',
        'C:\xampp\htdocs\eric2.0\auth\models' => 'App\Models',
        'C:\xampp\htdocs\eric2.0\warehouse\controllers' => 'App\Controllers',
        'C:\xampp\htdocs\eric2.0\warehouse\models' => 'App\Models',
        'C:\xampp\htdocs\eric2.0\production\controllers' => 'App\Controllers',
        'C:\xampp\htdocs\eric2.0\production\models' => 'App\Models',
        'C:\xampp\htdocs\eric2.0\finance\controllers' => 'App\Controllers',
        'C:\xampp\htdocs\eric2.0\finance\models' => 'App\Models'
    ];
    
    foreach ($paths as $baseDir => $prefix) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});

$controller = $_GET['controller'] ?? 'auth';
$action = $_GET['action'] ?? 'login';

$authControllers = ['auth'];
$warehouseControllers = ['warehouse'];
$productionControllers = ['production'];
$financeControllers = ['finance'];

if (in_array($controller, $authControllers)) {
    $controllerFile = __DIR__ . "/auth/controllers/{$controller}Controller.php";
} elseif (in_array($controller, $warehouseControllers)) {
    $controllerFile = __DIR__ . "/warehouse/controllers/{$controller}Controller.php";
} elseif (in_array($controller, $productionControllers)) {
    $controllerFile = __DIR__ . "/production/controllers/{$controller}Controller.php";
} elseif (in_array($controller, $financeControllers)) {
    $controllerFile = __DIR__ . "/finance/controllers/{$controller}Controller.php";
} else {
    $controllerFile = __DIR__ . "/admin/controllers/{$controller}Controller.php";
}

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $className = "App\\Controllers\\" . ucfirst($controller) . "Controller";
    $instance = new $className();
    if (method_exists($instance, $action)) {
        $instance->$action();
    } else {
        $instance->index();
    }
} else {
    echo "<h1>404 - Controller Not Found</h1>";
}