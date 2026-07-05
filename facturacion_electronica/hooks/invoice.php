<?php
/**
 * Hooks de Invoice — Facturación Electrónica
 *
 * Emisión automática a través del proveedor API cuando se paga una factura.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;
use WHMCS\FacturacionElectronica\Providers\BaseProvider;

add_hook('InvoicePaid', 1, function (array $vars): void {
    $invoiceId = $vars['invoiceid'];

    // 1. Obtener datos de la factura
    $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
    if (!$invoice) return;

    // Verificar si esta factura ya ha sido emitida o intentada
    $existingFe = Capsule::table('mod_fe_facturas')->where('whmcs_invoice_id', $invoiceId)->first();
    if ($existingFe && $existingFe->estado === 'emitida') {
        return; // Ya se emitió con éxito
    }

    // 2. Verificar si requiere factura (por ejemplo, si tiene un fee de IGV o custom field)
    $wantsFactura = false;
    $feeLabel = fe_get_config('fee_label', 'IGV (18%) — Factura Electrónica');

    $feeItem = Capsule::table('tblinvoiceitems')
        ->where('invoiceid', $invoiceId)
        ->where('description', 'like', '%' . $feeLabel . '%')
        ->first();

    if ($feeItem) {
        $wantsFactura = true;
    } else {
        // Mirar custom fields
        $cf = Capsule::table('tblcustomfields')
            ->where('type', 'client')
            ->where('fieldname', 'LIKE', 'Deseo una factura%')
            ->first();
            
        if ($cf) {
            $cfv = Capsule::table('tblcustomfieldsvalues')
                ->where('fieldid', $cf->id)
                ->where('relid', $invoice->userid)
                ->first();
            if ($cfv && $cfv->value === 'on') {
                $wantsFactura = true;
            }
        }
    }

    if (!$wantsFactura) {
        return;
    }

    // 3. Preparar datos para el Provider
    $config = [
        'provider' => fe_get_config('provider', 'none'),
        'api_key' => fe_get_config('api_key', ''),
        'api_url' => fe_get_config('api_url', ''),
        'ruc_emisor' => fe_get_config('ruc_emisor', ''),
        'razon_social_emisor' => fe_get_config('razon_social_emisor', ''),
        'sandbox_mode' => fe_get_config('sandbox_mode', 'yes'),
        'igv_rate' => fe_get_config('igv_rate', '18'),
    ];

    $serieFactura = fe_get_config('serie_factura', 'F001');
    $serieBoleta = fe_get_config('serie_boleta', 'B001');
    $config['serie'] = $serieFactura; // Por defecto

    if ($config['provider'] === 'none') {
        logActivity("[FacturacionElectronica] Factura WHMCS #{$invoiceId} no se pudo emitir. Proveedor no configurado.");
        return;
    }

    // Cliente
    $client = (array) Capsule::table('tblclients')->where('id', $invoice->userid)->first();
    $fiscalClient = (array) Capsule::table('mod_fe_clientes_fiscales')->where('client_id', $invoice->userid)->first();

    // RUC/DNI real que el cliente escribió en el checkout (mod_fe_clientes_fiscales
    // nunca se llena automáticamente, así que esta es la fuente de verdad).
    $ruc = fe_get_client_customfield_value('RUC', $invoice->userid);
    $dni = fe_get_client_customfield_value('DNI', $invoice->userid);

    if (strlen($ruc) === 11) {
        $numDoc = $ruc;
        $tipoDocId = '6';
    } elseif ($dni !== '') {
        $numDoc = $dni;
        $tipoDocId = '1';
    } else {
        $numDoc = '';
        $tipoDocId = '1';
    }

    // Mezclar para tener los datos fiscales a la mano (RUC/DNI real tiene prioridad)
    $clientData = array_merge($client, $fiscalClient ?: [], [
        'num_doc'     => $numDoc,
        'tipo_doc_id' => $tipoDocId,
    ]);

    // Items
    $items = Capsule::table('tblinvoiceitems')
        ->where('invoiceid', $invoiceId)
        ->where('type', '!=', 'Fee') // Excluir la linea del IGV en sí para no sumarla como un servicio
        ->get();
        
    $itemsData = json_decode(json_encode($items), true);

    try {
        $comprobanteData = BaseProvider::buildComprobanteData((array)$invoice, $clientData, $itemsData, $config);

        // Si el cliente no tiene RUC de 11 dígitos, forzar a Boleta
        if (strlen(trim($clientData['num_doc'] ?? '')) < 11) {
            $comprobanteData['tipo_doc'] = '03'; // Boleta
            $comprobanteData['serie'] = $serieBoleta;
            $comprobanteData['receptor']['tipo_doc'] = '1'; // DNI
        } else {
            $comprobanteData['receptor']['tipo_doc'] = '6'; // RUC
            $comprobanteData['serie'] = $serieFactura;
        }

        $provider = BaseProvider::make($config);
        $result = $provider->emitirComprobante($comprobanteData);

        // Registrar o actualizar
        $insertData = [
            'whmcs_invoice_id' => $invoiceId,
            'client_id' => $invoice->userid,
            'serie' => $comprobanteData['serie'],
            'correlativo' => $comprobanteData['correlativo'],
            'tipo_doc' => $comprobanteData['tipo_doc'],
            'ruc_cliente' => $comprobanteData['receptor']['num_doc'],
            'razon_social' => $comprobanteData['receptor']['razon_social'],
            'subtotal' => $comprobanteData['subtotal'],
            'igv' => $comprobanteData['igv'],
            'total' => $comprobanteData['total'],
            'estado' => $result['success'] ? 'emitida' : 'error',
            'provider_response' => json_encode($result['data']),
            'pdf_url' => $result['pdf_url'] ?? '',
            'xml_url' => $result['xml_url'] ?? '',
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existingFe) {
            Capsule::table('mod_fe_facturas')->where('id', $existingFe->id)->update($insertData);
        } else {
            $insertData['created_at'] = date('Y-m-d H:i:s');
            Capsule::table('mod_fe_facturas')->insert($insertData);
        }

        if ($result['success']) {
            logActivity("[FacturacionElectronica] Comprobante emitido OK para Factura WHMCS #{$invoiceId}.");
        } else {
            logActivity("[FacturacionElectronica] Error al emitir comprobante para Factura WHMCS #{$invoiceId}: " . ($result['error'] ?? 'Desconocido'));
        }

    } catch (\Exception $e) {
        logActivity("[FacturacionElectronica] Excepción al emitir Factura WHMCS #{$invoiceId}: " . $e->getMessage());
    }
});

// El enlace "Factura Electrónica" en el menú de Facturación NO se agrega por
// hook: este tema (Lagom2/RSThemes) reconstruye ese menú desde su propio
// editor de menús en el admin (RSThemes > Menús), que borra cualquier hijo
// agregado por hooks estándar de WHMCS en cada carga de página. Hay que
// añadirlo ahí como "Custom Link" apuntando a:
//   index.php?m=facturacion_electronica
