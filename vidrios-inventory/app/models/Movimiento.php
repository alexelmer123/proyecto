<?php
declare(strict_types=1);

final class Movimiento extends BaseModel
{
    protected string $table = 'movimientos';

    public const TIPO_ENTRADA = 'entrada';
    public const TIPO_SALIDA  = 'salida';
    public const TIPO_AJUSTE  = 'ajuste';

    public const MOTIVO_VENTA     = 'venta';
    public const MOTIVO_ENCARGO   = 'encargo';
    public const MOTIVO_ACCIDENTE = 'accidente';
    public const MOTIVO_MERMA     = 'merma';
    // Retazo: marcador de "sobrante útil" que NO afecta stock. No se persiste
    // como movimiento; el controlador lo anexa como nota al observacion del
    // movimiento principal.
    public const MOTIVO_RETAZO    = 'retazo';

    /** Motivos válidos para salidas. */
    public const MOTIVOS_SALIDA = [
        self::MOTIVO_VENTA, self::MOTIVO_ENCARGO,
        self::MOTIVO_ACCIDENTE, self::MOTIVO_MERMA,
    ];

    /**
     * Registra un movimiento dentro de una transacción y actualiza el stock del producto.
     * Devuelve el id del movimiento creado.
     *
     * @throws RuntimeException si no hay stock suficiente en una salida.
     */
    public function registrar(
        int $productoId,
        string $tipo,
        float $cantidad,
        int $usuarioId,
        ?string $observacion = null,
        ?int $proveedorId = null,
        ?string $motivo = null,
        ?string $cliente = null,
        ?float $total = null,
        ?string $fechaEntrega = null,
        ?string $evidencia = null,
        ?int $encargoId = null
    ): int {
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad debe ser mayor a cero.');
        }
        if (!in_array($tipo, [self::TIPO_ENTRADA, self::TIPO_SALIDA, self::TIPO_AJUSTE], true)) {
            throw new RuntimeException('Tipo de movimiento inválido.');
        }
        if ($motivo !== null && !in_array($motivo, self::MOTIVOS_SALIDA, true)) {
            throw new RuntimeException('Motivo de salida inválido.');
        }

