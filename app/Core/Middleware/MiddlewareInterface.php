<?php
/**
 * MiddlewareInterface — Contrato para todos los middlewares.
 *
 * Todos los middlewares deben implementar este contrato,
 * garantizando un punto de entrada uniforme para el Router.
 *
 * @package App\Core\Middleware
 * @version 1.0.0
 *
 * @example
 * ```php
 * class AuthMiddleware implements MiddlewareInterface
 * {
 *     public function handle(): void
 *     {
 *         if (empty($_SESSION['user'])) {
 *             header('Location: /login');
 *             exit;
 *         }
 *     }
 * }
 * ```
 */

declare(strict_types=1);

namespace App\Core\Middleware;

interface MiddlewareInterface
{
    /**
     * Procesa la petición actual.
     *
     * Debe interrumpir la ejecución (exit/redirect) si la condición no se cumple,
     * o simplemente retornar si la petición puede continuar.
     */
    public function handle(): void;
}
