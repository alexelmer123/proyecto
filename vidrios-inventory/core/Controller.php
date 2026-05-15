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
        // Valida que el usuario en sesión todavía exista en la BD; si fue
        // eliminado o la BD se reinstaló, descartamos la sesión y forzamos login.
        if (!$this->usuarioVigenteEnBd((int) $_SESSION['usuario']['id'])) {
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_regenerate_id(true);
            }
            $_SESSION['flash'] = [
                'type' => 'warn',
                'msg'  => 'Tu sesión ya no es válida. Vuelve a iniciar sesión.',
            ];
            $this->redirect('/auth/login');
        }
    }

    private function usuarioVigenteEnBd(int $id): bool
    {
        if ($id <= 0) return false;
        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare("SELECT 1 FROM usuarios WHERE id = :id AND activo = 1 LIMIT 1");
            $stmt->execute([':id' => $id]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            // Si la tabla no existe o la BD está down, no bloqueamos por aquí.
            return true;
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

    protected function isAjax(): bool
    {
        $xrw = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return strtolower((string) $xrw) === 'fetch' || strtolower((string) $xrw) === 'xmlhttprequest';
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
