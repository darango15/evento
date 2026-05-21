<?php
/**
 * User — Modelo de usuarios (admin/staff de tenants).
 *
 * @package App\Models
 * @version 1.0.0
 *
 * @example
 * ```php
 * // Autenticar usuario
 * $user = User::authenticate('admin@demo.com', 'password123', $tenantId);
 *
 * // Crear usuario
 * $id = User::create([
 *     'tenant_id' => 1,
 *     'email'     => 'staff@demo.com',
 *     'password'  => password_hash('pass', PASSWORD_ARGON2ID),
 *     'name'      => 'Staff Demo',
 *     'role'      => 'staff',
 * ]);
 * ```
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'tenant_id', 'email', 'password', 'name',
        'role', 'is_active', 'last_login', 'remember_token', 'api_token'
    ];

    /**
     * Autentica un usuario por email, contraseña y tenant.
     *
     * @param  string   $email
     * @param  string   $password  Contraseña en texto plano
     * @param  int|null $tenantId  NULL para superadmin central
     * @return array|null          Datos del usuario o null si falla
     */
    public static function authenticate(string $email, string $password, ?int $tenantId): ?array
    {
        if ($tenantId !== null) {
            $sql = "SELECT * FROM users
                    WHERE email = :email AND tenant_id = :tid AND is_active = 1
                    LIMIT 1";
            $user = self::rawQueryFirst($sql, [':email' => $email, ':tid' => $tenantId]);
        } else {
            // Root domain: prefer superadmin (tenant_id IS NULL), fall back to any matching user
            $sql = "SELECT * FROM users
                    WHERE email = :email AND tenant_id IS NULL AND is_active = 1
                    LIMIT 1";
            $user = self::rawQueryFirst($sql, [':email' => $email]);

            if (!$user) {
                $sql  = "SELECT * FROM users
                         WHERE email = :email AND is_active = 1
                         ORDER BY FIELD(role,'superadmin','owner','admin','staff')
                         LIMIT 1";
                $user = self::rawQueryFirst($sql, [':email' => $email]);
            }
        }

        if (!$user) return null;

        if (!password_verify($password, $user['password'])) return null;

        // Actualizar último login
        self::update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);

        // No devolver el hash en la sesión
        unset($user['password'], $user['remember_token']);

        return $user;
    }

    /**
     * Devuelve todos los usuarios de un tenant.
     *
     * @param  int $tenantId
     * @return array
     */
    public static function byTenant(int $tenantId): array
    {
        return self::rawQuery(
            "SELECT id, email, name, role, is_active, last_login, created_at
             FROM users WHERE tenant_id = :tid ORDER BY name ASC",
            [':tid' => $tenantId]
        );
    }

    /**
     * Verifica si un email ya existe en un tenant.
     *
     * @param  string   $email
     * @param  int|null $tenantId
     * @param  int|null $excludeId ID a excluir (para updates)
     * @return bool
     */
    public static function emailExists(string $email, ?int $tenantId, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $sql = "SELECT id FROM users WHERE email = :email AND tenant_id <=> :tid AND id != :eid LIMIT 1";
            $row = self::rawQueryFirst($sql, [':email' => $email, ':tid' => $tenantId, ':eid' => $excludeId]);
        } else {
            $sql = "SELECT id FROM users WHERE email = :email AND tenant_id <=> :tid LIMIT 1";
            $row = self::rawQueryFirst($sql, [':email' => $email, ':tid' => $tenantId]);
        }

        return $row !== null;
    }

    /**
     * Crea el hash de contraseña con Argon2id.
     *
     * @param  string $password
     * @return string
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost'   => 4,
            'threads'     => 1,
        ]);
    }
}
