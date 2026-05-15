<?php
declare(strict_types=1);

final class Rol extends BaseModel
{
    protected string $table = 'roles';

    /** @return array<int, array<string, mixed>> */
    public function listadoConPermisos(): array
    {
        $sql = "
            SELECT r.id, r.nombre, r.descripcion, r.activo,
                   COUNT(rp.permiso_id) AS total_permisos,
                   GROUP_CONCAT(p.codigo ORDER BY p.modulo, p.codigo SEPARATOR ', ') AS codigos
              FROM roles r
              LEFT JOIN roles_permisos rp ON rp.rol_id = r.id
              LEFT JOIN permisos p ON p.id = rp.permiso_id
             GROUP BY r.id
             ORDER BY r.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    /** @return array<int, array<string, mixed>> Roles activos sólo (para selects). */
    public function activos(): array
    {
        return $this->db->query(
            "SELECT id, nombre, descripcion FROM roles WHERE activo = 1 ORDER BY nombre"
        )->fetchAll();
    }

    /** @return array<string> Códigos de permiso de un rol por id. */
    public function permisosDeRolId(int $rolId): array
    {
        $stmt = $this->db->prepare("
            SELECT p.codigo
              FROM roles_permisos rp
              JOIN permisos p ON p.id = rp.permiso_id
             WHERE rp.rol_id = :id
        ");
        $stmt->execute([':id' => $rolId]);
        return array_column($stmt->fetchAll(), 'codigo');
    }

    /** @return array<int> Ids de permiso del rol. */
    public function idsPermisoDeRolId(int $rolId): array
    {
        $stmt = $this->db->prepare(
            "SELECT permiso_id FROM roles_permisos WHERE rol_id = :id"
        );
        $stmt->execute([':id' => $rolId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'permiso_id'));
    }

    /** @return array<string> Lista de codigos de permiso para un rol por nombre. */
    public function permisosDeRolNombre(string $rolNombre): array
    {
        $stmt = $this->db->prepare("
            SELECT p.codigo
              FROM roles r
              JOIN roles_permisos rp ON rp.rol_id = r.id
              JOIN permisos p ON p.id = rp.permiso_id
             WHERE r.nombre = :n AND r.activo = 1
        ");
        $stmt->execute([':n' => $rolNombre]);
        return array_column($stmt->fetchAll(), 'codigo');
    }

    public function findByNombre(string $nombre, ?int $excluirId = null): ?array
    {
        $sql = "SELECT * FROM roles WHERE nombre = :n";
        $params = [':n' => $nombre];
        if ($excluirId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql . " LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Crea un rol con sus permisos en una sola transacción.
     * @param array<int> $permisoIds
     */
    public function crearConPermisos(array $datos, array $permisoIds): int
    {
        $this->db->beginTransaction();
        try {
            $rolId = $this->create($datos);
            $this->reemplazarPermisos($rolId, $permisoIds);
            $this->db->commit();
            return $rolId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza un rol y reemplaza por completo sus permisos.
     * @param array<int> $permisoIds
     */
    public function actualizarConPermisos(int $rolId, array $datos, array $permisoIds): void
    {
        $this->db->beginTransaction();
        try {
            $this->update($rolId, $datos);
            $this->reemplazarPermisos($rolId, $permisoIds);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Borra y re-inserta la relación rol_permiso. @param array<int> $permisoIds */
    public function reemplazarPermisos(int $rolId, array $permisoIds): void
    {
        $del = $this->db->prepare("DELETE FROM roles_permisos WHERE rol_id = :id");
        $del->execute([':id' => $rolId]);
        if ($permisoIds === []) {
            return;
        }
        $ins = $this->db->prepare(
            "INSERT INTO roles_permisos (rol_id, permiso_id) VALUES (:r, :p)"
        );
        foreach (array_unique(array_map('intval', $permisoIds)) as $pid) {
            if ($pid > 0) {
                $ins->execute([':r' => $rolId, ':p' => $pid]);
            }
        }
    }

    public function softDelete(int $id): bool
    {
        return $this->update($id, ['activo' => 0]);
    }

    public function contarUsuarios(int $rolId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM usuarios WHERE rol_id = :id AND activo = 1"
        );
        $stmt->execute([':id' => $rolId]);
        return (int) $stmt->fetchColumn();
    }
}
