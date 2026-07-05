<?php
/**
 * Webhook endpoint para Facturación Electrónica (invoxe.redeperu.com).
 * URL de ejemplo a configurar en el dashboard:
 * https://tudominio.com/whmcs/modules/addons/facturacion_electronica/webhook.php
 */

require_once __DIR__ . '/../../../init.php';

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Obtener el body crudo y las cabeceras
$rawBody = file_get_contents('php://input');
$firmaRecibida = $_SERVER['HTTP_X_REDEPERU_SIGNATURE'] ?? '';
$eventoHeader = $_SERVER['HTTP_X_REDEPERU_EVENT'] ?? '';

// Intentar obtener el secreto desde la configuración (si lo hubieran agregado)
$secreto = '';
try {
    $setting = Capsule::table('tbladdonmodules')
        ->where('module', 'facturacion_electronica')
        ->where('setting', 'webhook_secret')
        ->first();
    if ($setting) {
        $secreto = $setting->value;
    }
} catch (\Exception $e) { }

// Solo validamos si hay un secreto guardado
if (!empty($secreto)) {
    $firmaEsperada = 'sha256=' . hash_hmac('sha256', $rawBody, $secreto);
    if (!hash_equals($firmaEsperada, $firmaRecibida)) {
        logActivity('[FacturacionElectronica] Webhook rechazado por firma inválida.');
        http_response_code(401);
        exit('Firma inválida');
    }
}

$payload = json_decode($rawBody, true);
if (!$payload) {
    http_response_code(400);
    exit('Invalid JSON');
}

// Escuchamos el evento de documento actualizado
if ($payload['evento'] === 'documento.actualizado') {
    $data = $payload['data'] ?? [];
    
    $serie = $data['serie'] ?? '';
    $correlativo = $data['correlativo'] ?? '';
    
    if ($serie && $correlativo) {
        $factura = Capsule::table('mod_fe_facturas')
            ->where('serie', $serie)
            ->where('correlativo', $correlativo)
            ->first();
            
        if ($factura) {
            $update = [];
            
            if (isset($data['estado'])) {
                if ($data['estado'] === 'ACEPTADO') {
                    $update['estado'] = 'emitida';
                } elseif ($data['estado'] === 'RECHAZADO') {
                    $update['estado'] = 'error';
                }
            }
            if (isset($data['pdfUrl'])) $update['pdf_url'] = $data['pdfUrl'];
            if (isset($data['xmlUrl'])) $update['xml_url'] = $data['xmlUrl'];
            
            if (!empty($update)) {
                $update['updated_at'] = date('Y-m-d H:i:s');
                Capsule::table('mod_fe_facturas')->where('id', $factura->id)->update($update);
                logActivity("[FacturacionElectronica] Webhook actualizó estado de {$serie}-{$correlativo} a " . ($update['estado'] ?? 'actualizado'));
            }
        }
    }
}

http_response_code(200);
echo 'ok';
