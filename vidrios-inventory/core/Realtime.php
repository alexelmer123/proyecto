<?php
declare(strict_types=1);

/**
 * Realtime — cliente PHP que publica eventos al daemon WebSocket.
 *
 * Hace un POST HTTP sobre TCP crudo (fsockopen) con timeout corto. Si el
 * daemon está caído, ignoramos el error: el inventario sigue funcionando
 * sin tiempo real, pero los usuarios deberán refrescar manualmente.
 *
 * El payload se serializa como:
 *   { "event": "<nombre>", "data": {...}, "ts": "<iso8601>" }
 *
 * El navegador recibe ese mismo JSON tal cual por WebSocket.
 */
final class Realtime
{
    /** @param array<string, mixed> $data */
    public static function publish(string $event, array $data): bool
    {
        if (!defined('REALTIME_ENABLED') || !REALTIME_ENABLED) {
            return false;
        }

        $payload = json_encode([
            'event' => $event,
            'data'  => $data,
            'ts'    => date('c'),
        ], JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            return false;
        }

        $host       = defined('REALTIME_PUSH_HOST')   ? (string) REALTIME_PUSH_HOST : '127.0.0.1';
        $port       = defined('REALTIME_PUSH_PORT')   ? (int)    REALTIME_PUSH_PORT : 8081;
        $secret     = defined('REALTIME_PUSH_SECRET') ? (string) REALTIME_PUSH_SECRET : '';
        $timeoutMs  = defined('REALTIME_TIMEOUT_MS')  ? (int)    REALTIME_TIMEOUT_MS : 250;
        $timeoutSec = max(0.05, $timeoutMs / 1000.0);

        $errno = 0; $errstr = '';
        $fp = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            $timeoutSec,
            STREAM_CLIENT_CONNECT
        );
        if ($fp === false) {
            return false;
        }
        @stream_set_timeout($fp, 0, (int) ($timeoutSec * 1_000_000));

        $request = "POST /push HTTP/1.1\r\n"
                 . "Host: {$host}:{$port}\r\n"
                 . "X-Realtime-Secret: {$secret}\r\n"
                 . "Content-Type: application/json; charset=utf-8\r\n"
                 . 'Content-Length: ' . strlen($payload) . "\r\n"
                 . "Connection: close\r\n\r\n"
                 . $payload;

        @fwrite($fp, $request);
        @fclose($fp);
        return true;
    }

    /**
     * Helper específico para cambios de stock. Resuelve los datos públicos del
     * producto (código, nombre, stock actual) y los embebe en el evento.
     *
     * @param array<string, mixed> $extras  tipo, motivo, delta, observacion, etc.
     */
    public static function publishStockChange(int $productoId, array $extras = []): bool
    {
        if (!defined('REALTIME_ENABLED') || !REALTIME_ENABLED) {
            return false;
        }
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT id, codigo, nombre, stock_actual, stock_minimo, unidad
                 FROM productos WHERE id = :id LIMIT 1"
            );
            $stmt->execute([':id' => $productoId]);
            $p = $stmt->fetch();
            if ($p === false) {
                return false;
            }

            $stockBajo = (int) $db->query(
                "SELECT COUNT(*) FROM productos
                 WHERE estado = 1 AND stock_actual <= stock_minimo"
            )->fetchColumn();

            $usuario = $_SESSION['usuario'] ?? null;
            $payload = array_merge([
                'producto_id'      => (int) $p['id'],
                'producto_codigo'  => (string) $p['codigo'],
                'producto_nombre'  => (string) $p['nombre'],
                'stock_nuevo'      => (float) $p['stock_actual'],
                'stock_minimo'     => isset($p['stock_minimo']) ? (float) $p['stock_minimo'] : null,
                'unidad'           => (string) $p['unidad'],
                'stock_bajo_count' => $stockBajo,
                'usuario'          => $usuario['nombre'] ?? 'sistema',
                'usuario_id'       => isset($usuario['id']) ? (int) $usuario['id'] : 0,
                'usuario_rol'      => $usuario['rol'] ?? null,
            ], $extras);

            return self::publish('stock_changed', $payload);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Helper genérico: notifica que una entidad fue creada/editada/eliminada.
     *
     * Lo invoca automáticamente Controller::audit() para no tener que tocar
     * cada controlador. El cliente JS muestra un toast y marca como obsoletos
     * todos los nodos `[data-entity-id="entidad:id"]` y `[data-<entidad>-id="id"]`
     * presentes en la pantalla.
     *
     * @param array<string,mixed> $extras  campos opcionales (refresh_url, etiquetas, etc.)
     */
    public static function publishEntityChange(
        string $accion,
        string $entidad,
        ?string $entidadId = null,
        ?string $descripcion = null,
        array $extras = []
    ): bool {
        if (!defined('REALTIME_ENABLED') || !REALTIME_ENABLED) {
            return false;
        }
        $usuario = $_SESSION['usuario'] ?? null;
        $payload = array_merge([
            'accion'      => $accion,
            'entidad'     => $entidad,
            'entidad_id'  => $entidadId,
            'descripcion' => $descripcion,
            'usuario'     => $usuario['nombre'] ?? 'sistema',
            'usuario_id'  => isset($usuario['id']) ? (int) $usuario['id'] : 0,
            'usuario_rol' => $usuario['rol'] ?? null,
        ], $extras);

        return self::publish('entity_changed', $payload);
    }
}
