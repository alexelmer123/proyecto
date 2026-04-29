<?php
declare(strict_types=1);

final class Usuario extends BaseModel
{
    protected string $table = 'usuarios';

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE email = :email AND activo = 1 LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    public function actualizarUltimoAcceso(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET ultimo_acceso = NOW() WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
    }
}
