<?php
declare(strict_types=1);

final class Proveedor extends BaseModel
{
    protected string $table = 'proveedores';

    public function activos(): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE estado = 1 ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /** Lista enriquecida con nombre de ubicación (ciudad / país). */
    public function listadoConUbicacion(): array
    {
        $sql = "SELECT pr.*,
                       pa.nombre AS pais_nombre,
                       ci.nombre AS ciudad_nombre
                  FROM proveedores pr
                  LEFT JOIN paises   pa ON pa.id = pr.pais_id
                  LEFT JOIN ciudades ci ON ci.id = pr.ciudad_id
                 ORDER BY pr.nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }
}
