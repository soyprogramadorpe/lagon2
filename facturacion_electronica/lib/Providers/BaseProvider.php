<?php
/**
 * BaseProvider — Contrato y factory para los providers de Fase 2
 *
 * Uso:
 *   $provider = BaseProvider::make($config);
 *   $result   = $provider->emitirComprobante($data);
 *
 * @package WHMCS\FacturacionElectronica\Providers
 */

namespace WHMCS\FacturacionElectronica\Providers;

use WHMCS\FacturacionElectronica\Api\FacturacionClient;

// =============================================================================
// Factory
// =============================================================================

abstract class BaseProvider extends FacturacionClient
{
    /**
     * Crea el provider correcto según la config del addon.
     *
     * @param  array $config  ['provider'=>'nubefact', 'api_key'=>'...', ...]
     * @throws \RuntimeException si el provider no está implementado
     */
    public static function make(array $config): FacturacionClient
    {
        $provider = strtolower($config['provider'] ?? 'none');
        $apiKey   = $config['api_key']      ?? '';
        $apiUrl   = $config['api_url']      ?? '';
        $sandbox  = ($config['sandbox_mode'] ?? 'yes') === 'yes';

        switch ($provider) {
            case 'redeperu':
                return new RedeperuProvider($apiKey, $apiUrl, $sandbox);

            case 'nubefact':
                return new NubefactProvider($apiKey, $apiUrl, $sandbox);

            case 'efact':
                // TODO Fase 2: implementar EfactProvider
                throw new \RuntimeException('eFact provider no implementado aún (Fase 2).');

            case 'sunat_direct':
                // TODO Fase 2: implementar SunatDirectProvider
                throw new \RuntimeException('SUNAT Direct provider no implementado aún (Fase 2).');

            default:
                throw new \RuntimeException("Provider '{$provider}' no reconocido.");
        }
    }
}

// =============================================================================
// Nubefact Provider  (Fase 2 — estructura lista, lógica pendiente)
// Docs: https://nubefact.com/api/
// =============================================================================

class NubefactProvider extends BaseProvider
{
    // Nubefact usa "Bearer" en lugar de "Token"
    public function __construct(string $apiKey, string $apiUrl, bool $sandbox = false)
    {
        // URL por defecto de Nubefact si no se configuró
        if (empty($apiUrl)) {
            $apiUrl = $sandbox
                ? 'https://demo.nubefact.com/api/v1'
                : 'https://nubefact.com/api/v1';
        }

        parent::__construct($apiKey, $apiUrl, $sandbox);

        // Override del header de auth para Nubefact
        $this->extraHeaders['Authorization'] = 'Bearer ' . $apiKey;
    }

    /**
     * Emite un comprobante vía Nubefact API.
     *
     * TODO Fase 2: mapear $data al schema de Nubefact y procesar la respuesta.
     */
    public function emitirComprobante(array $data): array
    {
        // ── Mapeo al formato Nubefact ─────────────────────────────────────────
        $payload = [
            'operacion'              => 'generar_comprobante',
            'tipo_de_comprobante'    => (int) $data['tipo_doc'],   // 1=factura, 3=boleta
            'serie'                  => $data['serie'],
            'numero'                 => (int) $data['correlativo'],
            'sunat_transaction'      => 1,
            'cliente_tipo_de_documento' => (int) $data['receptor']['tipo_doc'],
            'cliente_numero_de_documento' => $data['receptor']['num_doc'],
            'cliente_denominacion'   => $data['receptor']['razon_social'],
            'cliente_direccion'      => $data['receptor']['direccion'] ?? '',
            'fecha_de_emision'       => $data['fecha_emision'],
            'moneda'                 => 1,  // 1=PEN, 2=USD
            'porcentaje_de_igv'      => 18.00,
            'items'                  => array_map(function ($item) {
                return [
                    'unidad_de_medida'    => 'ZZ',
                    'codigo'              => 'SERV',
                    'descripcion'         => $item['description'],
                    'cantidad'            => $item['quantity'],
                    'valor_unitario'      => round($item['unit_price'] / 1.18, 4),
                    'precio_unitario'     => $item['unit_price'],
                    'subtotal'            => $item['total'],
                    'tipo_de_igv'         => 1,
                    'igv'                 => round($item['total'] - ($item['total'] / 1.18), 4),
                    'total'               => $item['total'],
                ];
            }, $data['items']),
            'subtotal'               => $data['subtotal'],
            'igv'                    => $data['igv'],
            'total'                  => $data['total'],
            'observaciones'          => $data['observaciones'] ?? '',
            'enviar_automaticamente_a_la_sunat'    => true,
            'enviar_automaticamente_al_cliente'    => false,
        ];

        // ── TODO Fase 2: descomentar cuando se configure el API ───────────────
        // $response = $this->post('/emitir', $payload);
        // return $this->parseNubefactResponse($response);

        // ── Placeholder hasta Fase 2 ──────────────────────────────────────────
        return [
            'success' => false,
            'data'    => [],
            'error'   => 'Nubefact: integración pendiente de activación (Fase 2).',
            'payload' => $payload,  // Para debug
        ];
    }

    public function consultarEstado(string $serie, string $correlativo, string $tipo): array
    {
        // TODO Fase 2
        return ['success' => false, 'error' => 'No implementado (Fase 2)'];
    }

    public function anularComprobante(string $serie, string $correlativo, string $tipo): array
    {
        // TODO Fase 2
        return ['success' => false, 'error' => 'No implementado (Fase 2)'];
    }

    /**
     * Parsea la respuesta específica de Nubefact.
     * TODO Fase 2: implementar según documentación.
     */
    private function parseNubefactResponse(array $response): array
    {
        if ($response['http_code'] !== 200 || !empty($response['error'])) {
            return [
                'success' => false,
                'data'    => $response['body'] ?? [],
                'error'   => $response['error'] ?? 'Error desconocido de Nubefact',
            ];
        }

        $body = $response['body'];

        return [
            'success'     => true,
            'data'        => $body,
            'serie'       => $body['serie']       ?? '',
            'correlativo' => $body['numero']       ?? '',
            'pdf_url'     => $body['enlace_del_pdf']  ?? '',
            'xml_url'     => $body['enlace_del_xml']  ?? '',
            'error'       => '',
        ];
    }
}
