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

    public function existsByEmail(string $email, ?int $excluirId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE email = :e";
        $params = [':e' => $email];
        if ($excluirId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function actualizarUltimoAcceso(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET ultimo_acceso = NOW() WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listadoConRol(): array
    {
        $sql = "
            SELECT u.id, u.nombre, u.email, u.rol, u.rol_id, u.activo,
                   u.ultimo_acceso, u.created_at,
                   r.nombre AS rol_nombre,
                   (SELECT COUNT(*) FROM usuarios_permisos up WHERE up.usuario_id = u.id) AS extras_count
              FROM usuarios u
              LEFT JOIN roles r ON r.id = u.rol_id
             WHERE u.activo = 1
             ORDER BY u.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    public function findByIdConRol(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.*, r.nombre AS rol_nombre
              FROM usuarios u
              LEFT JOIN roles r ON r.id = u.rol_id
             WHERE u.id = :id
             LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** @return array<int> Ids de permisos extra del usuario (los que NO vienen del rol). */
    public function idsPermisoExtra(int $usuarioId): array
    {
        $stmt = $this->db->prepare(
            "SELECT permiso_id FROM usuarios_permisos WHERE usuario_id = :id"
        );
        $stmt->execute([':id' => $usuarioId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'permiso_id'));
    }

    /**
     * Crea un usuario con su rol y permisos extra en una sola transacción.
     * @param array<int> $permisosExtraIds
     */
    public function crearConAsignaciones(array $datos, array $permisosExtraIds): int
    {
        $this->db->beginTransaction();
        try {
            $uid = $this->create($datos);
            $this->reemplazarPermisosExtra($uid, $permisosExtraIds);
            $this->db->commit();
            return $uid;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza el usuario y reemplaza sus permisos extra.
     * @param array<int> $permisosExtraIds
     */
    public function actualizarConAsignaciones(int $usuarioId, array $datos, array $permisosExtraIds): void
    {
        $this->db->beginTransaction();
        try {
            $this->update($usuarioId, $datos);
            $this->reemplazarPermisosExtra($usuarioId, $permisosExtraIds);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** @param array<int> $permisoIds */
    public function reemplazarPermisosExtra(int $usuarioId, array $permisoIds): void
    {
        $del = $this->db->prepare("DELETE FROM usuarios_permisos WHERE usuario_id = :id");
        $del->execute([':id' => $usuarioId]);
        if ($permisoIds === []) {
            return;
        }
        $ins = $this->db->prepare(
            "INSERT INTO usuarios_permisos (usuario_id, permiso_id) VALUES (:u, :p)"
        );
        foreach (array_unique(array_map('intval', $permisoIds)) as $pid) {
            if ($pid > 0) {
                $ins->execute([':u' => $usuarioId, ':p' => $pid]);
            }
        }
    }

    public function softDelete(int $id): bool
    {
        return $this->update($id, ['activo' => 0]);
    }
}
