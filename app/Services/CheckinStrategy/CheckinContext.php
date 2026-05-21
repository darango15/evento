<?php
/**
 * CheckinContext — Contexto del patrón Strategy para check-in.
 *
 * Actúa como el "cliente" del patrón: selecciona e invoca la estrategia
 * correcta según el método de check-in requerido.
 *
 * @package App\Services\CheckinStrategy
 * @version 1.0.0
 *
 * @example
 * ```php
 * // Check-in QR
 * $context = new CheckinContext('qr_code');
 * $result  = $context->execute('A1B2C3...', null, ['user_id' => 1]);
 *
 * // Check-in manual por email
 * $context = new CheckinContext('manual');
 * $result  = $context->execute('juan@demo.com', null, ['event_id' => 3]);
 *
 * // Cambiar estrategia en tiempo de ejecución
 * $context->setStrategy('kiosk');
 * ```
 */

declare(strict_types=1);

namespace App\Services\CheckinStrategy;

use InvalidArgumentException;

class CheckinContext
{
    /** @var CheckinStrategyInterface Estrategia activa */
    private CheckinStrategyInterface $strategy;

    /** @var array<string, class-string<CheckinStrategyInterface>> */
    private static array $registry = [
        'qr_code' => QRCheckinStrategy::class,
        'manual'  => ManualCheckinStrategy::class,
        'mobile'  => MobileCheckinStrategy::class,
        'kiosk'   => KioskCheckinStrategy::class,
    ];

    /**
     * @param string $method 'qr_code' | 'manual'
     */
    public function __construct(string $method = 'qr_code')
    {
        $this->setStrategy($method);
    }

    /**
     * Establece la estrategia por nombre.
     *
     * @param  string $method
     * @throws InvalidArgumentException
     */
    public function setStrategy(string $method): void
    {
        if (!isset(self::$registry[$method])) {
            throw new InvalidArgumentException(
                "Método de check-in desconocido: '{$method}'. Disponibles: " .
                implode(', ', array_keys(self::$registry))
            );
        }

        $class          = self::$registry[$method];
        $this->strategy = new $class();
    }

    /**
     * Registra una nueva estrategia personalizada.
     *
     * @param string                             $name
     * @param class-string<CheckinStrategyInterface> $class
     */
    public static function register(string $name, string $class): void
    {
        self::$registry[$name] = $class;
    }

    /**
     * Ejecuta el check-in con la estrategia actual.
     *
     * @param  string   $identifier
     * @param  int|null $sessionId
     * @param  array    $context
     * @return array
     */
    public function execute(string $identifier, ?int $sessionId = null, array $context = []): array
    {
        return $this->strategy->process($identifier, $sessionId, $context);
    }

    /**
     * Devuelve el nombre de la estrategia activa.
     *
     * @return string
     */
    public function getStrategyName(): string
    {
        return $this->strategy->getName();
    }

    /**
     * Lista las estrategias disponibles.
     *
     * @return string[]
     */
    public static function available(): array
    {
        return array_keys(self::$registry);
    }
}
