<?php
declare(strict_types=1);

final class Auditoria extends BaseModel
{
    protected string $table = 'auditoria';

    public function registrar(
        string $accion,
        string $entidad,
        ?string $entidadId = null,
        ?string $descripcion = null
    ): int {
        $usuario = $_SESSION['usuario'] ?? null;
        return $this->create([
            'usuario_id'    => $usuario['id']    ?? null,
            'usuario_email' => $usuario['email'] ?? null,
            'accion'        => $accion,
            'entidad'       => $entidad,
            'entidad_id'    => $entidadId,
            'descripcion'   => $descripcion,
            'ip'            => $_SERVER['REMOTE_ADDR']     ?? null,
            'user_agent'    => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
        ]);
    }

    public function contar(array $filtros = []): int
    {
        [$where, $params] = $this->whereFiltros($filtros);
        $sql = "SELECT COUNT(*) FROM auditoria" . ($where !== '' ? " WHERE {$where}" : '');
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, $this->pdoType($v));
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public function listar(array $filtros = [], int $limit = 10, int $offset = 0): array
    {
        [$where, $params] = $this->whereFiltros($filtros);
        $sql = "
            SELECT a.*, u.nombre AS usuario_nombre
              FROM auditoria a
              LEFT JOIN usuarios u ON u.id = a.usuario_id"
            . ($where !== '' ? " WHERE {$where}" : '')
            . " ORDER BY a.created_at DESC, a.id DESC
              LIMIT :__limit__ OFFSET :__offset__";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, $this->pdoType($v));
        }
        $stmt->bindValue(':__limit__',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':__offset__', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function whereFiltros(array $f): array
    {
        $clauses = [];
        $params  = [];
        if (!empty($f['entidad'])) {
            $clauses[] = 'a.entidad = :f_entidad';
            $params['f_entidad'] = $f['entidad'];
        }
        if (!empty($f['accion'])) {
            $clauses[] = 'a.accion = :f_accion';
            $params['f_accion'] = $f['accion'];
        }
        if (!empty($f['usuario_id'])) {
            $clauses[] = 'a.usuario_id = :f_usuario_id';
            $params['f_usuario_id'] = (int) $f['usuario_id'];
        }
        return [implode(' AND ', $clauses), $params];
    }
}
