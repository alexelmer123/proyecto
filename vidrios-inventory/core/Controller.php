<?php
declare(strict_types=1);

/**
 * Controller — clase base con utilidades de render, redirect y sesión.
 */
abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function render(string $view, array $data = [], bool $withLayout = true): void
    {
        $viewFile = ROOT . '/app/views/' . $view . '.php';
        if (!is_file($viewFile)) {
            http_response_code(500);
            throw new RuntimeException("Vista no encontrada: {$view}");
        }

        // Variables expuestas al layout (header/sidebar dependen de éstas)
        $usuario       = $_SESSION['usuario']      ?? null;
        $stockBajoCount = $this->contarStockBajo();
        $flash         = $_SESSION['flash']        ?? null;
        unset($_SESSION['flash']);

        extract($data, EXTR_SKIP);

        if ($withLayout) {
            require ROOT . '/app/views/layouts/header.php';
            require ROOT . '/app/views/layouts/sidebar.php';
            echo '<main class="main-content">';
            require $viewFile;
            echo '</main>';
            require ROOT . '/app/views/layouts/footer.php';
        } else {
            require $viewFile;
        }
    }

    protected function redirect(string $url): never
    {
        if (defined('BASE_URL') && BASE_URL !== '' && BASE_URL !== '/'
            && str_starts_with($url, '/')
            && !str_starts_with($url, BASE_URL . '/')
            && !str_starts_with($url, '//')) {
            $url = rtrim(BASE_URL, '/') . $url;
        }
        header('Location: ' . $url);
        exit;
    }

    protected function isLoggedIn(): bool
    {
        return isset($_SESSION['usuario']) && !empty($_SESSION['usuario']['id']);
    }

    protected function isAdmin(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['usuario']['rol'] ?? '') === 'admin';
    }

    protected function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            $_SESSION['flash'] = ['type' => 'warn', 'msg' => 'Inicia sesión para continuar.'];
            $this->redirect('/auth/login');
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            http_response_code(403);
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'No tienes permisos para esta acción.'];
            $this->redirect('/producto/index');
        }
    }

    protected function setFlash(string $type, string $msg): void
    {
        $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    }

    protected function audit(string $accion, string $entidad, ?string $entidadId = null, ?string $descripcion = null): void
    {
        try {
            (new Auditoria())->registrar($accion, $entidad, $entidadId, $descripcion);
        } catch (Throwable) {
            // La auditoría no debe romper el flujo del negocio.
        }
    }

    protected function userId(): ?int
    {
        return isset($_SESSION['usuario']['id']) ? (int) $_SESSION['usuario']['id'] : null;
    }

    protected function contarStockBajo(): int
    {
        if (!$this->isLoggedIn()) {
            return 0;
        }
        try {
            $db = Database::getInstance();
            $sql = "SELECT COUNT(*) FROM productos
                    WHERE estado = 1 AND stock_actual <= stock_minimo";
            return (int) $db->query($sql)->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }
}
