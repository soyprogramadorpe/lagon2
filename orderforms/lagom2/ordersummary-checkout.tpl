<div class="panel-body">
    <div class="summary-content content">
        <ul class="summary-list">
            <li class="list-item" data-subtotal>
                <span class="item-name">{$LANG.ordersubtotal}</span>
                <span class="item-value">{$subtotal}</span>
            </li>
        </ul>
        {if $promotioncode || $taxrate || $taxrate2}
        <ul class="summary-list faded">
            {if $taxrate}
                <li class="list-item">
                    <span class="item-name">{$taxname} @ {$taxrate}%</span>
                    <span class="item-value" id="taxTotal1">{$taxtotal}</span>
                </li>
            {/if}
            {if $taxrate2}
                <li class="list-item">
                    <span class="item-name">{$taxname2} @ {$taxrate2}%</span>
                    <span class="item-value" id="taxTotal2">{$taxtotal2}</span>
                </li>
            {/if}
            {if $promotioncode}
                <li class="list-item light">
                    <span class="item-name">{$promotiondescription}</span>
                    <span class="item-value" id="discount">{$discount}</span>
                </li>
            {/if}
        </ul>
        {/if}
        <ul class="summary-list" id="recurring">
            <li class="list-item faded">{$LANG.orderForm.totals}</li>
            <li class="list-item" id="recurringMonthly" {if !$totalrecurringmonthly}style="display:none;"{/if}>
                <span class="item-name">{$LANG.orderpaymenttermmonthly}</span>
                <span class="item-value">{$totalrecurringmonthly}</span>
            </li>                
            <li class="list-item" id="recurringQuarterly" {if !$totalrecurringquarterly}style="display:none;"{/if}>
                <span class="item-name">{$LANG.orderpaymenttermquarterly}</span>
                <span class="item-value">{$totalrecurringquarterly}</span>
            </li>
            <li class="list-item" id="recurringSemiAnnually" {if !$totalrecurringsemiannually}style="display:none;"{/if}>
                <span class="item-name">{$LANG.orderpaymenttermsemiannually}</span>
                <span class="item-value">{$totalrecurringsemiannually}</span>
            </li>
            <li class="list-item" id="recurringAnnually" {if !$totalrecurringannually}style="display:none;"{/if}>
                <span class="item-name">{$LANG.orderpaymenttermannually}</span>
                <span class="item-value">{$totalrecurringannually}</span>
            </li>
            <li class="list-item" id="recurringBiennially" {if !$totalrecurringbiennially}style="display:none;"{/if}>
                <span class="item-name">{$LANG.orderpaymenttermbiennially}</span>
                <span class="item-value">{$totalrecurringbiennially}</span>
            </li>
            <li class="list-item" id="recurringTriennially" {if !$totalrecurringtriennially}style="display:none;"{/if}>
                <span class="item-name">{$LANG.orderpaymenttermtriennially}</span>
                <span class="item-value">{$totalrecurringtriennially}</span>
            </li>
        </ul>

        {* ═══════════════════════════════════════════════════════
           FACTURACIÓN ELECTRÓNICA — Toggle IGV
           ═══════════════════════════════════════════════════════ *}
        {assign var="fe_checked" value=false}
        {if $clientsdetails && $clientsdetails.customfields}
            {foreach from=$clientsdetails.customfields item=cf}
                {if $cf.name|strpos:'Deseo una factura (SUNAT PERÚ)' !== false && $cf.value eq 'on'}
                    {assign var="fe_checked" value=true}
                {/if}
            {/foreach}
        {/if}
        
        <div id="fe-factura-block" style="
            border-top: 1px solid #e9ecef;
            margin-top: 12px;
            padding-top: 14px;
        ">
            <label style="
                display: flex;
                align-items: flex-start;
                gap: 10px;
                cursor: pointer;
                margin: 0;
                font-size: 13px;
                color: #555;
                font-weight: normal;
                line-height: 1.4;
            ">
                <input
                    type="checkbox"
                    id="fe_quiere_factura"
                    value="1"
                    {if $fe_checked}checked{/if}
                    style="
                        width: 16px;
                        height: 16px;
                        margin-top: 2px;
                        flex-shrink: 0;
                        cursor: pointer;
                    "
                >
                <span>¿Deseas emitir una <strong>factura electrónica</strong>? <em style="color:#999;">(agrega 18% IGV)</em></span>
            </label>

            {* Fila IGV — oculta hasta que el toggle se active *}
            <ul class="summary-list faded" id="fe-igv-row" style="display:none; margin-top:10px;">
                <li class="list-item" style="color: #e67e22;">
                    <span class="item-name">IGV (18%) — Factura Electrónica</span>
                    <span class="item-value" id="fe-igv-amount">$0.00</span>
                </li>
            </ul>
        </div>
        {* ═══════════════════════════════════════════════════════ *}

    </div>
