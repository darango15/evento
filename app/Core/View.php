<?php
/**
 * View — Motor de renderizado de plantillas PHP.
 *
 * Renderiza vistas PHP dentro de layouts con soporte para
 * secciones, datos compartidos y partials.
 *
 * @package App\Core
 * @version 1.0.0
 *
 * @example
 * ```php
 * // Desde un Controller:
 * View::render('events/index', ['events' => $events], 'admin');
 *
 * // Compartir datos con todas las vistas:
 * View::share('appName', 'EventoSaaS');
 *
 * // Dentro de una vista (partial):
 * View::partial('partials/pagination', ['page' => 1]);
 * ```
 */

declare(strict_types=1);

namespace App\Core;

class View
{
    /** @var array Datos compartidos globalmente con todas las vistas */
    private static array $shared = [];

    /** @var string Ruta base de las vistas */
    private static string $viewsPath = '';

    /**
     * Inicializa la ruta base de vistas.
     *
     * @param string $path Ruta absoluta al directorio views/
     */
    public static function setViewsPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/\\');
    }

    /**
     * Comparte una variable con todas las vistas.
     *
     * @param string $key
     * @param mixed  $value
     */
    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * Renderiza una vista dentro de un layout.
     *
     * @param string $view     Ruta relativa a views/ sin extensión (ej: 'events/index')
     * @param array  $data     Variables disponibles en la vista
     * @param string $layout   Layout a usar (nombre en views/layouts/, default 'admin')
     *
     * @throws \RuntimeException Si la vista no existe.
     */
    public static function render(string $view, array $data = [], string $layout = 'admin'): void
    {
        $viewFile   = self::resolvePath($view);
        $layoutFile = self::resolvePath("layouts/{$layout}");

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("Vista no encontrada: {$view} ({$viewFile})");
        }

        // Combinar datos compartidos con los locales (locales tienen precedencia)
        $vars = array_merge(self::$shared, $data);
        extract($vars, EXTR_SKIP);

        // Capturar contenido de la vista
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Renderizar el layout con el $content inyectado
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Renderiza una vista sin layout (partial o fragmento).
     *
     * @param string $view
     * @param array  $data
     */
    public static function partial(string $view, array $data = []): void
    {
        $viewFile = self::resolvePath($view);

        if (!file_exists($viewFile)) {
            return;
        }

        $vars = array_merge(self::$shared, $data);
        extract($vars, EXTR_SKIP);

        require $viewFile;
    }

    /**
     * Captura y devuelve el output de un partial como string.
     *
     * @param  string $view
     * @param  array  $data
     * @return string
     */
    public static function capture(string $view, array $data = []): string
    {
        ob_start();
        self::partial($view, $data);
        return ob_get_clean();
    }

    /**
     * Verifica si una vista existe.
     *
     * @param  string $view
     * @return bool
     */
    public static function exists(string $view): bool
    {
        return file_exists(self::resolvePath($view));
    }

    /**
     * Resuelve la ruta absoluta a un archivo de vista.
     *
     * @param  string $view
     * @return string
     */
    private static function resolvePath(string $view): string
    {
        $base = self::$viewsPath ?: (defined('ROOT_PATH') ? ROOT_PATH . '/views' : '');
        return $base . '/' . ltrim($view, '/') . '.php';
    }
}
