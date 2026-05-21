<?php
/**
 * Model — Clase base abstracta para todos los modelos.
 *
 * Implementa operaciones CRUD básicas usando PDO con prepared statements.
 * Cada modelo hijo debe definir $table y opcionalmente $fillable.
 *
 * @package App\Core
 * @version 1.0.0
 *
 * @example
 * ```php
 * class Event extends Model
 * {
 *     protected string $table    = 'events';
 *     protected array  $fillable = ['tenant_id', 'name', 'slug', 'status'];
 * }
 *
 * // Uso:
 * $event  = Event::find(1);
 * $events = Event::where('tenant_id', 3);
 * $id     = Event::create(['tenant_id' => 1, 'name' => 'Tech Summit', 'slug' => 'tech-summit', 'status' => 'draft']);
 * Event::update(1, ['status' => 'published']);
 * Event::delete(1);
 * ```
 */

declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use PDO;

abstract class Model
{
    /** @var string Nombre de la tabla en base de datos */
    protected string $table = '';

    /** @var string Clave primaria */
    protected string $primaryKey = 'id';

    /** @var array Columnas permitidas para mass-assignment */
    protected array $fillable = [];

    /** @var Database */
    private static ?Database $db = null;

    /** Obtiene la instancia de Database */
    protected static function db(): Database
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    /** Obtiene la conexión PDO */
    protected static function pdo(): \PDO
    {
        return self::db()->getConnection();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Consultas básicas
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Encuentra un registro por su clave primaria.
     *
     * @param  int|string $id
     * @return array|null
     */
    public static function find(int|string $id): ?array
    {
        $model = new static();
        $sql   = "SELECT * FROM `{$model->table}` WHERE `{$model->primaryKey}` = :id LIMIT 1";
        $stmt  = self::pdo()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Devuelve todos los registros de la tabla.
     *
     * @param  string $orderBy Campo de ordenamiento
     * @param  string $dir     ASC|DESC
     * @return array
     */
    public static function all(string $orderBy = 'id', string $dir = 'ASC'): array
    {
        $model = new static();
        $dir   = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $sql   = "SELECT * FROM `{$model->table}` ORDER BY `{$orderBy}` {$dir}";
        $stmt  = self::pdo()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Filtra registros por una condición simple (columna = valor).
     *
     * @param  string     $column
     * @param  mixed      $value
     * @param  string     $operator '=' | '!=' | '>' | '<' | 'LIKE'
     * @return array
     *
     * @example
     * ```php
     * $published = Event::where('status', 'published');
     * $recent    = Event::where('created_at', '2025-01-01', '>');
     * ```
     */
    public static function where(string $column, mixed $value, string $operator = '='): array
    {
        $model = new static();
        $sql   = "SELECT * FROM `{$model->table}` WHERE `{$column}` {$operator} :value";
        $stmt  = self::pdo()->prepare($sql);
        $stmt->execute([':value' => $value]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve el primer registro que coincida.
     *
     * @param  string $column
     * @param  mixed  $value
     * @return array|null
     */
    public static function firstWhere(string $column, mixed $value): ?array
    {
        $model = new static();
        $sql   = "SELECT * FROM `{$model->table}` WHERE `{$column}` = :value LIMIT 1";
        $stmt  = self::pdo()->prepare($sql);
        $stmt->execute([':value' => $value]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Cuenta los registros con una condición opcional.
     *
     * @param  string $column
     * @param  mixed  $value
     * @return int
     */
    public static function count(string $column = '1', mixed $value = null): int
    {
        $model = new static();

        if ($value !== null) {
            $sql  = "SELECT COUNT(*) FROM `{$model->table}` WHERE `{$column}` = :value";
            $stmt = self::pdo()->prepare($sql);
            $stmt->execute([':value' => $value]);
        } else {
            $sql  = "SELECT COUNT(*) FROM `{$model->table}`";
            $stmt = self::pdo()->query($sql);
        }

        return (int) $stmt->fetchColumn();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Escritura
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Inserta un nuevo registro. Filtra por $fillable si está definido.
     *
     * @param  array $data ['columna' => 'valor', ...]
     * @return int   ID del registro insertado
     *
     * @example
     * ```php
     * $id = Event::create([
     *     'tenant_id' => 1,
     *     'name'      => 'Tech Summit',
     *     'slug'      => 'tech-summit',
     *     'status'    => 'draft',
     * ]);
     * ```
     */
    public static function create(array $data): int
    {
        $model  = new static();
        $data   = $model->filterFillable($data);
        $cols   = array_keys($data);
        $colStr = implode('`, `', $cols);
        $phStr  = implode(', ', array_map(fn($c) => ":{$c}", $cols));

        $sql  = "INSERT INTO `{$model->table}` (`{$colStr}`) VALUES ({$phStr})";
        $stmt = self::pdo()->prepare($sql);

        $params = [];
        foreach ($data as $col => $val) {
            $params[":{$col}"] = $val;
        }

        $stmt->execute($params);
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Actualiza un registro por su clave primaria.
     *
     * @param  int|string $id
     * @param  array      $data
     * @return bool
     */
    public static function update(int|string $id, array $data): bool
    {
        $model  = new static();
        $data   = $model->filterFillable($data);
        $sets   = implode(', ', array_map(fn($c) => "`{$c}` = :{$c}", array_keys($data)));

        $sql  = "UPDATE `{$model->table}` SET {$sets} WHERE `{$model->primaryKey}` = :__id";
        $stmt = self::pdo()->prepare($sql);

        $params = [':__id' => $id];
        foreach ($data as $col => $val) {
            $params[":{$col}"] = $val;
        }

        return $stmt->execute($params);
    }

    /**
     * Elimina un registro por su clave primaria.
     *
     * @param  int|string $id
     * @return bool
     */
    public static function delete(int|string $id): bool
    {
        $model = new static();
        $sql   = "DELETE FROM `{$model->table}` WHERE `{$model->primaryKey}` = :id";
        $stmt  = self::pdo()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Filtra el array de datos para que solo incluya columnas en $fillable.
     * Si $fillable está vacío, permite todos los campos.
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_filter(
            $data,
            fn($key) => in_array($key, $this->fillable, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Ejecuta una query SQL raw con parámetros y devuelve todos los resultados.
     *
     * @param  string $sql
     * @param  array  $params
     * @return array
     */
    public static function rawQuery(string $sql, array $params = []): array
    {
        $stmt = self::db()->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ejecuta una query y devuelve un único resultado.
     *
     * @param  string $sql
     * @param  array  $params
     * @return array|null
     */
    public static function rawQueryFirst(string $sql, array $params = []): ?array
    {
        $stmt   = self::db()->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
