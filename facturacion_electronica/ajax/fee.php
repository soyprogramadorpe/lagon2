<?php
/**
 * AJAX endpoint — Toggle Factura / Fee IGV
 * WHMCS está en: /public_html/panel/
 */

// ── Bootstrap WHMCS ──────────────────────────────────────────────────────────
// Desde: .../panel/modules/addons/facturacion_electronica/ajax/fee.php
// Subimos 5 niveles para llegar a .../panel/
$whmcsRoot = realpath(__DIR__ . '/../../../../../');

// Fallback: buscar init.php subiendo niveles automáticamente
if (!$whmcsRoot || !file_exists($whmcsRoot . '/init.php')) {
    $dir = __DIR__;
    for ($i = 0; $i < 8; $i++) {
        $dir = dirname($dir);
        if (file_exists($dir . '/init.php')) {
            $whmcsRoot = $dir;
            break;
        }
    }
}

if (!$whmcsRoot || !file_exists($whmcsRoot . '/init.php')) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'WHMCS root not found. Path: ' . __DIR__]);
    exit;
}

chdir($whmcsRoot);
require $whmcsRoot . '/init.php';

// ── Cabeceras ─────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ── Solo POST + XHR ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest'
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Bad request']);
    exit;
}

$action = $_POST['action'] ?? '';
$value  = (int) ($_POST['value'] ?? 0);

if ($action === 'init') {
    // Leer estado de sesión o DB
    $isChecked = !empty($_SESSION['fe_quiere_factura']);
    
    if (empty($_SESSION['fe_quiere_factura']) && !empty($_SESSION['uid'])) {
        try {
            $cf = \WHMCS\Database\Capsule::table('tblcustomfields')
                ->where('type', 'client')
                ->where('fieldname', 'LIKE', 'Deseo una factura (SUNAT PERÚ)%')
                ->first();
            if ($cf) {
                $existing = \WHMCS\Database\Capsule::table('tblcustomfieldsvalues')
                    ->where('fieldid', $cf->id)
                    ->where('relid', $_SESSION['uid'])
                    ->first();
                if ($existing && $existing->value === 'on') {
                    $isChecked = true;
                    $_SESSION['fe_quiere_factura'] = true;
                }
            }
        } catch (\Exception $e) {}
    }
    
    echo json_encode(['success' => true, 'quiere_factura' => $isChecked]);
    exit;
}

if ($action !== 'toggle') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// ── Guardar preferencia en sesión ────────────────────────────────────────────
$_SESSION['fe_quiere_factura'] = (bool) $value;

// ── Actualizar perfil si está logueado ───────────────────────────────────────
if (!empty($_SESSION['uid'])) {
    try {
        $cf = \WHMCS\Database\Capsule::table('tblcustomfields')
            ->where('type', 'client')
            ->where('fieldname', 'LIKE', 'Deseo una factura (SUNAT PERÚ)%')
            ->first();
            
        if ($cf) {
            $val = $value ? 'on' : '';
            $existing = \WHMCS\Database\Capsule::table('tblcustomfieldsvalues')
                ->where('fieldid', $cf->id)
                ->where('relid', $_SESSION['uid'])
                ->first();
                
            if ($existing) {
                \WHMCS\Database\Capsule::table('tblcustomfieldsvalues')
                    ->where('fieldid', $cf->id)
                    ->where('relid', $_SESSION['uid'])
                    ->update(['value' => $val]);
            } else {
                \WHMCS\Database\Capsule::table('tblcustomfieldsvalues')
                    ->insert(['fieldid' => $cf->id, 'relid' => $_SESSION['uid'], 'value' => $val]);
            }
        }
    } catch (\Exception $e) {
        // Ignorar
    }
}

// ── Leer config del addon ─────────────────────────────────────────────────────
function fe_ajax_get_config(string $key, string $default = ''): string
{
    try {
        $setting = \WHMCS\Database\Capsule::table('tbladdonmodules')
            ->where('module', 'facturacion_electronica')
            ->where('setting', $key)
            ->first();
        return $setting ? $setting->value : $default;
    } catch (\Exception $e) {
        return $default;
    }
}

// ── Calcular subtotal ────────────────────────────────────────────────────────
$subtotal = (float) ($_POST['base_total'] ?? 0);
$currency = $_SESSION['currency'] ?? 1;

// Fallback por si acaso base_total falla
if ($subtotal <= 0) {
    if (!empty($_SESSION['cart']['products'])) {
        foreach ($_SESSION['cart']['products'] as $p) {
            $subtotal += (float) ($p['price'] ?? 0);
        }
    }
    if (!empty($_SESSION['cart']['addons'])) {
        foreach ($_SESSION['cart']['addons'] as $a) {
            $subtotal += (float) ($a['price'] ?? 0);
        }
    }
    if (!empty($_SESSION['cart']['renewals'])) {
        foreach ($_SESSION['cart']['renewals'] as $r) {
            $subtotal += (float) ($r['price'] ?? 0);
        }
    }
    if (!empty($_SESSION['cart']['domains'])) {
        foreach ($_SESSION['cart']['domains'] as $d) {
            $subtotal += (float) ($d['price'] ?? 0);
        }
    }
    if ($subtotal <= 0 && !empty($_SESSION['orderTotal'])) {
        $subtotal = (float) $_SESSION['orderTotal'];
    }
}

$_SESSION['fe_last_subtotal'] = $subtotal;

// ── Calcular IGV ──────────────────────────────────────────────────────────────
$igvRatePct = (float) fe_ajax_get_config('igv_rate', '18');
$igvRate    = $igvRatePct / 100;
$igvAmount  = $value ? round($subtotal * $igvRate, 2) : 0;
$total      = $subtotal + $igvAmount;

// ── Símbolo de moneda ─────────────────────────────────────────────────────────
$symbol = '$';
try {
    $curr = \WHMCS\Database\Capsule::table('tblcurrencies')->where('id', (int)$currency)->first();
    if ($curr && !empty($curr->prefix)) {
        $symbol = $curr->prefix;
    }
} catch (\Exception $e) {
    // fallback
}

$fmt = function($amount) use ($symbol) {
    return $symbol . number_format($amount, 2);
};

// ── Respuesta JSON ────────────────────────────────────────────────────────────
echo json_encode([
    'success'            => true,
    'quiere_factura'     => (bool) $value,
    'subtotal'           => $subtotal,
    'subtotal_formatted' => $fmt($subtotal),
    'igv'                => $igvAmount,
    'igv_formatted'      => $fmt($igvAmount),
    'total'              => $total,
    'total_formatted'    => $fmt($total),
    'igv_rate'           => $igvRatePct,
    'fee_label'          => fe_ajax_get_config('fee_label', 'IGV (18%) — Factura Electrónica'),
]);