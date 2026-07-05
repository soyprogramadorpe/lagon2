<?php
/**
 * Módulo de Facturación Electrónica para WHMCS
 *
 * Fase 1: Fee 18% IGV en checkout con toggle AJAX
 * Fase 2: Integración con proveedor de facturación electrónica vía API
 *
 * @package    WHMCS
 * @author     Cultura Interactiva
 * @version    1.0.0
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

// ── Constantes del módulo ─────────────────────────────────────────────────────
define('FE_MODULE_NAME',    'facturacion_electronica');
define('FE_MODULE_VERSION', '1.0.0');
define('FE_IGV_RATE',       0.18);

// ── Autoloader de clases propias ──────────────────────────────────────────────
spl_autoload_register(function ($class) {
    $prefix = 'WHMCS\\FacturacionElectronica\\';
    if (strpos($class, $prefix) !== 0) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/lib/' . $relative . '.php';
    if (file_exists($file)) require_once $file;
});

// ── Hooks ─────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/hooks/checkout.php';

// =============================================================================
// FUNCIONES REQUERIDAS POR WHMCS
// =============================================================================

function facturacion_electronica_MetaData(): array
{
    return [
        'DisplayName' => 'Facturación Electrónica',
        'APIVersion'  => '1.1',
        'Author'      => 'Cultura Interactiva',
        'Description' => 'Genera facturas electrónicas con IGV (18%) en el checkout.',
        'Language'    => 'spanish',
        'Version'     => FE_MODULE_VERSION,
    ];
}

function facturacion_electronica_config(): array
{
    return [
        'name'        => 'Facturación Electrónica',
        'description' => 'Habilita el campo de factura en el checkout y gestiona la emisión electrónica.',
        'version'     => FE_MODULE_VERSION,
        'author'      => 'Cultura Interactiva',
        'fields'      => [

            // ── General ───────────────────────────────────────────────────────
            'igv_rate' => [
                'FriendlyName' => 'Tasa IGV (%)',
                'Type'         => 'text',
                'Size'         => '5',
                'Default'      => '18',
                'Description'  => 'Porcentaje de IGV a aplicar (sin el símbolo %). Perú: 18',
            ],
            'fee_label' => [
                'FriendlyName' => 'Etiqueta del Fee',
                'Type'         => 'text',
                'Size'         => '40',
                'Default'      => 'IGV (18%) — Factura Electrónica',
                'Description'  => 'Nombre que aparece en el resumen del carrito.',
            ],
            'checkout_label' => [
                'FriendlyName' => 'Texto del Toggle',
                'Type'         => 'text',
                'Size'         => '60',
                'Default'      => '¿Deseas emitir una factura electrónica? (agrega 18% IGV)',
                'Description'  => 'Pregunta que ve el cliente en el checkout.',
            ],

            // ── Proveedor (Fase 2) ────────────────────────────────────────────
            'provider' => [
                'FriendlyName' => 'Proveedor API',
                'Type'         => 'dropdown',
                'Options'      => 'none,redeperu,nubefact,efact,sunat_direct',
                'Default'      => 'none',
                'Description'  => 'Proveedor para emisión electrónica (Fase 2).',
            ],
            'api_key' => [
                'FriendlyName' => 'API Key / Token',
                'Type'         => 'password',
                'Size'         => '60',
                'Default'      => '',
                'Description'  => 'Token del proveedor seleccionado (Fase 2).',
            ],
            'api_url' => [
                'FriendlyName' => 'URL Base del API',
                'Type'         => 'text',
                'Size'         => '80',
                'Default'      => '',
                'Description'  => 'Endpoint base del proveedor (Fase 2).',
            ],
            'ruc_emisor' => [
                'FriendlyName' => 'RUC Emisor',
                'Type'         => 'text',
                'Size'         => '20',
                'Default'      => '',
                'Description'  => 'RUC de tu empresa emisora (Fase 2).',
            ],
            'razon_social_emisor' => [
                'FriendlyName' => 'Razón Social Emisor',
                'Type'         => 'text',
                'Size'         => '60',
                'Default'      => '',
                'Description'  => 'Razón social de tu empresa (Fase 2).',
            ],
            'sandbox_mode' => [
                'FriendlyName' => 'Modo Sandbox',
                'Type'         => 'yesno',
                'Default'      => 'yes',
                'Description'  => 'Activa el entorno de pruebas del proveedor (Fase 2).',
            ],
            'serie_factura' => [
                'FriendlyName' => 'Serie Factura',
                'Type'         => 'text',
                'Size'         => '10',
                'Default'      => 'F001',
                'Description'  => 'Serie para Facturas (ej. F001).',
            ],
            'serie_boleta' => [
                'FriendlyName' => 'Serie Boleta',
                'Type'         => 'text',
                'Size'         => '10',
                'Default'      => 'B001',
                'Description'  => 'Serie para Boletas (ej. B001).',
            ],
            'serie_nc' => [
                'FriendlyName' => 'Serie Nota Crédito',
                'Type'         => 'text',
                'Size'         => '10',
                'Default'      => 'FC01',
                'Description'  => 'Serie para Notas de Crédito.',
            ],
            'serie_nd' => [
                'FriendlyName' => 'Serie Nota Débito',
                'Type'         => 'text',
                'Size'         => '10',
                'Default'      => 'FD01',
                'Description'  => 'Serie para Notas de Débito.',
            ],
        ],
    ];
}

function facturacion_electronica_activate(): array
{
    try {
        $queries = [
            "CREATE TABLE IF NOT EXISTS `mod_fe_facturas` (
                `id`                INT(11)       NOT NULL AUTO_INCREMENT,
                `whmcs_invoice_id`  INT(11)       NOT NULL,
                `client_id`         INT(11)       NOT NULL,
                `serie`             VARCHAR(10)   NOT NULL DEFAULT '',
                `correlativo`       VARCHAR(20)   NOT NULL DEFAULT '',
                `tipo_doc`          ENUM('01','03') NOT NULL DEFAULT '01',
                `ruc_cliente`       VARCHAR(15)   NOT NULL DEFAULT '',
                `razon_social`      VARCHAR(200)  NOT NULL DEFAULT '',
                `subtotal`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `igv`               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `total`             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `estado`            ENUM('pendiente','emitida','error','anulada') NOT NULL DEFAULT 'pendiente',
                `provider_response` TEXT,
                `pdf_url`           VARCHAR(500)  NOT NULL DEFAULT '',
                `xml_url`           VARCHAR(500)  NOT NULL DEFAULT '',
                `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_invoice` (`whmcs_invoice_id`),
                KEY `idx_client`  (`client_id`),
                KEY `idx_estado`  (`estado`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `mod_fe_clientes_fiscales` (
                `id`           INT(11)      NOT NULL AUTO_INCREMENT,
                `client_id`    INT(11)      NOT NULL,
                `tipo_doc_id`  ENUM('1','6') NOT NULL DEFAULT '6',
                `num_doc`      VARCHAR(15)  NOT NULL DEFAULT '',
                `razon_social` VARCHAR(200) NOT NULL DEFAULT '',
                `direccion`    VARCHAR(300) NOT NULL DEFAULT '',
                `ubigeo`       VARCHAR(10)  NOT NULL DEFAULT '',
                `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_client` (`client_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];

        foreach ($queries as $sql) {
            full_query($sql);
        }

        return ['status' => 'success', 'description' => 'Módulo activado. Tablas creadas correctamente.'];
    } catch (\Exception $e) {
        return ['status' => 'error', 'description' => 'Error al activar: ' . $e->getMessage()];
    }
}

function facturacion_electronica_deactivate(): array
{
    return [
        'status'      => 'success',
        'description' => 'Módulo desactivado. Los datos se conservan en la base de datos.',
    ];
}

function facturacion_electronica_upgrade(array $vars): void
{
    // Migraciones de DB por versión irán aquí
    // $currentVersion = $vars['version'];
}

function facturacion_electronica_output(array $vars): void
{
    $modulelink = $vars['modulelink'];

    $modulelink = $vars['modulelink'];
    $provider = $vars['provider'] ?? 'none';
    $apiUrl = $vars['api_url'] ?? '';

    $totalRes  = select_query('mod_fe_facturas', 'COUNT(*) as cnt', []);
    $totalRow  = mysql_fetch_assoc($totalRes);
    $total     = $totalRow['cnt'] ?? 0;

    $emitRes   = select_query('mod_fe_facturas', 'COUNT(*) as cnt', ['estado' => 'emitida']);
    $emitRow   = mysql_fetch_assoc($emitRes);
    $emitidas  = $emitRow['cnt'] ?? 0;

    $errorRes  = select_query('mod_fe_facturas', 'COUNT(*) as cnt', ['estado' => 'error']);
    $errorRow  = mysql_fetch_assoc($errorRes);
    $errores   = $errorRow['cnt'] ?? 0;

    // Procesar Desconexión
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disconnect_redeperu'])) {
        update_query('tbladdonmodules', ['value' => ''], ['module' => 'facturacion_electronica', 'setting' => 'api_key']);
        update_query('tbladdonmodules', ['value' => ''], ['module' => 'facturacion_electronica', 'setting' => 'ruc_emisor']);
        update_query('tbladdonmodules', ['value' => ''], ['module' => 'facturacion_electronica', 'setting' => 'razon_social_emisor']);
        $vars['api_key'] = '';
        $vars['ruc_emisor'] = '';
        $vars['razon_social_emisor'] = '';
        $msgConexion = '<div class="alert alert-success">Desconectado exitosamente.</div>';
    }

    // Procesar Guardado de Series
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_series'])) {
        $sf = $_POST['serie_factura'] ?? 'F001';
        $sb = $_POST['serie_boleta'] ?? 'B001';
        $snc = $_POST['serie_nc'] ?? 'FC01';
        $snd = $_POST['serie_nd'] ?? 'FD01';
        update_query('tbladdonmodules', ['value' => $sf], ['module' => 'facturacion_electronica', 'setting' => 'serie_factura']);
        update_query('tbladdonmodules', ['value' => $sb], ['module' => 'facturacion_electronica', 'setting' => 'serie_boleta']);
        update_query('tbladdonmodules', ['value' => $snc], ['module' => 'facturacion_electronica', 'setting' => 'serie_nc']);
        update_query('tbladdonmodules', ['value' => $snd], ['module' => 'facturacion_electronica', 'setting' => 'serie_nd']);
        $vars['serie_factura'] = $sf;
        $vars['serie_boleta'] = $sb;
        $vars['serie_nc'] = $snc;
        $vars['serie_nd'] = $snd;
        $msgConexion = '<div class="alert alert-success">Series guardadas correctamente.</div>';
    }

    // Procesar conexión si se envió el formulario (sólo para Redeperu)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connect_redeperu']) && $provider === 'redeperu') {
        $email = $_POST['email'] ?? '';
        $pass = $_POST['pass'] ?? '';
        $ruc = $_POST['ruc'] ?? '';
        
        $base = empty($apiUrl) ? 'https://invoxe.redeperu.com' : rtrim($apiUrl, '/');
        
        if (empty($ruc)) {
            // Paso 1: Listar empresas
            $ch = curl_init($base . '/api/auth/connect/empresas');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => $email, 'pass' => $pass]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            $res = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($res, true);
            if (!empty($data['success'])) {
                $empresas = $data['empresas'];
                $msgConexion = '<div class="alert alert-success">Autenticado. Selecciona la empresa:</div>
                <form method="post" action="' . $modulelink . '">
                    <input type="hidden" name="connect_redeperu" value="1">
                    <input type="hidden" name="email" value="'.htmlspecialchars($email).'">
                    <input type="hidden" name="pass" value="'.htmlspecialchars($pass).'">
                    <select name="ruc" class="form-control" style="width:300px; display:inline-block; margin-right:10px;">';
                foreach ($empresas as $emp) {
                    $msgConexion .= '<option value="'.$emp['ruc'].'">'.$emp['razonSocial'].' ('.$emp['ruc'].')</option>';
                }
                $msgConexion .= '</select><button type="submit" class="btn btn-primary">Conectar Empresa</button></form>';
            } else {
                $msgConexion = '<div class="alert alert-danger">Error: '.($data['mensaje'] ?? 'Credenciales inválidas').'</div>';
            }
        } else {
            // Paso 2: Obtener token
            $ch = curl_init($base . '/api/auth/connect/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => $email, 'pass' => $pass, 'ruc' => $ruc]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            $res = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($res, true);
            if (!empty($data['success'])) {
                $token = $data['apiToken'];
                // Guardar en tbladdonmodules
                update_query('tbladdonmodules', ['value' => $token], ['module' => 'facturacion_electronica', 'setting' => 'api_key']);
                update_query('tbladdonmodules', ['value' => $ruc], ['module' => 'facturacion_electronica', 'setting' => 'ruc_emisor']);
                update_query('tbladdonmodules', ['value' => $data['razonSocial']], ['module' => 'facturacion_electronica', 'setting' => 'razon_social_emisor']);
                $vars['api_key'] = $token;
                $vars['ruc_emisor'] = $ruc;
                $vars['razon_social_emisor'] = $data['razonSocial'];
                
                $msgConexion = '<div class="alert alert-success">Conectado exitosamente. Token guardado.</div>';
            } else {
                $msgConexion = '<div class="alert alert-danger">Error al obtener token: '.($data['mensaje'] ?? 'Fallo').'</div>';
            }
        }
    }

    echo '<div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Facturación Electrónica — Panel de Control</h3>
        </div>
        <div class="panel-body">
            <div class="row text-center" style="margin-bottom:20px">
                <div class="col-sm-4">
                    <div class="panel panel-info">
                        <div class="panel-body">
                            <h2>' . $total . '</h2><p>Total Facturas</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="panel panel-success">
                        <div class="panel-body">
                            <h2>' . $emitidas . '</h2><p>Emitidas OK</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="panel panel-danger">
                        <div class="panel-body">
                            <h2>' . $errores . '</h2><p>Con Error</p>
                        </div>
                    </div>
                </div>
            </div>
            <p><strong>Versión:</strong> ' . FE_MODULE_VERSION . '</p>
            <p><strong>Proveedor:</strong> ' . htmlspecialchars($provider) . '</p>';

    if ($provider === 'redeperu') {
        echo '<h4>Conexión con invoxe.redeperu.com</h4>';
        if ($msgConexion) {
            echo $msgConexion;
        }
        
        if (empty($vars['api_key'])) {
            echo '<form method="post" action="' . $modulelink . '" class="form-inline well">
                <input type="hidden" name="connect_redeperu" value="1">
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" class="form-control" required placeholder="tu@email.com">
                </div>
                <div class="form-group" style="margin-left:15px;">
                    <label>Contraseña:</label>
                    <input type="password" name="pass" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success" style="margin-left:15px;">Iniciar Conexión</button>
            </form>';
        } else {
            // Mostrar info de conexión y botón para desconectar
            echo '<div class="well">
                <p><strong>Empresa conectada:</strong> ' . htmlspecialchars($vars['razon_social_emisor']) . ' (RUC: ' . htmlspecialchars($vars['ruc_emisor']) . ')</p>
                <form method="post" action="' . $modulelink . '" style="display:inline-block; margin-right: 15px;">
                    <input type="hidden" name="disconnect_redeperu" value="1">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'¿Estás seguro de desconectar esta empresa?\')">Desconectar</button>
                </form>
            </div>';

            // Obtener series
            $base = empty($apiUrl) ? 'https://invoxe.redeperu.com' : rtrim($apiUrl, '/');
            $ch = curl_init($base . '/api/companies/' . $vars['ruc_emisor'] . '/series');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $vars['api_key'],
                'Content-Type: application/json'
            ]);
            $res = curl_exec($ch);
            curl_close($ch);

            $seriesData = json_decode($res, true);
            $facturasSeries = [];
            $boletasSeries = [];
            $ncSeries = [];
            $ndSeries = [];
            
            if (isset($seriesData['series'])) {
                foreach ($seriesData['series'] as $serie) {
                    if ($serie['tipoDocumento'] === '01') {
                        $facturasSeries[] = $serie['serie'];
                    } elseif ($serie['tipoDocumento'] === '03') {
                        $boletasSeries[] = $serie['serie'];
                    } elseif ($serie['tipoDocumento'] === '07') {
                        $ncSeries[] = $serie['serie'];
                    } elseif ($serie['tipoDocumento'] === '08') {
                        $ndSeries[] = $serie['serie'];
                    }
                }
            }
            
            $currentFactura = $vars['serie_factura'] ?? 'F001';
            $currentBoleta = $vars['serie_boleta'] ?? 'B001';
            $currentNc = $vars['serie_nc'] ?? 'FC01';
            $currentNd = $vars['serie_nd'] ?? 'FD01';
            
            echo '<h4>Configuración de Series</h4>
            <div class="well">
                <form method="post" action="' . $modulelink . '">
                    <input type="hidden" name="save_series" value="1">
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Serie Factura:</label>
                                <select name="serie_factura" class="form-control">';
            if (!empty($facturasSeries)) {
                foreach ($facturasSeries as $s) {
                    $sel = ($s === $currentFactura) ? 'selected' : '';
                    echo '<option value="' . $s . '" ' . $sel . '>' . $s . '</option>';
                }
            } else {
                echo '<option value="' . $currentFactura . '">' . $currentFactura . '</option>';
            }
            echo '              </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Serie Boleta:</label>
                                <select name="serie_boleta" class="form-control">';
            if (!empty($boletasSeries)) {
                foreach ($boletasSeries as $s) {
                    $sel = ($s === $currentBoleta) ? 'selected' : '';
                    echo '<option value="' . $s . '" ' . $sel . '>' . $s . '</option>';
                }
            } else {
                echo '<option value="' . $currentBoleta . '">' . $currentBoleta . '</option>';
            }
            echo '              </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Serie Nota de Crédito:</label>
                                <select name="serie_nc" class="form-control">';
            if (!empty($ncSeries)) {
                foreach ($ncSeries as $s) {
                    $sel = ($s === $currentNc) ? 'selected' : '';
                    echo '<option value="' . $s . '" ' . $sel . '>' . $s . '</option>';
                }
            } else {
                echo '<option value="' . $currentNc . '">' . $currentNc . '</option>';
            }
            echo '              </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Serie Nota de Débito:</label>
                                <select name="serie_nd" class="form-control">';
            if (!empty($ndSeries)) {
                foreach ($ndSeries as $s) {
                    $sel = ($s === $currentNd) ? 'selected' : '';
                    echo '<option value="' . $s . '" ' . $sel . '>' . $s . '</option>';
                }
            } else {
                echo '<option value="' . $currentNd . '">' . $currentNd . '</option>';
            }
            echo '              </select>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Guardar Series</button>
                </form>
            </div>';
        }
    }

    echo '
            <p>Configura el proveedor API en
                <a href="' . $modulelink . '">Configuración del Addon</a>.
            </p>
        </div>
    </div>';
}

/**
 * Página del área de cliente — "Factura Electrónica".
 * Accesible en index.php?m=facturacion_electronica. Usa su propia plantilla
 * (templates/clientarea.tpl dentro del módulo), sin tocar el tema.
 */
function facturacion_electronica_clientarea(array $vars): array
{
    $clientId = (int) ($_SESSION['uid'] ?? 0);

    $comprobantesView = [];
    if ($clientId) {
        $comprobantes = \WHMCS\Database\Capsule::table('mod_fe_facturas')
            ->where('client_id', $clientId)
            ->where('estado', 'emitida')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($comprobantes as $c) {
            $comprobantesView[] = [
                'comprobante' => $c->serie . '-' . str_pad($c->correlativo, 8, '0', STR_PAD_LEFT),
                'tipo'        => $c->tipo_doc === '01' ? 'Factura' : 'Boleta',
                'fecha'       => date('d/m/Y', strtotime($c->created_at)),
                'total'       => 'S/' . number_format((float) $c->total, 2),
                'pdf_url'     => $c->pdf_url,
                'xml_url'     => $c->xml_url,
            ];
        }
    }

    return [
        'pagetitle'    => 'Factura Electrónica',
        'breadcrumb'   => [
            'index.php?m=facturacion_electronica' => 'Factura Electrónica',
        ],
        'templatefile' => 'clientarea',
        'requirelogin' => true,
        'vars'         => [
            'comprobantes' => $comprobantesView,
        ],
    ];
}
