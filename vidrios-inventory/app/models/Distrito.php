<?php
declare(strict_types=1);

final class Distrito extends BaseModel
{
    protected string $table = 'distritos';

    public function porProvincia(int $provId): array
    {
        $stmt = $this->db->prepare("SELECT id, nombre FROM distritos WHERE provincia_id = :p ORDER BY nombre");
        $stmt->execute([':p' => $provId]);
        return $stmt->fetchAll();
    }
}