</div>

{* ── Overlay loader ── *}
<div id="fe-overlay" style="
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(255,255,255,0.85);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 12px;
">
    <div style="
        width: 40px; height: 40px;
        border: 4px solid #dee2e6;
        border-top-color: #c0392b;
        border-radius: 50%;
        animation: fe-spin 0.7s linear infinite;
    "></div>
    <span style="font-size:13px; color:#666;">Actualizando totales...</span>
</div>
<style>@keyframes fe-spin { to { transform: rotate(360deg); } }</style>

<div class="panel-footer">                        
    <div class="price price-left-h" data-total>
        <span class="price-total">{$LANG.ordertotalduetoday}</span> 
        <div class="price-amount amt" id="totalDueToday">{$total}</div>
    </div>    
    <div class="summary-actions">
    {if $RSThemes['pages'][$templatefile] && $RSThemes['pages'][$templatefile]['config']['tosLocation'] === "Above CTA button" }
        {if $accepttos}
            <div class="order-checkbox" data-form-input="#accepttos">
                <div class="checkbox m-t-0 m-b-1x" id="tos-checkbox">
                    <label>
                        <input class="icheck-control" type="checkbox" data-tos-checkbox />
                        <span>{$LANG.ordertosagreement} <a href="{$tosurl}" target="_blank">{$LANG.ordertos}</a></span>
                    </label>
                </div>
                <div class="alert alert-lagom alert-xs alert-danger m-b-2x hidden">
                    <div class="alert-body">
                        {$LANG.ordererroraccepttos}
                    </div>
                </div> 
            </div>
            {if file_exists("templates/orderforms/$carttpl/includes/viewcart/custom-tos.tpl")}
                {include file="templates/orderforms/$carttpl/includes/viewcart/custom-tos.tpl"}
            {/if}
        {/if}
    {/if}
        <button type="button" class="btn btn-lg btn-primary{if $summaryStyle == "primary"}-faded{/if} btn-checkout{if $cartitems == 0} disabled{/if}" {if $cartitems == 0} disabled{/if} data-btn-loader id="checkout">
            <span><i class="ls ls-share"></i>{$LANG.orderForm.checkout}</span>
            <div class="loader loader-button hidden">
                {include file="$template/includes/common/loader.tpl" classes="spinner-sm"}  
            </div>
        </button>
    </div>
</div>

{* ══════════════════════════════════════════════════════════════
   SCRIPT — Toggle AJAX Facturación Electrónica
   ══════════════════════════════════════════════════════════════ *}
