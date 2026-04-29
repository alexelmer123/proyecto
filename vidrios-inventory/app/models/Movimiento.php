<?php
declare(strict_types=1);

final class Movimiento extends BaseModel
{
    protected string $table = 'movimientos';

    public const TIPO_ENTRADA = 'entrada';
    public const TIPO_SALIDA  = 'salida';
    public const TIPO_AJUSTE  = 'ajuste';

    /**
     * Registra un movimiento dentro de una transacción y actualiza el stock del producto.
     * Devuelve el id del movimiento creado.
     *
     * @throws RuntimeException si no hay stock suficiente en una salida.
     */
    public function registrar(
        int $productoId,
        string $tipo,
        int $cantidad,
        int $usuarioId,
        ?string $observacion = null,
        ?int $proveedorId = null
    ): int {
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad debe ser mayor a cero.');
        }
        if (!in_array($tipo, [self::TIPO_ENTRADA, self::TIPO_SALIDA, self::TIPO_AJUSTE], true)) {
            throw new RuntimeException('Tipo de movimiento inválido.');
        }

        $this->db->beginTransaction();
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

            $stockAnterior = (int) $producto['stock_actual'];
            $stockNuevo    = match ($tipo) {
                self::TIPO_ENTRADA => $stockAnterior + $cantidad,
                self::TIPO_SALIDA  => $stockAnterior - $cantidad,
                self::TIPO_AJUSTE  => $cantidad, // ajuste = nuevo valor absoluto
            };

            if ($tipo === self::TIPO_SALIDA && $cantidad > $stockAnterior) {
                throw new RuntimeException(
                    "Stock insuficiente. Disponible: {$stockAnterior}, solicitado: {$cantidad}."
                );
            }
            if ($stockNuevo < 0) {
                throw new RuntimeException('El stock resultante no puede ser negativo.');
            }

            // Actualizar stock
            $upd = $this->db->prepare("UPDATE productos SET stock_actual = :s WHERE id = :id");
            $upd->execute([':s' => $stockNuevo, ':id' => $productoId]);

            // Registrar movimiento
            $ins = $this->db->prepare(
                "INSERT INTO `{$this->table}`
                    (producto_id, tipo, cantidad, stock_anterior, stock_nuevo,
                     usuario_id, proveedor_id, observacion, created_at)
                 VALUES (:p, :t, :c, :sa, :sn, :u, :pr, :o, NOW())"
            );
            $ins->execute([
                ':p'  => $productoId,
                ':t'  => $tipo,
                ':c'  => $cantidad,
                ':sa' => $stockAnterior,
                ':sn' => $stockNuevo,
                ':u'  => $usuarioId,
                ':pr' => $proveedorId,
                ':o'  => $observacion,
            ]);

            $id = (int) $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
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
        int $offset = 0
    ): array {
        $sql = "SELECT m.*, p.codigo AS producto_codigo, p.nombre AS producto_nombre,
                       u.nombre AS usuario_nombre, pr.nombre AS proveedor_nombre
                FROM `{$this->table}` m
                INNER JOIN productos p ON p.id = m.producto_id
                LEFT JOIN usuarios u ON u.id = m.usuario_id
                LEFT JOIN proveedores pr ON pr.id = m.proveedor_id
                WHERE 1 = 1";
        $params = [];
        $this->aplicarFiltrosHistorial($sql, $params, $productoId, $tipo, $desde, $hasta);
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

    public function contarHistorial(?int $productoId = null, ?string $tipo = null, ?string $desde = null, ?string $hasta = null): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}` m WHERE 1 = 1";
        $params = [];
        $this->aplicarFiltrosHistorial($sql, $params, $productoId, $tipo, $desde, $hasta);
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $this->pdoType($v));
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function aplicarFiltrosHistorial(string &$sql, array &$params, ?int $productoId, ?string $tipo, ?string $desde, ?string $hasta): void
    {
        if ($productoId !== null && $productoId > 0) {
            $sql .= ' AND m.producto_id = :pid';
            $params[':pid'] = $productoId;
        }
        if ($tipo !== null && $tipo !== '') {
            $sql .= ' AND m.tipo = :tipo';
            $params[':tipo'] = $tipo;
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
