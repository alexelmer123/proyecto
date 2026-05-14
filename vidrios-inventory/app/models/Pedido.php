<?php
declare(strict_types=1);

final class Pedido extends BaseModel
{
    protected string $table = 'pedidos';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_PAGADO    = 'pagado';
    public const ESTADO_DEUDA     = 'deuda';

    /** Calcula el estado correcto a partir de total y pagado. */
    public function calcularEstado(float $total, float $pagado): string
    {
        if ($total <= 0) {
            return self::ESTADO_PENDIENTE;
        }
        if ($pagado >= $total) {
            return self::ESTADO_PAGADO;
        }
        if ($pagado > 0 && $pagado < $total) {
            return self::ESTADO_DEUDA;
        }
        return self::ESTADO_PENDIENTE;
    }

    public function generarNumero(): string
    {
        do {
            $numero = 'PED-' . date('Y') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while ($this->existsBy('numero', $numero));
        return $numero;
    }

    /**
     * Lista pedidos con datos del proveedor, filtrable por estado.
     * @return array<int, array<string, mixed>>
     */
    public function listar(?string $estado = null, ?int $limit = null, int $offset = 0): array
    {
        $sql = "SELECT p.*, pr.nombre AS proveedor_nombre, u.nombre AS usuario_nombre,
                       (p.total - p.pagado) AS saldo
                  FROM pedidos p
                  LEFT JOIN proveedores pr ON pr.id = p.proveedor_id
                  LEFT JOIN usuarios u    ON u.id = p.usuario_id
                 WHERE 1=1";
        $params = [];
        if ($estado !== null && $estado !== '') {
            $sql .= ' AND p.estado = :estado';
            $params[':estado'] = $estado;
        }
        $sql .= ' ORDER BY p.fecha_pedido DESC, p.id DESC';
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

    public function contar(?string $estado = null): int
    {
        $sql = "SELECT COUNT(*) FROM pedidos";
        $params = [];
        if ($estado !== null && $estado !== '') {
            $sql .= ' WHERE estado = :estado';
            $params[':estado'] = $estado;
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /** @return array{pendientes:int, pagados:int, deudas:int, total_deuda:float} */
    public function resumen(): array
    {
        $row = $this->db->query("
            SELECT
                SUM(CASE WHEN estado='pendiente' THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado='pagado'    THEN 1 ELSE 0 END) AS pagados,
                SUM(CASE WHEN estado='deuda'     THEN 1 ELSE 0 END) AS deudas,
                COALESCE(SUM(CASE WHEN estado <> 'pagado' THEN total - pagado ELSE 0 END), 0) AS total_deuda
              FROM pedidos
        ")->fetch();
        return [
            'pendientes'  => (int)   ($row['pendientes']  ?? 0),
            'pagados'     => (int)   ($row['pagados']     ?? 0),
            'deudas'      => (int)   ($row['deudas']      ?? 0),
            'total_deuda' => (float) ($row['total_deuda'] ?? 0.0),
        ];
    }
}
