<?php
require_once __DIR__ . '/../../core/Config.php';
\App\Core\Config::init();

spl_autoload_register(function ($class) {
    $paths = [
        'C:\\xampp\\htdocs\\order-billing-system\\core' => 'App\\Core',
        'C:\\xampp\\htdocs\\order-billing-system\\app\\helpers' => 'App\\Helpers',
        'C:\\xampp\\htdocs\\order-billing-system\\admin\\models' => 'App\\Models',
        'C:\\xampp\\htdocs\\order-billing-system\\warehouse\\models' => 'App\\Models',
        'C:\\xampp\\htdocs\\order-billing-system\\qc\\models' => 'App\\Models'
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

header('Content-Type: application/json');

try {
    $payload = $_POST;
    if (empty($payload)) {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
    }

    $qcModel = new \App\Models\QcModel();
    $result = $qcModel->recordQcInspection($payload);
    echo json_encode([
        'success' => true,
        'result' => $result
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
