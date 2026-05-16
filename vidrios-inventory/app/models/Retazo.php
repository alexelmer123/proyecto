<?php
declare(strict_types=1);

final class Retazo extends BaseModel
{
    protected string $table = 'retazos';

    public const ORIGEN_SALIDA  = 'salida';
    public const ORIGEN_ENCARGO = 'encargo';

    /**
     * Inserta un retazo. $medidas es un sub-array con claves ancho/alto/longitud
     * (cualquiera puede faltar; las que no apliquen quedan NULL en BD).
     *
     * @param array<string, float|null> $medidas
     */
    public function registrar(
        int $productoId,
        float $cantidad,
        array $medidas,
        string $origen,
        ?int $origenId,
        ?string $observacion,
        ?int $usuarioId
    ): int {
        if ($productoId <= 0)               throw new RuntimeException('Producto inválido para retazo.');
        if ($cantidad <= 0)                 throw new RuntimeException('Cantidad inválida para retazo.');
        if (!in_array($origen, [self::ORIGEN_SALIDA, self::ORIGEN_ENCARGO], true)) {
            throw new RuntimeException('Origen de retazo inválido.');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}`
                (producto_id, cantidad, ancho, alto, longitud,
                 origen, origen_id, observacion, usuario_id, created_at)
             VALUES (:p, :c, :an, :al, :lo, :ori, :oid, :obs, :u, NOW())"
        );
        $stmt->bindValue(':p',   $productoId, PDO::PARAM_INT);
        $stmt->bindValue(':c',   number_format($cantidad, 2, '.', ''));
        $stmt->bindValue(':an',  $this->medida($medidas, 'ancho'));
        $stmt->bindValue(':al',  $this->medida($medidas, 'alto'));
        $stmt->bindValue(':lo',  $this->medida($medidas, 'longitud'));
        $stmt->bindValue(':ori', $origen);
        $stmt->bindValue(':oid', $origenId,   $origenId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':obs', $observacion);
        $stmt->bindValue(':u',   $usuarioId,  $usuarioId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    /**
     * Listado paginado con filtros. Trae datos del producto enriquecidos.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(
        ?int $productoId = null,
        ?string $origen = null,
        ?string $estado = null,
        ?string $desde = null,
        ?string $hasta = null,
        ?int $limit = null,
        int $offset = 0
    ): array {
        $sql = "SELECT r.*, p.codigo AS producto_codigo, p.nombre AS producto_nombre,
                       p.unidad AS producto_unidad, c.nombre AS categoria_nombre,
                       u.nombre AS usuario_nombre
                FROM `{$this->table}` r
                INNER JOIN productos p ON p.id = r.producto_id
                LEFT JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN usuarios   u ON u.id = r.usuario_id
                WHERE 1 = 1";
        $params = [];
        $this->aplicarFiltros($sql, $params, $productoId, $origen, $estado, $desde, $hasta);
        $sql .= ' ORDER BY r.created_at DESC, r.id DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT :__limit__ OFFSET :__offset__';
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v, $this->pdoType($v));
        if ($limit !== null) {
            $stmt->bindValue(':__limit__',  $limit,  PDO::PARAM_INT);
            $stmt->bindValue(':__offset__', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contar(
        ?int $productoId = null,
        ?string $origen = null,
        ?string $estado = null,
        ?string $desde = null,
        ?string $hasta = null
    ): int {
        $sql = "SELECT COUNT(*) FROM `{$this->table}` r WHERE 1 = 1";
        $params = [];
        $this->aplicarFiltros($sql, $params, $productoId, $origen, $estado, $desde, $hasta);
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v, $this->pdoType($v));
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function marcarAprovechado(int $id, bool $valor = true): bool
    {
        return $this->update($id, ['aprovechado' => $valor ? 1 : 0]);
    }

    private function aplicarFiltros(
        string &$sql,
        array &$params,
        ?int $productoId,
        ?string $origen,
        ?string $estado,
        ?string $desde,
        ?string $hasta
    ): void {
        if ($productoId !== null && $productoId > 0) {
            $sql .= ' AND r.producto_id = :pid';
            $params[':pid'] = $productoId;
        }
        if ($origen !== null && $origen !== '') {
            $sql .= ' AND r.origen = :ori';
            $params[':ori'] = $origen;
        }
        if ($estado === 'disponible') {
            $sql .= ' AND r.aprovechado = 0';
        } elseif ($estado === 'aprovechado') {
            $sql .= ' AND r.aprovechado = 1';
        }
        if ($desde !== null && $desde !== '') {
            $sql .= ' AND r.created_at >= :desde';
            $params[':desde'] = $desde . ' 00:00:00';
        }
        if ($hasta !== null && $hasta !== '') {
            $sql .= ' AND r.created_at <= :hasta';
            $params[':hasta'] = $hasta . ' 23:59:59';
        }
    }

    /** Convierte un valor de $medidas[key] al string formateado o NULL. */
    private function medida(array $medidas, string $key): ?string
    {
        if (!isset($medidas[$key])) return null;
        $v = (float) $medidas[$key];
        return $v > 0 ? number_format($v, 2, '.', '') : null;
    }
}