        // Si el caller ya inició una transacción (ej. creación de un encargo con
        // N items), no abrimos otra: dejamos commit/rollback al caller.
        $owns = !$this->db->inTransaction();
        if ($owns) {
            $this->db->beginTransaction();
        }
        try {
            // Bloqueo de fila para evitar carreras
            $stmt = $this->db->prepare(
                "SELECT id, stock_actual FROM productos WHERE id = :id AND estado = 1 FOR UPDATE"
            );
            $stmt->execute([':id' => $productoId]);
            $producto = $stmt->fetch();
            if ($producto === false) {
                throw new RuntimeException('Producto no encontrado.');
            }

            $stockAnterior = (float) $producto['stock_actual'];
            $stockNuevo    = match ($tipo) {
                self::TIPO_ENTRADA => $stockAnterior + $cantidad,
                self::TIPO_SALIDA  => $stockAnterior - $cantidad,
                self::TIPO_AJUSTE  => $cantidad, // ajuste = nuevo valor absoluto
            };

            // Redondeo a 2 decimales para evitar drift por suma de floats.
            $stockNuevo = round($stockNuevo, 2);

            if ($tipo === self::TIPO_SALIDA && $cantidad > $stockAnterior + 0.0001) {
                throw new RuntimeException(
                    "Stock insuficiente. Disponible: {$stockAnterior}, solicitado: {$cantidad}."
                );
            }
            if ($stockNuevo < 0) {
                throw new RuntimeException('El stock resultante no puede ser negativo.');
            }

            // Actualizar stock (DECIMAL: bindeamos como string formateado para
            // no perder precisión por float)
            $upd = $this->db->prepare("UPDATE productos SET stock_actual = :s WHERE id = :id");
            $upd->bindValue(':s',  number_format($stockNuevo, 2, '.', ''));
            $upd->bindValue(':id', $productoId, PDO::PARAM_INT);
            $upd->execute();

            // Registrar movimiento
            $ins = $this->db->prepare(
                "INSERT INTO `{$this->table}`
                    (producto_id, tipo, motivo, cantidad, stock_anterior, stock_nuevo,
                     usuario_id, proveedor_id, encargo_id,
                     cliente, total, fecha_entrega, evidencia,
                     observacion, created_at)
                 VALUES (:p, :t, :m, :c, :sa, :sn, :u, :pr, :en,
                         :cl, :to, :fe, :ev, :o, NOW())"
            );
            // Si el id del usuario no existe en la tabla `usuarios` o es <=0,
            // persistimos NULL (la FK es ON DELETE SET NULL).
            $usuarioIdFk = $this->resolverUsuarioId($usuarioId);

            $ins->bindValue(':p',  $productoId,  PDO::PARAM_INT);
            $ins->bindValue(':t',  $tipo);
            $ins->bindValue(':m',  $motivo);
            $ins->bindValue(':c',  number_format($cantidad,      2, '.', ''));
            $ins->bindValue(':sa', number_format($stockAnterior, 2, '.', ''));
            $ins->bindValue(':sn', number_format($stockNuevo,    2, '.', ''));
            $ins->bindValue(':u',  $usuarioIdFk, $usuarioIdFk === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $ins->bindValue(':pr', $proveedorId, $proveedorId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $ins->bindValue(':en', $encargoId,   $encargoId   === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $ins->bindValue(':cl', $cliente);
            $ins->bindValue(':to', $total === null ? null : number_format($total, 2, '.', ''));
            $ins->bindValue(':fe', $fechaEntrega);
            $ins->bindValue(':ev', $evidencia);
            $ins->bindValue(':o',  $observacion);
            $ins->execute();

            $id = (int) $this->db->lastInsertId();
            if ($owns) {
                $this->db->commit();
            }
            return $id;
        } catch (Throwable $e) {
            if ($owns && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /** Historial filtrable, paginado. */
    public function historial(
        ?int $productoId = null,
        ?string $tipo = null,
        ?string $desde = null,
        ?string $hasta = null,
        ?int $limit = null,
        int $offset = 0,
        ?string $motivo = null
    ): array {
        $sql = "SELECT m.*, p.codigo AS producto_codigo, p.nombre AS producto_nombre,
                       u.nombre AS usuario_nombre, pr.nombre AS proveedor_nombre
                FROM `{$this->table}` m
                INNER JOIN productos p ON p.id = m.producto_id
                LEFT JOIN usuarios u ON u.id = m.usuario_id
                LEFT JOIN proveedores pr ON pr.id = m.proveedor_id
                WHERE 1 = 1";
        $params = [];
        $this->aplicarFiltrosHistorial($sql, $params, $productoId, $tipo, $desde, $hasta, $motivo);
        $sql .= ' ORDER BY m.created_at DESC, m.id DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT :__limit__ OFFSET :__offset__';
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $this->pdoType($v));
        }
        if ($limit !== null) {
            $stmt->bindValue(':__limit__',  $limit,  PDO::PARAM_INT);
            $stmt->bindValue(':__offset__', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contarHistorial(?int $productoId = null, ?string $tipo = null, ?string $desde = null, ?string $hasta = null, ?string $motivo = null): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}` m WHERE 1 = 1";
        $params = [];
        $this->aplicarFiltrosHistorial($sql, $params, $productoId, $tipo, $desde, $hasta, $motivo);
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $this->pdoType($v));
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /** Devuelve el id si existe en `usuarios`; null en caso contrario. */
    private function resolverUsuarioId(int $id): ?int
    {
        if ($id <= 0) return null;
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? (int) $row['id'] : null;
    }

    private function aplicarFiltrosHistorial(string &$sql, array &$params, ?int $productoId, ?string $tipo, ?string $desde, ?string $hasta, ?string $motivo = null): void
    {
        if ($productoId !== null && $productoId > 0) {
            $sql .= ' AND m.producto_id = :pid';
            $params[':pid'] = $productoId;
        }
        if ($tipo !== null && $tipo !== '') {
            $sql .= ' AND m.tipo = :tipo';
            $params[':tipo'] = $tipo;
        }
        if ($motivo !== null && $motivo !== '') {
            $sql .= ' AND m.motivo = :motivo';
            $params[':motivo'] = $motivo;
        }
        if ($desde !== null && $desde !== '') {
            $sql .= ' AND m.created_at >= :desde';
            $params[':desde'] = $desde . ' 00:00:00';
        }
        if ($hasta !== null && $hasta !== '') {
            $sql .= ' AND m.created_at <= :hasta';
            $params[':hasta'] = $hasta . ' 23:59:59';
        }
    }

    /**
     * Mermas / retazos agrupadas por producto.
     * Considera salidas con motivo merma o accidente.
     */
    public function mermasPorProducto(?string $desde = null, ?string $hasta = null, ?string $motivo = null): array
    {
        $sql = "SELECT p.id AS producto_id, p.codigo, p.nombre,
                       c.nombre AS categoria_nombre,
                       SUM(CASE WHEN m.motivo = 'merma'     THEN m.cantidad ELSE 0 END) AS total_merma,
                       SUM(CASE WHEN m.motivo = 'accidente' THEN m.cantidad ELSE 0 END) AS total_accidente,
                       SUM(m.cantidad) AS total_perdido,
                       SUM(m.cantidad * p.precio_compra) AS valor_perdido,
                       COUNT(*) AS eventos,
                       MAX(m.created_at) AS ultimo_evento
                FROM `{$this->table}` m
                INNER JOIN productos p ON p.id = m.producto_id
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE m.tipo = 'salida'
                  AND m.motivo IN ('merma','accidente')";
        $params = [];
        if ($motivo === 'merma' || $motivo === 'accidente') {
            $sql .= " AND m.motivo = :motivo";
            $params[':motivo'] = $motivo;
        }
        if ($desde !== null && $desde !== '') {
            $sql .= " AND m.created_at >= :desde";
            $params[':desde'] = $desde . ' 00:00:00';
        }
        if ($hasta !== null && $hasta !== '') {
            $sql .= " AND m.created_at <= :hasta";
            $params[':hasta'] = $hasta . ' 23:59:59';
        }
        $sql .= " GROUP BY p.id, p.codigo, p.nombre, c.nombre
                  ORDER BY total_perdido DESC, p.nombre ASC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $this->pdoType($v));
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Lista cronológica de mermas con valorización a precio de compra. */
    public function mermasDetalle(?string $desde = null, ?string $hasta = null, ?string $motivo = null, int $limit = 500): array
    {
        $sql = "SELECT m.id, m.created_at, m.cantidad, m.motivo, m.observacion,
                       p.codigo AS producto_codigo, p.nombre AS producto_nombre,
                       p.precio_compra,
                       (m.cantidad * p.precio_compra) AS valor_perdido,
                       u.nombre AS usuario_nombre
                FROM `{$this->table}` m
                INNER JOIN productos p ON p.id = m.producto_id
                LEFT JOIN usuarios u ON u.id = m.usuario_id
                WHERE m.tipo = 'salida'
                  AND m.motivo IN ('merma','accidente')";
        $params = [];
        if ($motivo === 'merma' || $motivo === 'accidente') {
            $sql .= " AND m.motivo = :motivo";
            $params[':motivo'] = $motivo;
        }
        if ($desde !== null && $desde !== '') {
            $sql .= " AND m.created_at >= :desde";
            $params[':desde'] = $desde . ' 00:00:00';
        }
        if ($hasta !== null && $hasta !== '') {
            $sql .= " AND m.created_at <= :hasta";
            $params[':hasta'] = $hasta . ' 23:59:59';
        }
        $sql .= " ORDER BY m.created_at DESC, m.id DESC LIMIT :__limit__";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $this->pdoType($v));
        }
        $stmt->bindValue(':__limit__', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function agruparPorPeriodo(string $agrupacion = 'dia', ?string $desde = null, ?string $hasta = null): array
    {
        $expr = $agrupacion === 'mes'
            ? "DATE_FORMAT(created_at, '%Y-%m')"
            : "DATE(created_at)";

        $sql = "SELECT {$expr} AS periodo,
                       SUM(CASE WHEN tipo = 'entrada' THEN cantidad ELSE 0 END) AS total_entradas,
                       SUM(CASE WHEN tipo = 'salida'  THEN cantidad ELSE 0 END) AS total_salidas,
                       COUNT(*) AS total_movimientos
                FROM `{$this->table}`
                WHERE 1 = 1";
        $params = [];
        if ($desde) { $sql .= ' AND created_at >= :d'; $params[':d'] = $desde . ' 00:00:00'; }
        if ($hasta) { $sql .= ' AND created_at <= :h'; $params[':h'] = $hasta . ' 23:59:59'; }
        $sql .= " GROUP BY periodo ORDER BY periodo DESC LIMIT 200";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
