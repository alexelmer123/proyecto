<?php
declare(strict_types=1);

final class Producto extends BaseModel
{
    protected string $table = 'productos';

    /**
     * Listado con búsqueda por nombre/código y filtro por categoría.
     * Sólo devuelve productos activos (estado = 1).
     */
    public function buscar(string $q = '', ?int $categoriaId = null, ?int $limit = null, int $offset = 0): array
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, pr.nombre AS proveedor_nombre
                FROM `{$this->table}` p
                LEFT JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN proveedores pr ON pr.id = p.proveedor_id
                WHERE p.estado = 1";
        $params = [];

        if ($q !== '') {
            $sql .= " AND (p.nombre LIKE :q OR p.codigo LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }
        if ($categoriaId !== null && $categoriaId > 0) {
            $sql .= " AND p.categoria_id = :cat";
            $params[':cat'] = $categoriaId;
        }
        $sql .= " ORDER BY p.nombre ASC";

        if ($limit !== null) {
            $sql .= " LIMIT :__limit__ OFFSET :__offset__";
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

    public function contarBusqueda(string $q = '', ?int $categoriaId = null): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}` p WHERE p.estado = 1";
        $params = [];
        if ($q !== '') {
            $sql .= " AND (p.nombre LIKE :q OR p.codigo LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }
        if ($categoriaId !== null && $categoriaId > 0) {
            $sql .= " AND p.categoria_id = :cat";
            $params[':cat'] = $categoriaId;
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $this->pdoType($v));
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function findByCodigo(string $codigo): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE codigo = :c LIMIT 1");
        $stmt->execute([':c' => $codigo]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Trae un producto con los nombres de su categoría y proveedor (para vistas de detalle).
     */
    public function findConRelaciones(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.nombre AS categoria_nombre, pr.nombre AS proveedor_nombre
             FROM `{$this->table}` p
             LEFT JOIN categorias  c  ON c.id  = p.categoria_id
             LEFT JOIN proveedores pr ON pr.id = p.proveedor_id
             WHERE p.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    public function generarCodigoUnico(string $prefijo = 'VID'): string
    {
        do {
            $codigo = strtoupper($prefijo) . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while ($this->existsBy('codigo', $codigo));
        return $codigo;
    }

    public function softDelete(int $id): bool
    {
        return $this->update($id, ['estado' => 0]);
    }

    public function actualizarStock(int $id, int $nuevoStock): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET stock_actual = :s WHERE id = :id"
        );
        return $stmt->execute([':s' => $nuevoStock, ':id' => $id]);
    }

    public function stockBajo(): array
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre
                FROM `{$this->table}` p
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE p.estado = 1 AND p.stock_actual <= p.stock_minimo
                ORDER BY (p.stock_actual / NULLIF(p.stock_minimo,0)) ASC, p.nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function valorInventario(): array
    {
        $sql = "SELECT p.id, p.codigo, p.nombre, p.stock_actual, p.precio_compra,
                       (p.stock_actual * p.precio_compra) AS valor_total,
                       c.nombre AS categoria_nombre
                FROM `{$this->table}` p
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE p.estado = 1
                ORDER BY valor_total DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Reporte unificado de stock: combina stock actual, mínimos y valorización
     * (stock × precio_compra) con filtros opcionales.
     */
    public function stockReporte(bool $soloCriticos = false, ?int $categoriaId = null, ?int $proveedorId = null): array
    {
        $sql = "SELECT p.id, p.codigo, p.nombre,
                       p.stock_actual, p.stock_minimo,
                       p.precio_compra, p.precio_venta,
                       (p.stock_actual * p.precio_compra) AS valor_total,
                       GREATEST(p.stock_minimo - p.stock_actual, 0) AS faltante,
                       (p.stock_actual <= p.stock_minimo) AS es_critico,
                       c.nombre  AS categoria_nombre,
                       pr.nombre AS proveedor_nombre
                FROM `{$this->table}` p
                LEFT JOIN categorias  c  ON c.id  = p.categoria_id
                LEFT JOIN proveedores pr ON pr.id = p.proveedor_id
                WHERE p.estado = 1";
        $params = [];
        if ($soloCriticos) {
            $sql .= " AND p.stock_actual <= p.stock_minimo";
        }
        if ($categoriaId !== null && $categoriaId > 0) {
            $sql .= " AND p.categoria_id = :cat";
            $params[':cat'] = $categoriaId;
        }
        if ($proveedorId !== null && $proveedorId > 0) {
            $sql .= " AND p.proveedor_id = :prov";
            $params[':prov'] = $proveedorId;
        }
        $sql .= " ORDER BY es_critico DESC, (p.stock_actual / NULLIF(p.stock_minimo,0)) ASC, valor_total DESC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $this->pdoType($v));
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Productos suministrados por un proveedor (para el modal del consolidado). */
    public function porProveedor(int $proveedorId): array
    {
        $sql = "SELECT p.id, p.codigo, p.nombre,
                       p.stock_actual, p.stock_minimo,
                       p.precio_compra,
                       (p.stock_actual * p.precio_compra) AS valor_total,
                       c.nombre AS categoria_nombre
                FROM `{$this->table}` p
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE p.estado = 1 AND p.proveedor_id = :pid
                ORDER BY p.nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $proveedorId]);
        return $stmt->fetchAll();
    }
}