<script>
$(document).ready(function() {
    var $checkbox = $('#fe_quiere_factura');
    var $igvRow = $('#fe-igv-row');
    var $igvAmount = $('#fe-igv-amount');
    var $totalEl = $('#totalDueToday');
    var $overlay = $('#fe-overlay');
    var $realCheckbox = $('.fe-real-factura-checkbox');
    var $rucInput = $('.fe-ruc-input');
    var $dniInput = $('.fe-dni-input');

    if (!$checkbox.length || !$totalEl.length) return;

    var rawTotal = "{$rawtotal}";
    if (!rawTotal || rawTotal == "0") {
        var match = $totalEl.text().match(/[\d\.]+/g);
        if (match) {
            rawTotal = match.join('');
        }
    }

    // Limitar ingresos a solo números
    if ($dniInput.length) {
        $dniInput.attr('maxlength', '8');
        $dniInput.on('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); });
    }
    if ($rucInput.length) {
        $rucInput.attr('maxlength', '11');
        $rucInput.on('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); });
    }

    // Sincronizar estado inicial desde el campo real si existe (nuevo registro)
    if ($realCheckbox.length && $realCheckbox.prop('checked')) {
        $checkbox.prop('checked', true);
        if ($.fn.iCheck) $checkbox.iCheck('update');
    }

    var ajaxUrl = '/modules/addons/facturacion_electronica/ajax/fee.php';

    // Función principal para manejar el toggle
    function procesarToggle() {
        var isChecked = $checkbox.prop('checked');
        var quiere = isChecked ? 1 : 0;

        // Sincronizar hacia el campo real
        if ($realCheckbox.length) {
            $realCheckbox.prop('checked', isChecked);
            if ($.fn.iCheck) $realCheckbox.iCheck('update');
        }

        // Ya no establecemos .prop('required', true) aquí porque el navegador 
        // arroja el error "invalid form control is not focusable" si los campos
        // están ocultos (cuando el cliente elige su cuenta existente).
        // La validación estricta la haremos al momento de hacer clic en "Comprar".

        $overlay.css('display', 'flex');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'toggle',
                value: quiere,
                base_total: rawTotal
            },
            dataType: 'json',
            success: function(resp) {
                $overlay.hide();
                if (resp.success) {
                    if (resp.igv > 0) {
                        $igvAmount.text(resp.igv_formatted);
                        $igvRow.show();
                    } else {
                        $igvRow.hide();
                    }
                    $totalEl.text(resp.total_formatted);
                } else {
                    console.warn('[FE] Error:', resp.message);
                    $checkbox.prop('checked', !isChecked);
                    if ($.fn.iCheck) $checkbox.iCheck('update');
                }
            },
            error: function() {
                $overlay.hide();
                console.error('[FE] Network error');
                $checkbox.prop('checked', !isChecked);
                if ($.fn.iCheck) $checkbox.iCheck('update');
            }
        });
    }

    // Escuchar cambios (iCheck usa ifChanged, nativo usa change)
    $checkbox.on('change ifChanged', procesarToggle);

    // Si viene marcado por defecto desde el perfil del cliente, disparar cálculo inmediatamente
    if ($checkbox.prop('checked')) {
        procesarToggle();
    }

    // Validación al clickear comprar
    $('#checkout').on('click', function(e) {
        if ($checkbox.prop('checked')) {
            if ($dniInput.length && $dniInput.is(':visible') && $dniInput.val().length !== 8) {
                e.preventDefault();
                e.stopPropagation();
                alert('El DNI debe tener exactamente 8 dígitos.');
                $dniInput.focus();
                return false;
            }
            if ($rucInput.length && $rucInput.is(':visible') && $rucInput.val().length !== 11) {
                e.preventDefault();
                e.stopPropagation();
                alert('El RUC debe tener exactamente 11 dígitos.');
                $rucInput.focus();
                return false;
            }
        }
    });

    // Consultar estado inicial seguro vía AJAX (sin romper Smarty)
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: { action: 'init' },
        dataType: 'json',
        success: function(resp) {
            if (resp.success && resp.quiere_factura) {
                if (!$checkbox.prop('checked')) {
                    $checkbox.prop('checked', true);
                    if ($.fn.iCheck) $checkbox.iCheck('update');
                    procesarToggle(); // Calcular 18% automáticamente
                }
            }
        }
    });
});
</script>
