<?php
declare(strict_types=1);

/**
 * Model — clase base abstracta. Todas las consultas usan prepared statements.
 */
abstract class Model
{
    protected PDO $db;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** @return array<int, array<string, mixed>> */
    public function findAll(string $orderBy = ''): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($orderBy !== '') {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        return $this->db->query($sql)->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $columns      = array_keys($data);
        $placeholders = array_map(static fn(string $c): string => ':' . $c, $columns);
        $colSql       = implode(', ', array_map(static fn(string $c): string => "`{$c}`", $columns));
        $valSql       = implode(', ', $placeholders);

        $sql  = "INSERT INTO `{$this->table}` ({$colSql}) VALUES ({$valSql})";
        $stmt = $this->db->prepare($sql);

        foreach ($data as $col => $val) {
            $stmt->bindValue(':' . $col, $val, $this->pdoType($val));
        }
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        if ($data === []) {
            return false;
        }
        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = "`{$col}` = :{$col}";
        }
        $sql  = "UPDATE `{$this->table}` SET " . implode(', ', $sets)
              . " WHERE `{$this->primaryKey}` = :__pk__";
        $stmt = $this->db->prepare($sql);

        foreach ($data as $col => $val) {
            $stmt->bindValue(':' . $col, $val, $this->pdoType($val));
        }
        $stmt->bindValue(':__pk__', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    protected function pdoType(mixed $value): int
    {
        return match (true) {
            is_int($value)   => PDO::PARAM_INT,
            is_bool($value)  => PDO::PARAM_BOOL,
            is_null($value)  => PDO::PARAM_NULL,
            default          => PDO::PARAM_STR,
        };
    }
}
