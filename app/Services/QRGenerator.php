<?php
/**
 * QRGenerator — Servicio de generación de códigos QR.
 *
 * Envuelve la librería phpqrcode para generar imágenes PNG de códigos QR
 * con el check_in_code del asistente. Guarda el archivo en public/assets/uploads/qrcodes/.
 *
 * @package App\Services
 * @version 1.0.0
 *
 * @example
 * ```php
 * $qr = new QRGenerator();
 *
 * // Generar QR y obtener la ruta del archivo
 * $path = $qr->generate('A1B2C3D4', 'tenant_1_event_3_A1B2C3D4');
 *
 * // Obtener URL pública
 * $url = $qr->getPublicUrl('tenant_1_event_3_A1B2C3D4');
 * ```
 */

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class QRGenerator
{
    private string $storageDir;
    private string $publicBasePath;
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->storageDir     = PUBLIC_PATH . '/assets/uploads/qrcodes/';
        $this->publicBasePath = '/assets/uploads/qrcodes/';
        $this->apiUrl         = env('QR_API_URL', 'https://qr.innovate.com.pa/api/v1/qr/generate');
        $this->apiKey         = env('QR_API_KEY', '');

        $this->ensureDirectoryExists();
    }

    /**
     * Llama al API externo y devuelve los bytes PNG del QR.
     *
     * @throws RuntimeException Si el API no responde correctamente.
     */
    public function fetchPng(string $content, string $colorHex = '#6d28d9', bool $includeLogo = true): string
    {
        $endpoint = $this->apiUrl . '?' . http_build_query([
            'url'          => $content,
            'color_hex'    => $colorHex,
            'include_logo' => $includeLogo ? 'true' : 'false',
        ]);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['X-API-Key: ' . $this->apiKey],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            throw new RuntimeException("QR API devolvió HTTP {$httpCode}");
        }

        return $response;
    }

    /**
     * Genera el QR, lo guarda en disco y devuelve la ruta pública.
     *
     * @throws RuntimeException
     */
    public function generate(string $content, string $filename, string $colorHex = '#6d28d9'): string
    {
        $png      = $this->fetchPng($content, $colorHex);
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        $filePath = $this->storageDir . $filename . '.png';

        if (file_put_contents($filePath, $png) === false) {
            throw new RuntimeException("No se pudo guardar el QR en: {$filePath}");
        }

        return $this->publicBasePath . $filename . '.png';
    }

    /**
     * Genera el QR para un asistente y lo guarda en disco.
     */
    public function generateForAttendee(string $checkInCode, int $tenantId, int $eventId): string
    {
        $content  = url("/registro/ticket/{$checkInCode}");
        $filename = "t{$tenantId}_e{$eventId}_{$checkInCode}";

        return $this->generate($content, $filename);
    }

    /**
     * Devuelve el QR como data URI base64 (para vistas y emails).
     */
    public function generateBase64(string $content, string $colorHex = '#6d28d9'): string
    {
        try {
            $png = $this->fetchPng($content, $colorHex);
            return 'data:image/png;base64,' . base64_encode($png);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Elimina el archivo QR del disco.
     */
    public function delete(string $relativePath): void
    {
        $absolutePath = PUBLIC_PATH . $relativePath;
        if (file_exists($absolutePath)) {
            unlink($absolutePath);
        }
    }

    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->storageDir) && !mkdir($this->storageDir, 0775, true)) {
            throw new RuntimeException("No se pudo crear el directorio de QRs: {$this->storageDir}");
        }
    }
}
