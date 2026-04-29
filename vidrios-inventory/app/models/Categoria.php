<?php
declare(strict_types=1);

final class Categoria extends BaseModel
{
    protected string $table = 'categorias';

    public function activas(): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE estado = 1 ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function conConteoDeProductos(): array
    {
        $sql = "SELECT c.*, COUNT(p.id) AS total_productos
                FROM `{$this->table}` c
                LEFT JOIN productos p ON p.categoria_id = c.id AND p.estado = 1
                GROUP BY c.id
                ORDER BY c.nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }
}
