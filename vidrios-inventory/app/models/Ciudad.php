<?php
declare(strict_types=1);

final class Ciudad extends BaseModel
{
    protected string $table = 'ciudades';

    public function porDistrito(int $distId): array
    {
        $stmt = $this->db->prepare("SELECT id, nombre FROM ciudades WHERE distrito_id = :d ORDER BY nombre");
        $stmt->execute([':d' => $distId]);
        return $stmt->fetchAll();
    }
}
