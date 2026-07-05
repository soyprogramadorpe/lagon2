<?php
/**
 * FacturacionClient — HTTP Client base para la API del proveedor
 *
 * Fase 2: Envuelve cURL para comunicarse con Nubefact, eFact, o SUNAT directa.
 * El proveedor concreto extiende esta clase e implementa los métodos abstractos.
 *
 * @package WHMCS\FacturacionElectronica\Api
 */

namespace WHMCS\FacturacionElectronica\Api;

abstract class FacturacionClient
{
    protected string $apiKey;
    protected string $apiUrl;
    protected bool   $sandbox;
    protected int    $timeout = 30;

    /** @var array<string,string> Headers extra que cada provider puede agregar */
    protected array $extraHeaders = [];

    public function __construct(string $apiKey, string $apiUrl, bool $sandbox = false)
    {
        $this->apiKey  = $apiKey;
        $this->apiUrl  = rtrim($apiUrl, '/');
        $this->sandbox = $sandbox;
    }

    // =========================================================================
    // Métodos abstractos — cada provider los implementa
    // =========================================================================

    /**
     * Emite una Factura (tipo 01) o Boleta (tipo 03).
     *
     * @param  array $data  Datos normalizados del comprobante
     * @return array        ['success'=>bool, 'data'=>array, 'error'=>string]
     */
    abstract public function emitirComprobante(array $data): array;

    /**
     * Consulta el estado de un comprobante ya emitido.
     */
    abstract public function consultarEstado(string $serie, string $correlativo, string $tipo): array;

    /**
     * Anula un comprobante emitido.
     */
    abstract public function anularComprobante(string $serie, string $correlativo, string $tipo): array;

    // =========================================================================
    // HTTP helpers
    // =========================================================================

    /**
     * POST JSON al endpoint dado
     *
     * @param  string $endpoint  Ruta relativa, e.g. "/api/v1/invoice"
     * @param  array  $payload
     * @return array  ['http_code'=>int, 'body'=>array|string, 'error'=>string]
     */
    protected function post(string $endpoint, array $payload): array
    {
        return $this->request('POST', $endpoint, $payload);
    }

    /**
     * GET con query params opcionales
     */
    protected function get(string $endpoint, array $params = []): array
    {
        $url = $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $this->request('GET', $url);
    }

    /**
     * Ejecuta la petición cURL y devuelve un array normalizado.
     */
    private function request(string $method, string $endpoint, array $payload = []): array
    {
        $url = $this->apiUrl . '/' . ltrim($endpoint, '/');

        // extraHeaders puede sobreescribir Authorization (p.ej. Bearer en vez de Token)
        $headerMap = array_merge([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Token ' . $this->apiKey,
        ], $this->extraHeaders);

        $headers = [];
        foreach ($headerMap as $name => $value) {
            $headers[] = "{$name}: {$value}";
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => !$this->sandbox,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $this->log('error', "cURL error on {$method} {$url}: {$curlErr}");
            return ['http_code' => 0, 'body' => [], 'error' => $curlErr];
        }

        $body = json_decode($raw, true) ?? $raw;

        $this->log('info', "{$method} {$url} → HTTP {$httpCode}");

        return [
            'http_code' => $httpCode,
            'body'      => $body,
            'error'     => ($httpCode >= 400) ? $this->extractError($body) : '',
        ];
    }

    // =========================================================================
    // Helpers internos
    // =========================================================================

    /**
     * Extrae el mensaje de error del body de la respuesta (override por provider si necesita).
     */
    protected function extractError(mixed $body): string
    {
        if (is_array($body)) {
            return $body['errors'][0]['message']
                ?? $body['error']
                ?? $body['message']
                ?? 'Error desconocido del proveedor';
        }
        return is_string($body) ? $body : 'Error desconocido';
    }

    /**
     * Registra en el activity log de WHMCS.
     */
    protected function log(string $level, string $message): void
    {
        if (function_exists('logActivity')) {
            logActivity("[FacturacionElectronica][{$level}] {$message}");
        }
    }

    // =========================================================================
    // Normalización de datos (helper para los providers)
    // =========================================================================

    /**
     * Construye el array base de un comprobante desde una WHMCS invoice.
     * Los providers pueden extender / sobreescribir campos específicos.
     *
     * @param  array $invoice  Row de tblinvoices
     * @param  array $client   Row de tblclients + mod_fe_clientes_fiscales
     * @param  array $items    Rows de tblinvoiceitems
     * @param  array $config   Config del módulo
     */
    public static function buildComprobanteData(
        array $invoice,
        array $client,
        array $items,
        array $config
    ): array {
        $subtotal  = 0;
        $lineas    = [];

        foreach ($items as $item) {
            $amount     = (float) $item['amount'];
            $subtotal  += $amount;
            $lineas[]   = [
                'description' => $item['description'],
                'quantity'    => 1,
                'unit_price'  => $amount,
                'total'       => $amount,
            ];
        }

        $igvRate   = ((float) ($config['igv_rate'] ?? 18)) / 100;
        $igv       = round($subtotal * $igvRate, 2);
        $total     = $subtotal + $igv;

        return [
            'tipo_doc'       => '01',                          // Factura
            'serie'          => $config['serie'] ?? 'F001',
            'correlativo'    => $invoice['id'],
            'fecha_emision'  => date('Y-m-d'),
            'emisor'         => [
                'ruc'          => $config['ruc_emisor'] ?? '',
                'razon_social' => $config['razon_social_emisor'] ?? '',
            ],
            'receptor'       => [
                'tipo_doc'     => $client['tipo_doc_id'] ?? '6',
                'num_doc'      => $client['num_doc'] ?? '',
                'razon_social' => $client['razon_social'] ?? $client['companyname'] ?? '',
                'direccion'    => $client['direccion'] ?? $client['address1'] ?? '',
            ],
            'items'          => $lineas,
            'subtotal'       => $subtotal,
            'igv'            => $igv,
            'total'          => $total,
            'moneda'         => 'PEN',
            'observaciones'  => 'Factura WHMCS #' . $invoice['id'],
        ];
    }
}
