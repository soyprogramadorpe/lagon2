{if isset($RSThemes['pages'][$templatefile]) && file_exists($RSThemes['pages'][$templatefile]['fullPath'])}
    {include file=$RSThemes['pages'][$templatefile]['fullPath']}
{else}    
    {if $overdueinvoice}
        <div class="message message-danger message-lg message-no-data">
            <div class="message-icon">
                <i class="lm lm-close"></i>
            </div>
            <h3 class="message-title">{$rslang->trans('nodata.upgrade_not_available')}</h3>
            <p class="message-desc">{$LANG.upgradeerroroverdueinvoice}</p>
            <a href="clientarea.php?action=productdetails&id={$id}" class="btn btn-default">
                {$LANG.backtoservicedetails}
            </a>
        </div>
    {elseif $existingupgradeinvoice}
        <div class="message message-danger message-lg message-no-data">
            <div class="message-icon">
                <i class="lm lm-close"></i>
            </div>
            <h3 class="message-title">{$rslang->trans('nodata.upgrade_not_available')}</h3>
            <p class="message-desc">{$LANG.upgradeexistingupgradeinvoice}</p>
            <a href="submitticket.php" class="btn btn-default">
                {$LANG.submitticketdescription}
            </a>
        </div>
    {elseif $upgradenotavailable}
        <div class="message message-danger message-lg message-no-data">
            <div class="message-icon">
                <i class="lm lm-close"></i>
            </div>
            <h3 class="message-title">{$rslang->trans('nodata.upgrade_not_available')}</h3>
            <p class="message-desc">{$LANG.upgradeNotPossible}</p>
            <a href="submitticket.php" class="btn btn-default">
                {$LANG.submitticketdescription}
            </a>
        </div>
    {else}
        {if $type eq "package"}
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">{$LANG.upgradecurrentconfig}:</h2>
                </div>
                <div class="section-body">
                    <div class="panel panel-default active">
                        <div class="panel-body">
                            <strong>{$groupname} - {$productname}</strong> {if $domain} ({$domain}){/if}
                        </div>
                    </div>
                </div>
            </div>
            <div class="section">
                <div class="section-header">
                     <h2 class="section-title">{$LANG.upgradenewconfig}:</h2>
                </div>
                <div class="section-body">
                    {if $upgradepackages}
                        <div class="row row-eq-height row-eq-height-sm">
                            {assign var=counter value=1}
                            {assign var=upgradepackagesCount value=$upgradepackages|count}
                            {$showFreeLang = false}
                            {$pricingTerm}
                            {if                                                                            
                                isset($RSThemes.addonSettings.free_product_price) && 
                                $RSThemes.addonSettings.free_product_price == "enabled" &&
                                isset($RSThemes.addonSettings.free_product_price_value) &&
                                $RSThemes.addonSettings.free_product_price_value == "all"
                            }
                                {foreach key=num item=upgradepackage from=$upgradepackages}
                                    {if $upgradepackage.pricing.minprice.simple != "-1.00" && $upgradepackage.pricing.minprice.simple != "-1,00"}
                                        {if $upgradepackage.pricing.minprice.simple|strstr:","}
                                            {$pricingTerm = "comma"}
                                        {/if}
                                        {break}
                                    {/if}
                                {/foreach}
                                {$showFreeLang = true}
                                {if $pricingTerm == "comma"}
                                    {$freeLangReplaceTerm = "{$WHMCSCurrency.prefix}0,00{$WHMCSCurrency.suffix}"}
                                {else}
                                    {$freeLangReplaceTerm = "{$WHMCSCurrency.prefix}0.00{$WHMCSCurrency.suffix}"}
                                {/if}
                            {/if}

                            {foreach key=num item=upgradepackage from=$upgradepackages}
                                <div class="col{if $RSThemes['pages']['upgrade']['config']['productColumns'] == 1} col-12{/if}">
                                    <form class="package" method="post" action="{$smarty.server.PHP_SELF}">
                                        <input type="hidden" name="step" value="2">
                                        <input type="hidden" name="type" value="{$type}">
                                        <input type="hidden" name="id" value="{$id}">
                                        <input type="hidden" name="pid" value="{$upgradepackage.pid}">
                                        <h3 class="package-title m-t-0">{$upgradepackage.groupname} - {$upgradepackage.name}</h3>
                                        <div class="package-content">
                                            <div class="form-group m-b-3x">
                                                {if $upgradepackage.pricing.type eq "free"}
                                                    <input type="hidden" name="billingcycle" value="free">
                                                    <div class="package-price">
                                                        <div class="price">
                                                            {$LANG.orderfree}
                                                        </div>
                                                    </div>
                                                {elseif $upgradepackage.pricing.type eq "onetime"}
                                                    <input type="hidden" name="billingcycle" value="onetime">
                                                    <div class="package-price">
                                                        <div class="price">{$upgradepackage.pricing.onetime}
                                                        </div>
                                                        <div class="package-setup-fee">{$LANG.orderpaymenttermonetime}</div>
                                                    </div>        
                                                {elseif $upgradepackage.pricing.type eq "recurring"}
                                                    <select name="billingcycle" class="form-control input-lg">
                                                        {if $upgradepackage.pricing.monthly}
                                                            <option value="monthly">
                                                                {if $showFreeLang}
                                                                    {$upgradepackage.pricing.monthly|replace:$freeLangReplaceTerm:$LANG.orderfree}
                                                                {else}
                                                                    {$upgradepackage.pricing.monthly}
                                                                {/if}
                                                            </option>
                                                        {/if}
                                                        {if $upgradepackage.pricing.quarterly}
                                                            <option value="quarterly">
                                                                {if $showFreeLang}
                                                                    {$upgradepackage.pricing.quarterly|replace:$freeLangReplaceTerm:$LANG.orderfree}
                                                                {else}
                                                                    {$upgradepackage.pricing.quarterly}
                                                                {/if}
                                                            </option>
                                                        {/if}
                                                        {if $upgradepackage.pricing.semiannually}
                                                            <option value="semiannually">
                                                                {if $showFreeLang}
                                                                    {$upgradepackage.pricing.semiannually|replace:$freeLangReplaceTerm:$LANG.orderfree}
                                                                {else}
                                                                    {$upgradepackage.pricing.semiannually}
                                                                {/if}                                                            
                                                            </option>
                                                        {/if}
                                                        {if $upgradepackage.pricing.annually}
                                                            <option value="annually">
                                                                {if $showFreeLang}
                                                                    {$upgradepackage.pricing.annually|replace:$freeLangReplaceTerm:$LANG.orderfree}
                                                                {else}
                                                                    {$upgradepackage.pricing.annually}
                                                                {/if}   
                                                            </option>
                                                        {/if}
                                                        {if $upgradepackage.pricing.biennially}
                                                            <option value="biennially">
                                                                {if $showFreeLang}
                                                                    {$upgradepackage.pricing.biennially|replace:$freeLangReplaceTerm:$LANG.orderfree}
                                                                {else}
                                                                    {$upgradepackage.pricing.biennially}
                                                                {/if}     
                                                            </option>
                                                        {/if}
                                                        {if $upgradepackage.pricing.triennially}
                                                            <option value="triennially">
                                                                {if $showFreeLang}
                                                                    {$upgradepackage.pricing.triennially|replace:$freeLangReplaceTerm:$LANG.orderfree}
                                                                {else}
                                                                    {$upgradepackage.pricing.triennially}
                                                                {/if}    
                                                            </option>
                                                        {/if}
                                                    </select>
                                                {/if}
                                            </div>  
                                            <ul class="package-features">
                                                <li>
                                                    {$upgradepackage.description|replace:"<br/>":"</li>"}
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="package-footer">
                                            <div class="package-actions">
                                                <input type="submit" value="{$LANG.upgradedowngradechooseproduct}" class="btn btn-lg btn-primary" id="btnUpgradeDowngradeChooseProduct"/>                                        
                                            </div>
                                        </div>
                                    </form>    
                                </div>
                                {if $RSThemes['pages'][$templatefile]['config']['productColumns'] == '2'}
                                    {if $counter % 2 == 0}</div><div class="row row-eq-height row-eq-height-sm">{/if}
                                {elseif $RSThemes['pages'][$templatefile]['config']['productColumns'] == '3'}
                                    {if $counter % 3 == 0}</div><div class="row row-eq-height row-eq-height-sm">{/if}
                                {elseif $RSThemes['pages'][$templatefile]['config']['productColumns'] == '4'}
                                    {if $counter % 4 == 0}</div><div class="row row-eq-height row-eq-height-sm">{/if}
                                {else}
                                    {if $upgradepackagesCount == '4'}
                                        {if $counter % 2 == 0}</div><div class="row row-eq-height row-eq-height-sm">{/if}
                                    {/if}
                                {/if}
                                {$counter = $counter +1}
                            {/foreach}
                        </div>   
                    {else}     
                        <div class="message message-danger message-lg">
                            <div class="message-icon">
                                <i class="lm lm-close"></i>
                            </div>
                            <h6 class="message-title">{$LANG.outofstock}</h6>
                            <p class="message-desc">{$LANG.outofstockdescription}</p>
                            <div class="message-action">
                                <a class="btn btn-primary" href="{$WEB_ROOT}/submitticket.php">
                                    {$LANG.contactUs}
                                </a>
                            </div>
                        </div>
                    {/if}
                </div>
            </div>
        {elseif $type eq "configoptions"}
            <p>{$LANG.upgradechooseconfigoptions}</p>
            {if $errormessage}
                {include file="$template/includes/alert.tpl" type="error" errorshtml=$errormessage}
            {/if}
            <form method="post" action="{$smarty.server.PHP_SELF}">
                <input type="hidden" name="step" value="2" />
                <input type="hidden" name="type" value="{$type}" />
                <input type="hidden" name="id" value="{$id}" />
                {foreach key=num item=configoption from=$configoptions}
                    {$hideConfigOption = ''}
                    {if isset($RSThemes['pages'][$templatefile]['config']['hideUpgradeConfigOptions']) && is_array($RSThemes['pages'][$templatefile]['config']['hideUpgradeConfigOptions'])}
                        {$hideOptionArray = $RSThemes['pages'][$templatefile]['config']['hideUpgradeConfigOptions']}
                        {if in_array($configoption.id, $hideOptionArray)}
                            {$hideConfigOption = 'hidden'}
                        {/if}
                    {/if}
                    <div class="upgrade-config-option {$hideConfigOption}">
                        <h5>{$configoption.optionname}</h5>
                        <div class="row row-eq-height m-b-neg-3x">
                            <div class="upgrade-current col-md-6">
                                <div class="panel panel-default panel-form active">
                                    <div class="panel-body">
                                        <h6>{$LANG.upgradecurrentconfig}</h6>						
                                        {if $configoption.optiontype eq 1 || $configoption.optiontype eq 2}	
                                            <input class="form-control" type="text" value="{$configoption.selectedname}" disabled="">
                                        {elseif $configoption.optiontype eq 3}
                                            <label class="switch switch--lg switch--text">
                                                <input class="switch__checkbox" type="checkbox" {if $configoption.selectedqty}checked=""{/if} disabled="">
                                                <span class="switch__container"><span class="switch__handle"></span></span>
                                            </label>
                                        {elseif $configoption.optiontype eq 4}
                                            <div class="form-group m-b-0x">
                                                 <input class="form-control" type="number" value="{$configoption.selectedqty}" disabled="">
                                                <div class="m-t-1x">x {$configoption.options.0.name}</div>
                                            </div>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                            <div class="upgrade-new col-md-6">          
                                <div class="panel panel-default panel-form">
                                    <div class="panel-body">
                                        <div class="d-flex m-b-2x align-items-center">
                                            <h6 class="m-b-0x">{$LANG.upgradenewconfig}</h6>
                                            {if $configoption.optiontype eq 4 && $configoption.qtymaximum > 0}
                                                <span class="label label-sm label-default {if $RSThemes.styles.name == "default"}label-outline{/if} m-l-a">
                                                    {$rslang->trans('upgrade.max_value')}
                                                    {$configoption.qtymaximum}
                                                </span>
                                            {/if}
                                        </div>
                                        
                                        {if $configoption.optiontype eq 1 || $configoption.optiontype eq 2}	
                                            <select name="configoption[{$configoption.id}]" class="form-control">
                                                {foreach key=num item=option from=$configoption.options}
                                                    {if $option.selected}
                                                        <option value="{$option.id}" selected>{$LANG.upgradenochange}</option>
                                                    {else}
                                                        <option value="{$option.id}">
                                                            {$option.nameonly}
                                                            {if (
                                                                    $option.price|replace:$WHMCSCurrency.prefix:""|replace:$WHMCSCurrency.suffix:"" == '0.00' ||
                                                                    $option.price|replace:$WHMCSCurrency.prefix:""|replace:$WHMCSCurrency.suffix:"" == '0,00'
                                                                ) && (
                                                                    isset($RSThemes.addonSettings.free_product_price) && 
                                                                    $RSThemes.addonSettings.free_product_price == "enabled" &&
                                                                    isset($RSThemes.addonSettings.free_product_price_value) &&
                                                                    $RSThemes.addonSettings.free_product_price_value == "all"
                                                                )
                                                            }
                                                                {$LANG.orderfree}
                                                            {else}
                                                                {$option.price}
                                                            {/if}
                                                        </option>
                                                    {/if}
                                                {/foreach}
                                            </select>
                                        {elseif $configoption.optiontype eq 3}
                                            <label class="switch switch--lg switch--text">
                                                <input class="switch__checkbox" type="checkbox" name="configoption[{$configoption.id}]" value="1"{if $configoption.selectedqty} checked{/if}>
                                                <span class="switch__container"><span class="switch__handle"></span></span>
                                            </label>
                                        {elseif $configoption.optiontype eq 4}
                                            <div class="form-group m-b-0x">
                                                <input class="form-control" type="number" min={$configoption.qtyminimum} {if $configoption.qtymaximum > 0}max="{$configoption.qtymaximum}"{/if} name="configoption[{$configoption.id}]" value="{$configoption.selectedqty}">
                                                <div class="m-t-1x">x {$configoption.options.0.name}</div>
                                            </div>
                                        {/if}
                                    </div>
                                </div>                            
                            </div>
                        </div>
                    </div>
                {/foreach}
                <div class="form-actions">
                    <input type="submit" value="{$LANG.ordercontinuebutton}" class="btn btn-primary" />
                </div>
            </form>
        {/if}
    {/if}
{/if}