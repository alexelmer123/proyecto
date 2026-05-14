<?php
declare(strict_types=1);

final class Permiso extends BaseModel
{
    protected string $table = 'permisos';

    /** @return array<int, array<string, mixed>> */
    public function listadoAgrupado(): array
    {
        return $this->db->query(
            "SELECT id, codigo, nombre, modulo FROM permisos ORDER BY modulo, codigo"
        )->fetchAll();
    }
}
