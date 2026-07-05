<?php
/**
 * RedeperuProvider — Proveedor para invoxe.redeperu.com
 *
 * @package WHMCS\FacturacionElectronica\Providers
 */

namespace WHMCS\FacturacionElectronica\Providers;

class RedeperuProvider extends BaseProvider
{
    public function __construct(string $apiKey, string $apiUrl, bool $sandbox = false)
    {
        if (empty($apiUrl)) {
            $apiUrl = 'https://invoxe.redeperu.com';
        }
        parent::__construct($apiKey, $apiUrl, $sandbox);
        
        // El Bearer se configura en FacturacionClient, por defecto es 'Token', pero para Redeperu es 'Bearer'
        $this->extraHeaders['Authorization'] = 'Bearer ' . $apiKey;
    }

    public function emitirComprobante(array $data): array
    {
        $payload = [
            'emisor' => [
                'ruc' => $data['emisor']['ruc'],
            ],
            'receptor' => [
                'tipo_documento' => $data['receptor']['tipo_doc'],
                'ruc'            => $data['receptor']['num_doc'],
                'razon_social'   => $data['receptor']['razon_social'],
                'direccion'      => $data['receptor']['direccion'],
                // Podemos dejar vacío departamento/provincia/distrito o añadirlo si existe
            ],
            'comprobante' => [
                'tipo'                    => $data['tipo_doc'],
                'tipo_venta'              => '0101',
                'serie'                   => $data['serie'],
                // correlativo se asigna automáticamente por el sistema facturador
                'fecha_emision'           => $data['fecha_emision'],
                'hora_emision'            => date('H:i:s'),
                'fecha_vencimiento'       => $data['fecha_emision'],
                'moneda'                  => 'PEN',
                'forma_pago'              => 'Contado',
                'total_op_gravadas'       => $data['subtotal'],
                'total_op_exoneradas'     => 0.00,
                'total_op_inafectas'      => 0.00,
                'igv'                     => $data['igv'],
                'icbper'                  => 0.00,
                'total_antes_impuestos'   => $data['subtotal'],
                'total_impuestos'         => $data['igv'],
                'total_despues_impuestos' => $data['total'],
                'total_a_pagar'           => $data['total'],
            ],
            'items' => array_map(function ($item, $index) {
                // Cálculo simple asumiendo que los totales ya tienen el IGV sumado o separado
                // WHMCS por defecto guarda $item['total'] (amount) sin IGV si aplicamos la fee aparte,
                // Pero en buildComprobanteData, 'total' de item es 'unit_price' de WHMCS, 
                // y buildComprobanteData asume que el invoiceitem de Fee está como otra línea o 
                // se aplica IGV al subtotal.
                
                // Asegurémonos del cálculo correcto
                $valorUnitario = round($item['unit_price'], 5);
                $igvItem = round($item['total'] * 0.18, 2);
                $totalAntesImpuestos = $item['total'];
                
                return [
                    'item'                  => $index + 1,
                    'cantidad'              => $item['quantity'],
                    'unidad'                => 'NIU',
                    'nombre'                => $item['description'],
                    'valor_unitario'        => $valorUnitario,
                    'precio_lista'          => round($valorUnitario * 1.18, 2),
                    'valor_total'           => $totalAntesImpuestos,
                    'igv'                   => $igvItem,
                    'porcentaje_igv'        => 18,
                    'icbper'                => 0.00,
                    'factor_icbper'         => 0.00,
                    'total_antes_impuestos' => $totalAntesImpuestos,
                    'total_impuestos'       => $igvItem,
                    'codigos'               => ["S", "10", "1000", "IGV", "VAT"]
                ];
            }, $data['items'], array_keys($data['items'])),
        ];

        $response = $this->post('/api/comprobantes/enviar', $payload);

        return $this->parseRedeperuResponse($response);
    }

    public function consultarEstado(string $serie, string $correlativo, string $tipo): array
    {
        return ['success' => false, 'error' => 'No soportado externamente'];
    }

    public function anularComprobante(string $serie, string $correlativo, string $tipo): array
    {
        return ['success' => false, 'error' => 'Pendiente de implementación'];
    }

    private function parseRedeperuResponse(array $response): array
    {
        if ($response['http_code'] !== 200 || !empty($response['error']) || (isset($response['body']['estado']) && $response['body']['estado'] === 'ERROR')) {
            $errMsg = $response['body']['mensaje'] ?? $response['error'] ?? 'Error desconocido al emitir en Redeperu';
            return [
                'success' => false,
                'data'    => $response['body'] ?? [],
                'error'   => $errMsg,
            ];
        }

        $body = $response['body'];
        return [
            'success'     => true,
            'data'        => $body,
            'pdf_url'     => $this->absoluteUrl($body['pdfUrl'] ?? ''),
            'xml_url'     => $this->absoluteUrl($body['xmlUrl'] ?? ''),
            'cdr_url'     => $this->absoluteUrl($body['cdrUrl'] ?? ''),
            'error'       => '',
        ];
    }

    /**
     * La API de Redeperu devuelve rutas relativas (ej. /data/pdf/...) que
     * viven en invoxe.redeperu.com, no en el dominio del panel WHMCS.
     */
    private function absoluteUrl(string $url): string
    {
        if ($url === '' || preg_match('#^https?://#i', $url)) {
            return $url;
        }
        return $this->apiUrl . '/' . ltrim($url, '/');
    }
}
