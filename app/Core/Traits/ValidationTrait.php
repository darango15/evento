<?php
/**
 * ValidationTrait — Reglas de validación reutilizables para Controllers y Services.
 *
 * Proporciona un conjunto de métodos para validar datos de formularios
 * con mensajes de error en español y soporte para reglas encadenables.
 *
 * @package App\Core\Traits
 * @version 1.0.0
 *
 * @example
 * ```php
 * class EventController extends Controller
 * {
 *     use ValidationTrait;
 *
 *     public function store(): void
 *     {
 *         $errors = $this->validate($_POST, [
 *             'name'       => 'required|min:3|max:200',
 *             'email'      => 'required|email',
 *             'start_date' => 'required|date',
 *             'capacity'   => 'nullable|integer|min:1',
 *         ]);
 *
 *         if (!empty($errors)) { ... }
 *     }
 * }
 * ```
 */

declare(strict_types=1);

namespace App\Core\Traits;

trait ValidationTrait
{
    /** @var array<string, string> Errores de la última validación */
    protected array $validationErrors = [];

    /**
     * Valida un array de datos contra un conjunto de reglas.
     *
     * @param  array  $data  Los datos a validar (normalmente $_POST)
     * @param  array  $rules Reglas: ['campo' => 'regla1|regla2:param', ...]
     * @param  array  $labels Etiquetas personalizadas para mensajes: ['campo' => 'Nombre legible']
     * @return array  Errores encontrados: ['campo' => 'Mensaje de error']
     */
    protected function validate(array $data, array $rules, array $labels = []): array
    {
        $this->validationErrors = [];

        foreach ($rules as $field => $ruleString) {
            $value     = $data[$field] ?? null;
            $fieldRules = explode('|', $ruleString);
            $label     = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $nullable  = in_array('nullable', $fieldRules, true);

            // Si es nullable y está vacío, saltar otras reglas
            if ($nullable && ($value === null || $value === '')) {
                continue;
            }

            foreach ($fieldRules as $rule) {
                if ($rule === 'nullable') continue;

                $error = $this->applyRule($rule, $field, $value, $data, $label);
                if ($error !== null) {
                    $this->validationErrors[$field] = $error;
                    break; // Solo el primer error por campo
                }
            }
        }

        return $this->validationErrors;
    }

    /**
     * Verifica si la última validación pasó sin errores.
     */
    protected function passes(): bool
    {
        return empty($this->validationErrors);
    }

    /**
     * Aplica una regla individual a un valor.
     *
     * @param  string      $rule   Nombre de la regla, ej: 'min:3'
     * @param  string      $field  Nombre del campo
     * @param  mixed       $value  Valor a validar
     * @param  array       $data   Todos los datos (para reglas cruzadas como 'confirmed')
     * @param  string      $label  Etiqueta del campo para mensajes
     * @return string|null Mensaje de error o null si pasa
     */
    private function applyRule(string $rule, string $field, mixed $value, array $data, string $label): ?string
    {
        // Separar nombre de regla y parámetro: 'min:3' → ['min', '3']
        [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);

        return match ($ruleName) {
            'required'  => $this->ruleRequired($value, $label),
            'email'     => $this->ruleEmail($value, $label),
            'min'       => $this->ruleMin($value, (int)$param, $label),
            'max'       => $this->ruleMax($value, (int)$param, $label),
            'integer'   => $this->ruleInteger($value, $label),
            'numeric'   => $this->ruleNumeric($value, $label),
            'date'      => $this->ruleDate($value, $label),
            'url'       => $this->ruleUrl($value, $label),
            'in'        => $this->ruleIn($value, explode(',', $param ?? ''), $label),
            'confirmed' => $this->ruleConfirmed($value, $data[$field . '_confirmation'] ?? null, $label),
            'unique_slug' => null, // manejado externamente
            default     => null,
        };
    }

    private function ruleRequired(mixed $value, string $label): ?string
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return "El campo {$label} es requerido.";
        }
        return null;
    }

    private function ruleEmail(mixed $value, string $label): ?string
    {
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "El campo {$label} debe ser un email válido.";
        }
        return null;
    }

    private function ruleMin(mixed $value, int $min, string $label): ?string
    {
        if (is_string($value) && mb_strlen($value) < $min) {
            return "El campo {$label} debe tener al menos {$min} caracteres.";
        }
        if (is_numeric($value) && (float)$value < $min) {
            return "El campo {$label} debe ser al menos {$min}.";
        }
        return null;
    }

    private function ruleMax(mixed $value, int $max, string $label): ?string
    {
        if (is_string($value) && mb_strlen($value) > $max) {
            return "El campo {$label} no puede tener más de {$max} caracteres.";
        }
        if (is_numeric($value) && (float)$value > $max) {
            return "El campo {$label} no puede ser mayor a {$max}.";
        }
        return null;
    }

    private function ruleInteger(mixed $value, string $label): ?string
    {
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            return "El campo {$label} debe ser un número entero.";
        }
        return null;
    }

    private function ruleNumeric(mixed $value, string $label): ?string
    {
        if ($value !== '' && !is_numeric($value)) {
            return "El campo {$label} debe ser un valor numérico.";
        }
        return null;
    }

    private function ruleDate(mixed $value, string $label): ?string
    {
        if ($value !== '' && strtotime((string)$value) === false) {
            return "El campo {$label} debe ser una fecha válida.";
        }
        return null;
    }

    private function ruleUrl(mixed $value, string $label): ?string
    {
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            return "El campo {$label} debe ser una URL válida.";
        }
        return null;
    }

    private function ruleIn(mixed $value, array $options, string $label): ?string
    {
        if ($value !== '' && !in_array($value, $options, true)) {
            $list = implode(', ', $options);
            return "El campo {$label} debe ser uno de: {$list}.";
        }
        return null;
    }

    private function ruleConfirmed(mixed $value, mixed $confirmation, string $label): ?string
    {
        if ($value !== $confirmation) {
            return "La confirmación de {$label} no coincide.";
        }
        return null;
    }
}
