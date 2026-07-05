{if $comprobantes|@count}
    <div class="table-container clearfix">
        <table class="table table-list">
            <thead>
                <tr>
                    <th>Comprobante</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$comprobantes item=c}
                <tr>
                    <td>{$c.comprobante}</td>
                    <td>{$c.tipo}</td>
                    <td>{$c.fecha}</td>
                    <td>{$c.total}</td>
                    <td class="cell-action cell-action--last">
                        {if $c.pdf_url}<a href="{$c.pdf_url}" target="_blank" class="btn btn-default btn-sm btn-manage">PDF</a>{/if}
                        {if $c.xml_url}<a href="{$c.xml_url}" target="_blank" class="btn btn-default btn-sm btn-manage">XML</a>{/if}
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
{else}
    <div class="message message-no-data">
        <h6 class="message-title">Aún no tienes comprobantes electrónicos emitidos.</h6>
    </div>
{/if}
