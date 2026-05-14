<?php
declare(strict_types=1);

/**
 * BaseModel — extensión del Model del core para utilidades comunes
 * de la capa de aplicación (búsqueda paginada, helpers, etc.).
 */
abstract class BaseModel extends Model
{
    /**
     * Devuelve true si existe un registro con la columna/valor dados.
     * Útil para validaciones de unicidad (código de producto, email, etc.).
     */
    public function existsBy(string $column, mixed $value, ?int $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM `{$this->table}` WHERE `{$column}` = :v";
        if ($exceptId !== null) {
            $sql .= " AND `{$this->primaryKey}` <> :pk";
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':v', $value, $this->pdoType($value));
        if ($exceptId !== null) {
            $stmt->bindValue(':pk', $exceptId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }
}
