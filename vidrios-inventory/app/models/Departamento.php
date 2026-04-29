<?php
declare(strict_types=1);

final class Departamento extends BaseModel
{
    protected string $table = 'departamentos';

    public function porPais(int $paisId): array
    {
        $stmt = $this->db->prepare("SELECT id, nombre FROM departamentos WHERE pais_id = :p ORDER BY nombre");
        $stmt->execute([':p' => $paisId]);
        return $stmt->fetchAll();
    }
}
