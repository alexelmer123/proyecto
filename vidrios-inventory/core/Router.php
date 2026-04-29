<?php
declare(strict_types=1);

/**
 * Router — parsea /controlador/accion/parametros y despacha.
 * Ruta por defecto: ProductoController@index
 */
final class Router
{
    private string $defaultController = 'DashboardController';
    private string $defaultAction     = 'index';

    public function dispatch(string $url): void
    {
        $segments = $url === '' ? [] : array_values(array_filter(explode('/', $url), 'strlen'));

        $controllerName = isset($segments[0])
            ? $this->studly($segments[0]) . 'Controller'
            : $this->defaultController;

        $action = isset($segments[1]) ? $this->camel($segments[1]) : $this->defaultAction;
        $params = array_slice($segments, 2);

        if (!class_exists($controllerName)) {
            $this->notFound("Controlador no encontrado: {$controllerName}");
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $action)) {
            $this->notFound("Acción no encontrada: {$controllerName}::{$action}");
            return;
        }

        try {
            call_user_func_array([$controller, $action], $params);
        } catch (Throwable $e) {
            http_response_code(500);
            if (defined('DEBUG') && DEBUG) {
                echo '<pre style="padding:2rem;font-family:monospace;background:#0f0f10;color:#f5d27a;">';
                echo htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8');
                echo '</pre>';
            } else {
                echo '<h1>Error interno del servidor.</h1>';
            }
        }
    }

    private function notFound(string $msg): void
    {
        http_response_code(404);
        echo '<!doctype html><meta charset="utf-8">';
        echo '<title>404 — No encontrado</title>';
        echo '<style>body{font-family:Georgia,serif;background:#0c0d10;color:#e8e3d8;'
           . 'display:grid;place-items:center;min-height:100vh;margin:0;}'
           . 'h1{font-size:6rem;margin:0;letter-spacing:.05em;}p{color:#9a9588;}</style>';
        $home = (defined('BASE_URL') && BASE_URL !== '') ? rtrim(BASE_URL, '/') . '/' : '/';
        echo '<div style="text-align:center;"><h1>404</h1><p>'
           . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>'
           . '<p><a style="color:#f5b94a;" href="' . htmlspecialchars($home, ENT_QUOTES, 'UTF-8') . '">Volver al inicio</a></p></div>';
    }

    private function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', strtolower($value))));
    }

    private function camel(string $value): string
    {
        return lcfirst($this->studly($value));
    }
}
