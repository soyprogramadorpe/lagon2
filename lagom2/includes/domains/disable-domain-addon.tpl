<div class="modal modal-lg modal-info fade" id="cancelDomainAddon" data-ajax-url="{$smarty.server.PHP_SELF}?action=domainaddons" data-cancel-domain-addon-modal>
    <div class="modal-dialog">
        <form class="modal-content" data-cancel-domain-addon-modal-form>
            <input type="hidden" name="disable" value="" data-cancel-domain-addon-modal-addon>
            <input type="hidden" name="id" value="" data-cancel-domain-addon-modal-domain>
            <input type="hidden" name="confirm" value="1">
            <input type="hidden" name="token" value="{$token}">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lm lm-close"></i></button>
                <h3 class="modal-title" data-cancel-domain-addon-modal-title></h3>
            </div>
            <div class="modal-body">
                <p>{$LANG.domainaddonscancelareyousure}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-btn-loader data-cancel-domain-addon-modal-confirm>
                    <span class="btn-text">{$LANG.domainaddonsconfirm}</span>
                    <div class="loader loader-button hidden" >
                        {include file="$template/includes/common/loader.tpl" classes="spinner-sm spinner-light"}
                    </div>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">{$LANG.cancel}</button>
            </div>
        </form>
    </div>
</div>
{literal}
    <script>
        $(document).ready(function(){
            $('[data-cancel-domain-addon ]').on('click', function(){
                let modal = $('[data-cancel-domain-addon-modal]'),                    
                    modalAddon = modal.find('[data-cancel-domain-addon-modal-addon]'),
                    modalDomain = modal.find('[data-cancel-domain-addon-modal-domain]'),
                    modalTitle = modal.find('[data-cancel-domain-addon-modal-title]'),
                    button = $(this),
                    buttonTitle = button[0].attributes['data-title'].value,
                    buttomDomain = button[0].attributes['data-domain-id'].value,
                    buttonAddon = button[0].attributes['data-addon'].value,
                    alertSuccess = $('.alert-domain-addon-success'),
                    alertDanger = $('.alert-domain-addon-error');
                alertSuccess.addClass('hidden');
                alertDanger.addClass('hidden');
                modalTitle.text(buttonTitle);
                modalDomain.val(buttomDomain);
                modalAddon.val(buttonAddon);

                modal.modal('show');
            });
            $('[data-cancel-domain-addon-modal-confirm]').on('click', function(){
                 let modal = $('[data-cancel-domain-addon-modal]'),
                     modalForm = modal.find('[data-cancel-domain-addon-modal-form]'),
                     ajaxUrl = modal[0].attributes['data-ajax-url'].value,
                     alertSuccess = $('.alert-domain-addon-success'),
                     alertDanger = $('.alert-domain-addon-error'),
                     modalAddon = modal.find('[data-cancel-domain-addon-modal-addon]'),
                     modalAddonValue = modalAddon.val(),
                     button = $(this);

                WHMCS.http.jqClient.post(
                    ajaxUrl,
                    modalForm.serialize(),
                    function(data) {
                        if (data.includes("{/literal}{$_LANG['domainaddonscancelsuccess']}{literal}")){
                           alertSuccess.removeClass('hidden');
                           $('[data-domain-addon-actions="'+modalAddonValue+'"]').addClass('hidden');
                           $('[data-domain-addon-footer="'+modalAddonValue+'"]').removeClass('hidden');
                        }
                        else{
                            alertDanger.removeClass('hidden');
                        }
                        let removeLoading = button.find('.invisible');
                        removeLoading.removeClass('invisible');
                        modal.modal('hide');
                    }
                ); 
            });
        });
    </script>
{/literal}