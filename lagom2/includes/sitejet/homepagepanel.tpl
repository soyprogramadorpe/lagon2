<div class="d-flex flex-direction-column">
   <div style="height: 227px; width: 100%;" class="w-100 text-center">
        <img src="{routePath('clientarea-sitejet-get-preview', $sitejetServices[0]->id)}"
            onerror="this.src = whmcsBaseUrl + '/assets/img/sitejet/preview_placeholder.png';" alt="Sitejet Preview" id="sitejetPromoImage" class="img-fluid"  style="transition: opacity 0.2s ease-in-out; max-height: 227px"/>
    </div>
    <div class="form-group m-t-2x w-100">
        <label>{lang key='sitejetBuilder.chooseWebsite'}</label>
        <select id="sitejetServiceSelect" class="form-control">
            {foreach $sitejetServices as $key => $service}
                <option value="{$service->id}">{$service->domain}</option>
            {/foreach}
        </select>
    </div>
    <button class="btn btn-primary-faded btn-custom-action d-block div-service-item w-100"
            id="sitejetSsoButton"
            data-serviceid="{$sitejetServices[0]->id}"
            data-identifier="sitejet"
            data-active="true"
    >
        {lang key="sitejetBuilder.editWebsite"}
    </button>
    <div class="alert alert-danger m-t-2x" id="sitejetAlert" style="display: none;">{lang key="errorButTryAgain"}</div>
</div>
          
<script>
    jQuery(document).ready(function() {
        let sitejetPreviewImage = jQuery('#sitejetPromoImage');

        jQuery('#sitejetServiceSelect').on('change', function (e) {
         
            let serviceId = jQuery(e.target).val();

            if (serviceId) {
                jQuery('#sitejetSsoButton').data('serviceid', serviceId);
                
                // if this were to fail to load, the below onerror handler will fall back to a placeholder image
                sitejetPreviewImage.attr(
                    'src',
                    WHMCS.utils.getRouteUrl('/clientarea/sitejet/service/' + serviceId + '/preview')
                );
                sitejetPreviewImage.data('is-placeholder', false);
            }
        })
        sitejetPreviewImage.on('load', function() {
            sitejetPreviewImage.parent().removeAttr('style');
        });
        
        sitejetPreviewImage.removeAttr('onerror').error(function() {
           
            if (!sitejetPreviewImage.data('is-placeholder')) {
                sitejetPreviewImage.attr(
                    'src',
                    whmcsBaseUrl + '/assets/img/sitejet/preview_placeholder.png'
                );

                sitejetPreviewImage.data('is-placeholder', true);
            }
        });
    });
</script>
