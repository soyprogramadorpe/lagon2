<?php
/**
 * Archivo principal de Hooks para el módulo Facturación Electrónica.
 * WHMCS busca automáticamente este archivo en la raíz del módulo para registrar hooks globales.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// Autoloader de las clases propias (Providers, Api, etc.). Tiene que estar
// aquí y no solo en facturacion_electronica.php, porque este archivo es el
// que WHMCS carga en cada request (incluido al pagar una factura), mientras
// que facturacion_electronica.php solo se carga para la página de config.
spl_autoload_register(function ($class) {
    $prefix = 'WHMCS\\FacturacionElectronica\\';
    if (strpos($class, $prefix) !== 0) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/lib/' . $relative . '.php';
    if (file_exists($file)) require_once $file;
});

// Requerir el archivo de hooks que contiene la lógica de checkout y la inyección de IGV
require_once __DIR__ . '/hooks/checkout.php';

// Requerir el archivo de hooks para la emisión automática de facturas en InvoicePaid
require_once __DIR__ . '/hooks/invoice.php';
