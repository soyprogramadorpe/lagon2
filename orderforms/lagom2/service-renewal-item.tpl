{foreach $renewableItems as $renewableItem}
<div class="panel panel-default panel-form service-renewal search-renewal {if $renewalTypeAddon}search-renewal-addons{/if}" 
        data-product-name="{$renewableItem.product->name}" 
        data-service-id="{$renewableItem.serviceId}" 
        data-service-domain="{$renewableItem.domain}" 
        data-search-name="{$renewableItemsGroups[$renewableItem.product.productGroupId]['name']} {$renewableItem.product->name} {$renewableItem.domain}"
        {if $renewableItem.renewable === false}style="display: none;" data-is-renewable="false" {else}data-is-renewable="true"{/if}
    >
        {if $renewalTypeAddon}
            <div class="addon-renewals-divider"></div>
        {/if}
        <div class="panel-body">
            <div class="domain-renewal-content">
                <div class="domain-renewal-title {$renewableServicesGroups[$renewalGroupId]['attributes']['name']}">
                    <div class="domain-renewal-title-right">
                        <div>{$renewableServicesGroups[$renewableItem.product.productGroupId]['name']} {if !$renewalTypeAddon}-{/if} {$renewableItem.product.name} 
                            {if $renewableItem.renewable === false}
                                <i class="lm lm-info" 
                                    data-toggle="tooltip" data-placement="top" data-title="{$renewableItem.reason}"
                                ></i>
                            {/if}
                        </div>
                        {if !$renewalTypeAddon}
                            <a class="domain-renewal-url" href="http://{$renewableItem.domain}" target="_blank">{$renewableItem.domain}</a>
                        {/if}
                    </div>
                </div>
                <div class="domain-renewal-periods">
                    {if is_null($renewableItem.nextDueDate)}
                        <div class="domain-renewal-next-due">
                            {lang key='renewService.serviceNextDueDateBasic' nextDueDate={lang key='na'}}
                            {if $renewableItem.renewable === false}
                                <div class="domain-renewal-status">
                                    <span class="label label-default label-xxs m-l-1x">
                                        {lang key='renewService.renewalUnavailable'}
                                    </span>
                                </div>
                            {else}
                                <div class="domain-renewal-status">
                            <span class="label label-xxs {if $renewableItem.nextDueDate->diffInDays() <= '31'} label-danger{else} label-warning{/if} m-l-1x">
                                        {lang key='renewService.renewingIn' days=$renewableItem.nextDueDate->diffInDays()}
                                    </span>
                                </div>
                            {/if}
                        </div>
                    {else}
                        <div class="domain-renewal-next-due">
                            {lang key='renewService.serviceNextDueDateBasic' nextDueDate=$renewableItem.nextDueDate->toClientDateFormat()}
                            {if $renewableItem.renewable === false}
                                <div class="domain-renewal-status">
                                    <span class="label label-default label-xxs m-l-1x">
                                        {lang key='renewService.renewalUnavailable'}
                                    </span>
                                </div>
                            {else}
                                <div class="domain-renewal-status">
                                    <span class="label label-xxs {if $renewableItem.nextDueDate->diffInDays() <= '31'} label-danger{else} label-warning{/if} m-l-1x">
                                        {lang key='renewService.renewingIn' days=$renewableItem.nextDueDate->diffInDays()}
                                    </span>
                                </div>
                            {/if}
                        </div>
                        <span>{lang key='renewService.renewalPeriodLabel'}</span>
                        <span>{lang key='renewService.renewalPeriod' nextDueDate=$renewableItem.nextDueDate->toClientDateFormat() nextPayUntilDate=$renewableItem.nextPayUntilDate->toClientDateFormat() renewalPrice=$renewableItem.price}</span>
                    {/if}
                </div>
            </div>
            <div class="domain-renewal-form">
                <div class="domain-renewal-actions">
                    {if $renewableItem.renewable === true}
                        <button id="renewService{$renewableItem.serviceId}" class="btn {if $renewalTypeAddon}btn-xs{/if} {if $renewal_added}btn-primary{else}btn-primary-faded{/if} btn-add-renewal-to-cart" data-service-id="{$prefix}{$renewableItem.serviceId}">
                            <div class="loader loader-button">
                                {include file="$template/includes/common/loader.tpl" classes="spinner-sm"}  
                            </div>
                            <span class="to-add" {if $renewal_added}style="display: none"{/if}>{lang key='addtocart'}</span>
                            <span class="added align-center" {if $renewal_added}style="display: flex"{/if}>
                                <i class="ls ls-check"></i> 
                                {lang key='domaincheckeradded'}
                            </span>
                        </button>
                    {/if}
                    </div>
                    {if $renewableItem.renewable === true}
                        <button type="button" class="btn {if $renewalTypeAddon}btn-xs{/if} btn-primary btn-remove-renewal" {if $renewal_added}style="display: flex"{/if} onclick="{if $renewalTypeAddon}removeItem('r','{{$renewableItem.serviceId}}','addon');{else}removeItem('r','{{$renewableItem.serviceId}}','service');{/if}"data-toggle="tooltip" title="{$LANG.orderForm.remove}">
                            <i class="{if $renewalTypeAddon}ls ls-trash{else}lm lm-trash{/if}"></i> 
                            <div class="loader loader-button hidden">
                                {include file="$template/includes/common/loader.tpl" classes="spinner-sm"}  
                            </div>
                        </button>
                    {/if}
            </div> 
        </div>
        {if !empty($renewableItem.addons)}
            <div class="addon-renewals"
                 {if $renewableItem.renewableCount <= 0}style="display: none;" data-is-renewable="false" {else}data-is-renewable="true"{/if}
            >
                <h4 class="addon-renewals-title m-b-2x">
                    {$LANG.cartaddons}
                    <div class="addon-renewals-title-line"></div>
                </h4>
                <div class="addon-renewals-content">
                    {include file="orderforms/$carttpl/service-renewal-item.tpl" renewalTypeAddon=true renewableItems=$renewableItem.addons prefix='a-'}
                </div>
            </div>
        {/if}
    </div>
{/foreach}
