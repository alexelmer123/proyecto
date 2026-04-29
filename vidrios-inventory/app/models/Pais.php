<?php
declare(strict_types=1);

final class Pais extends BaseModel
{
    protected string $table = 'paises';

    public function activos(): array
    {
        return $this->db->query("SELECT id, nombre, codigo FROM paises ORDER BY nombre")->fetchAll();
    }
}
