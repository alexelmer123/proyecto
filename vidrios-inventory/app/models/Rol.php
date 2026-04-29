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
}
