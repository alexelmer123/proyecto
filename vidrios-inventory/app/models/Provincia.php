<?php
declare(strict_types=1);

final class Provincia extends BaseModel
{
    protected string $table = 'provincias';

    public function porDepartamento(int $depId): array
    {
        $stmt = $this->db->prepare("SELECT id, nombre FROM provincias WHERE departamento_id = :d ORDER BY nombre");
        $stmt->execute([':d' => $depId]);
        return $stmt->fetchAll();
    }
}
