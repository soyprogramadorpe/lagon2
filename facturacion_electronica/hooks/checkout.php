<?php
/**
 * Hooks de Checkout — Facturación Electrónica
 *
 * Inyecta el toggle de factura en el checkout Lagon y aplica el fee IGV
 * via AJAX sin recargar la página completa.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

// =============================================================================
// HELPER: obtener config del addon
// =============================================================================

function fe_get_config(string $key, string $default = ''): string
{
    $result = select_query(
        'tbladdonmodules',
        'value',
        ['module' => 'facturacion_electronica', 'setting' => $key]
    );
    $row = mysql_fetch_assoc($result);
    return $row['value'] ?? $default;
}

function fe_get_igv_rate(): float
{
    $rate = (float) fe_get_config('igv_rate', '18');
    return $rate / 100;
}

/**
 * Lee el valor de un custom field de cliente (ej. 'RUC', 'DNI') por nombre.
 */
function fe_get_client_customfield_value(string $fieldName, int $clientId): string
{
    $cf = \WHMCS\Database\Capsule::table('tblcustomfields')
        ->where('type', 'client')
        ->where('fieldname', $fieldName)
        ->first();

    if (!$cf) {
        return '';
    }

    $cfv = \WHMCS\Database\Capsule::table('tblcustomfieldsvalues')
        ->where('fieldid', $cf->id)
        ->where('relid', $clientId)
        ->first();

    return trim($cfv->value ?? '');
}

// El toggle de factura se renderiza directamente en ordersummary-checkout.tpl
// de la plantilla (no aquí) para que aparezca en el Sumario de Pedido.

// =============================================================================
// HOOK: Inyectar IGV directamente en la factura (Aplica a nuevas y renovaciones)
// =============================================================================

add_hook('ShoppingCartValidateCheckout', 1, function (array $vars): void {
    if (isset($_POST['fe_quiere_factura'])) {
        $_SESSION['fe_quiere_factura'] = (bool) $_POST['fe_quiere_factura'];
    }
});

add_hook('InvoiceCreated', 1, function (array $vars): void {
    $invoiceId = $vars['invoiceid'];

    // Obtener la factura
    $invoice = \WHMCS\Database\Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
    if (!$invoice) return;

    // Verificar si el cliente quiere factura electrónica
    $wantsFactura = false;

    // Primero revisar la sesión (para compras en vivo)
    if (isset($_SESSION['fe_quiere_factura'])) {
        $wantsFactura = (bool) $_SESSION['fe_quiere_factura'];
    } else {
        // Si no hay sesión (ej. Cron Job de renovaciones), revisar el Custom Field del cliente
        $cf = \WHMCS\Database\Capsule::table('tblcustomfields')
            ->where('type', 'client')
            ->where('fieldname', 'LIKE', 'Deseo una factura%')
            ->first();

        if ($cf) {
            $cfv = \WHMCS\Database\Capsule::table('tblcustomfieldsvalues')
                ->where('fieldid', $cf->id)
                ->where('relid', $invoice->userid)
                ->first();
            if ($cfv && $cfv->value === 'on') {
                $wantsFactura = true;
            }
        }
    }

    if ($wantsFactura) {
        $igvRate = fe_get_igv_rate();
        $feeLabel = fe_get_config('fee_label', 'IGV (18%) — Factura Electrónica');

        // Calcular el IGV basado en el subtotal de la factura
        $igvAmount = round($invoice->subtotal * $igvRate, 2);

        if ($igvAmount > 0) {
            // Insertar el IGV como una línea de cobro en la factura
            \WHMCS\Database\Capsule::table('tblinvoiceitems')->insert([
                'invoiceid' => $invoiceId,
                'userid' => $invoice->userid,
                'type' => 'Fee',
                'relid' => 0,
                'description' => $feeLabel,
                'amount' => $igvAmount,
                'taxed' => 0,
                'duedate' => $invoice->duedate,
                'paymentmethod' => $invoice->paymentmethod,
            ]);

            // Actualizar el total de la factura
            \WHMCS\Database\Capsule::table('tblinvoices')->where('id', $invoiceId)->update([
                'total' => $invoice->total + $igvAmount
            ]);
        }
    }
});

// =============================================================================
// HOOK: Limpiar la sesión después de que se crea el pedido
// =============================================================================

add_hook('AfterShoppingCartCheckout', 1, function (array $vars): void {
    if (isset($_SESSION['fe_quiere_factura'])) {
        // Guardar en custom field de la order para referencia futura
        $orderId  = $vars['OrderID'] ?? 0;
        $clientId = $vars['ClientID'] ?? 0;

        if ($orderId && $_SESSION['fe_quiere_factura']) {
            // Guardamos en tblorderscustomfields si existe, o en meta tabla propia
            // Por ahora registramos en el log para Fase 2
            logActivity(
                "[FacturacionElectronica] Order #{$orderId} (Client #{$clientId}) — " .
                "cliente solicitó factura electrónica."
            );
        }

        unset($_SESSION['fe_quiere_factura']);
    }
});
