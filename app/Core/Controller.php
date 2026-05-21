<?php
/**
 * Controller — Clase base para todos los controladores.
 *
 * Proporciona helpers para renderizar vistas, redireccionar,
 * manejar flash messages y responder con JSON.
 *
 * @package App\Core
 * @version 1.0.0
 *
 * @example
 * ```php
 * class EventController extends Controller
 * {
 *     public function index(): void
 *     {
 *         $events = Event::all();
 *         $this->render('events/index', compact('events'));
 *     }
 *
 *     public function store(): void
 *     {
 *         // ... guardar evento
 *         $this->flash('success', 'Evento creado correctamente.');
 *         $this->redirect('/admin/events');
 *     }
 * }
 * ```
 */

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /**
     * Renderiza una vista dentro del layout especificado.
     *
     * @param string $view    Ruta relativa a /views (sin .php), ej: 'events/index'
     * @param array  $data    Variables disponibles en la vista
     * @param string $layout  Nombre del layout en /views/layouts/, default 'admin'
     */
    protected function render(string $view, array $data = [], string $layout = 'admin'): void
    {
        // Extraer variables para que estén disponibles en la vista
        extract($data, EXTR_SKIP);

        $viewFile   = ROOT_PATH . "/views/{$view}.php";
        $layoutFile = ROOT_PATH . "/views/layouts/{$layout}.php";

        if (!file_exists($viewFile)) {
            $this->abort(500, "Vista no encontrada: {$view}");
            return;
        }

        // Capturar el contenido de la vista
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Renderizar el layout con el contenido inyectado
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Renderiza una vista sin layout (para fragmentos o páginas simples).
     *
     * @param string $view
     * @param array  $data
     */
    protected function renderPartial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = ROOT_PATH . "/views/{$view}.php";

        if (file_exists($viewFile)) {
            require $viewFile;
        }
    }

    /**
     * Responde con JSON y termina la ejecución.
     *
     * @param mixed $data
     * @param int   $statusCode
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redirige a otra URL.
     *
     * @param string $url
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Redirige de vuelta al referer o a una URL de fallback.
     *
     * @param string $fallback
     */
    protected function back(string $fallback = '/'): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
        $this->redirect($referer);
    }

    /**
     * Almacena un mensaje flash en la sesión.
     *
     * @param string $type    'success' | 'error' | 'warning' | 'info'
     * @param string $message
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][$type][] = $message;
    }

    /**
     * Verifica que el CSRF token sea válido.
     * Llama abort(403) si no lo es.
     */
    protected function validateCsrf(): void
    {
        $token = $_POST['_token'] ?? '';

        if (!isset($_SESSION['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'], $token)) {
            $this->abort(403, 'Token CSRF inválido.');
        }
    }

    /**
     * Recupera y filtra datos del POST.
     *
     * @param  array $fields Campos permitidos
     * @return array
     */
    protected function input(array $fields): array
    {
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = trim($_POST[$field] ?? '');
        }
        return $data;
    }

    /**
     * Aborta la ejecución mostrando una página de error.
     *
     * @param int    $code    Código HTTP (403, 404, 500)
     * @param string $message
     */
    protected function abort(int $code, string $message = ''): void
    {
        http_response_code($code);
        $errorView = ROOT_PATH . "/views/errors/{$code}.php";

        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo "<h1>Error {$code}</h1><p>{$message}</p>";
        }
        exit;
    }

    /**
     * Verifica si la petición es AJAX (XMLHttpRequest).
     *
     * @return bool
     */
    protected function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    /**
     * Verifica si la petición es POST.
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
