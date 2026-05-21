<?php
/**
 * SingletonTrait — Implementación reutilizable del patrón Singleton.
 *
 * Permite a cualquier clase convertirse en Singleton simplemente
 * usando este trait. La clase solo necesita un constructor privado.
 *
 * @package App\Core\Traits
 * @version 1.0.0
 *
 * @example
 * ```php
 * class MyService
 * {
 *     use SingletonTrait;
 *
 *     private function __construct()
 *     {
 *         // inicialización
 *     }
 *
 *     public function doSomething(): void { ... }
 * }
 *
 * // Uso:
 * $service = MyService::getInstance();
 * $service->doSomething();
 * ```
 */

declare(strict_types=1);

namespace App\Core\Traits;

trait SingletonTrait
{
    /** @var static|null Instancia única por clase */
    private static ?self $instance = null;

    /**
     * Devuelve la única instancia de la clase.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Destruye la instancia actual (útil para tests).
     */
    public static function resetInstance(): void
    {
        static::$instance = null;
    }

    /** Previene clonación */
    private function __clone() {}

    /** Previene deserialización */
    public function __wakeup(): void
    {
        throw new \RuntimeException('Cannot unserialize a singleton.');
    }
}
