<?php

namespace App\Models;

use Core\Database;
use PDO;

/**
 * BaseModel
 * Modelo base con operaciones CRUD comunes.
 * Todos los modelos de la app extienden de este.
 * Equivalente a Eloquent Model de Laravel (versión ligera).
 */
abstract class BaseModel
{
    protected string $table  = '';
    protected string $pk     = 'id';
    protected PDO    $db;

    public function __construct()
    {
        $this->db = Database::get();
    }

    /** Busca un registro por su PK */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->pk} = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /** Busca por una condición simple */
    public function findBy(string $column, mixed $value): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$column} = ? LIMIT 1"
        );
        $stmt->execute([$value]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /** Retorna todos los registros de la tabla */
    public function all(string $orderBy = ''): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) $sql .= " ORDER BY {$orderBy}";
        return $this->db->query($sql)->fetchAll();
    }

    /** Inserta un nuevo registro y retorna el ID generado */
    public function create(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})"
        );
        $stmt->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    /** Actualiza un registro por su PK */
    public function update(int $id, array $data): bool
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET {$set} WHERE {$this->pk} = ?"
        );
        return $stmt->execute([...array_values($data), $id]);
    }

    /** Elimina un registro por su PK (soft delete si tiene columna 'estado') */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE {$this->pk} = ?"
        );
        return $stmt->execute([$id]);
    }

    /** Desactiva un registro cambiando estado a 'inactivo' */
    public function deactivate(int $id): bool
    {
        return $this->update($id, ['estado' => 'inactivo']);
    }

    /** Cuenta registros con condición opcional */
    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if ($where) $sql .= " WHERE {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Ejecuta una query personalizada y retorna todos los resultados */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Ejecuta una query y retorna un solo resultado */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /** Ejecuta una query de escritura (INSERT/UPDATE/DELETE) */
    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /** Inicia una transacción */
    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /** Confirma una transacción */
    public function commit(): void
    {
        $this->db->commit();
    }

    /** Revierte una transacción */
    public function rollback(): void
    {
        $this->db->rollBack();
    }
}
