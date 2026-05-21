<?php
/**
 * Database — Conexión PDO con patrón Singleton.
 *
 * Gestiona una única conexión PDO compartida en toda la aplicación.
 * Configurable vía variables de entorno (.env).
 *
 * @package App\Core
 * @author  EventoSaaS Team
 * @version 1.0.0
 *
 * @example
 * ```php
 * $db  = Database::getInstance();
 * $pdo = $db->getConnection();
 *
 * $stmt = $pdo->prepare('SELECT * FROM events WHERE tenant_id = :tid');
 * $stmt->execute([':tid' => 1]);
 * $rows = $stmt->fetchAll();
 * ```
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    /** @var Database|null Instancia única (Singleton) */
    private static ?Database $instance = null;

    /** @var PDO Conexión activa */
    private PDO $pdo;

    /**
     * Constructor privado — usa getInstance().
     *
     * Lee las credenciales de las constantes de entorno ya cargadas.
     *
     * @throws RuntimeException Si la conexión falla.
     */
    private function __construct()
    {
        $host    = env('DB_HOST', '127.0.0.1');
        $port    = env('DB_PORT', '3306');
        $dbname  = env('DB_NAME', 'evento_saas');
        $user    = env('DB_USER', 'root');
        $pass    = env('DB_PASS', '');
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // No exponemos credenciales en producción
            $message = env('APP_DEBUG', 'false') === 'true'
                ? 'Error de conexión DB: ' . $e->getMessage()
                : 'No se pudo conectar a la base de datos. Por favor, inténtalo más tarde.';

            throw new RuntimeException($message, (int) $e->getCode(), $e);
        }
    }

    /**
     * Devuelve la única instancia del Database.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Devuelve la conexión PDO subyacente.
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Ejecuta una query preparada y devuelve el Statement.
     *
     * @param string $sql    SQL con placeholders nombrados (:param).
     * @param array  $params Parámetros a enlazar.
     * @return \PDOStatement
     *
     * @example
     * ```php
     * $stmt = Database::getInstance()->query(
     *     'SELECT * FROM attendees WHERE check_in_code = :code',
     *     [':code' => 'ABC123']
     * );
     * $row = $stmt->fetch();
     * ```
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();
        return $stmt;
    }

    /**
     * Devuelve el último ID insertado.
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Inicia una transacción.
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Confirma la transacción actual.
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Revierte la transacción actual.
     */
    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /** Prevenir copia */
    private function __clone() {}

    /** Prevenir deserialización */
    public function __wakeup()
    {
        throw new RuntimeException('Cannot unserialize singleton.');
    }
}
