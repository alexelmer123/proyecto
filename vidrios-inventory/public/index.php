<?php
declare(strict_types=1);

session_start();

define('ROOT', dirname(__DIR__));

require ROOT . '/config/config.php';
require ROOT . '/config/database.php';

require ROOT . '/core/Database.php';
require ROOT . '/core/Model.php';
require ROOT . '/core/Controller.php';
require ROOT . '/core/Router.php';
require ROOT . '/core/Paginator.php';
require ROOT . '/core/Exporter.php';

spl_autoload_register(function (string $class): void {
    foreach (['app/controllers', 'app/models'] as $dir) {
        $file = ROOT . '/' . $dir . '/' . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

$url = isset($_GET['url']) ? trim((string) $_GET['url'], '/') : '';

$router = new Router();
$router->dispatch($url);
