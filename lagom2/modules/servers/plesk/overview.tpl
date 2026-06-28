{if file_exists("templates/$template/modules/servers/plesk/overwrites/overview.tpl")}
    {include file="{$template}/modules/servers/plesk/overwrites/overview.tpl"}  
{else}
    <script src="modules/servers/plesk/js/client.js?v={$versionHash}"></script>

    <div class="product-details">
        <div class="row row-eq-height row-eq-height-sm">
            <div class="col-md-6">
                {if $isSitejetActive}
                    <div class="product-icon panel panel-default" id="pleskPackagePanel">
                        <div class="product-content">
                            <div class="product-image">
                                <img class="card-img-top"
                                    id="sitejetPublishPreview"
                                    alt="Sitejet Preview"
                                    data-serviceid="{$serviceId}"
                                    data-src="{fqdnRoutePath('clientarea-sitejet-get-preview', $serviceId)}"
                                    src="{fqdnRoutePath('clientarea-sitejet-get-preview', $serviceId)}"
                                    onerror="fallbackSitejetPreview(event)"
                                    onload="sitejetPreviewLoadComplete(event)"
                                    data-publish-in-progress="false"
                                    style="max-width: 100%; border: 1px solid #ddd; transition: opacity 0.2s ease-in-out; opacity: 0"
                                />
                            </div>    
                            <h2 class="product-name">
                                <span class="product-group-name">{$groupname} - </span>{$product} <span class="label label-success">{$status}</span>
                            </h2>
                            <div
                                id="sitejetPublishProgressBarWrapper"
                                class="product-progress progress w-100 m-b-1x"
                                style="display: none;"
                            >
                                <div class="progress-bar bg-primary"
                                     role="progressbar"
                                     aria-valuenow="0"
                                     aria-valuemin="0"
                                     aria-valuemax="100"
                                ></div>
                            </div>
                            <div id="sitejetAlert"
                                 class="product-progress-text text-small"
                                 role="alert"
                                 data-progress-text="{lang key='sitejetBuilder.servicePage.publishProgress'}"
                                 data-success-text="{lang key='sitejetBuilder.servicePage.publishSuccess'}"
                                 data-error-text="{lang key='clientareaerroroccured'}"
                                 style="display: none"
                            ></div>
                        </div>
                        <div class="product-footer">
                            {if $domain}  
                                {if isset($RSThemes['pages'][$templatefile]) && $RSThemes['pages'][$templatefile]['config']['removeUrlFromDomainName'] == "0"}<a  href="http://{$domain}">{$domain}</a>{else}<span>{$domain}</span>{/if}
                            {/if}
                            {if $isSitejetSsoAvailable}
                                <button id="sitejetEditBtn"
                                        class="btn btn-link btn-xs btn-custom-action div-service-item"
                                        data-serviceid="{$serviceId}"
                                        data-identifier="sitejet"
                                        data-active="true"
                                        data-ca-target="_self"
                                        {if $sitejetPublish}data-do-publish="true"{/if}
                                        style="display: inline-block"
                                >
                                    {lang key='sitejetBuilder.servicePage.editSite'}
                                </button>
                            {/if}
                        </div>
                    </div>
                {else}
                    <div class="product-icon" id="pleskPackagePanel">
                        <div class="product-content">
                            <div class="product-image">
                                {include file="$template/includes/common/svg-icon.tpl" icon="addon" onDark=true}
                            </div>
                            <h2 class="product-name"><span class="product-group-name">{$groupname} - </span>{$product}</h2>
                            <div class="product-status">{$LANG.clientareastatus}:
                                <span class="label label-success">{$status}</span>
                            </div>
                        </div>
                        {if $domain}  
                            {if isset($RSThemes['pages'][$templatefile]) && $RSThemes['pages'][$templatefile]['config']['removeUrlFromDomainName'] == "0"}<a class="product-footer" href="http://{$domain}">{$domain}</a>{else}<span class="product-footer">{$domain}</span>{/if}
                        {/if}
                    </div>
                {/if}
            </div>
            <div class="col-md-6">
                <div class="panel panel-default cpanel-usage-stats" id="cPanelUsagePanel">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col" id="diskUsage">
                                <div class="usage-stats">
                                    <span>{lang key='diskUsage'}</span>
                                    <div class="knob-dial">
                                    <input type="text" value="{$diskpercent|substr:0:-1}" class="usage-dial" data-bgColor="#e6e8ec" data-fgColor="#0c70de" data-angleOffset="-125" data-angleArc="250" data-min="0" data-max="{if substr($diskpercent, 0, -1) > 100}{$diskpercent|substr:0:-1}{else}100{/if}" data-readOnly="true" data-width="104" data-thickness=.3 data-height="80" />        
                                    </div>
                                    <span>{$diskusage} M / {$disklimit} M</span>
                                </div>
                            </div>
                            <div class="col" id="bandwidthUsage">
                                <div class="usage-stats">
                                    <span>{lang key='bandwidthUsage'}</span>
                                    <input type="text" value="{$bwpercent|substr:0:-1}" class="usage-dial" data-bgColor="#e6e8ec" data-fgColor="#0c70de" data-angleOffset="-125" data-angleArc="250" data-min="0" data-max="{if substr($bwpercent, 0, -1) > 100}{$bwpercent|substr:0:-1}{else}100{/if}" data-readOnly="true" data-width="104" data-thickness=.3 data-height="80" />
                                    <span>{$bwusage} M / {$bwlimit} M</span>
                                </div>
                            </div>
                        </div>
                        <script src="{$BASE_PATH_JS}/jquery.knob.js"></script>
                        <script type="text/javascript">
                            jQuery(function() {
                                jQuery(".usage-dial").knob({
                                    'change': function (v) { },
                                    draw: function () {
                                        if ($(this.i).val() > 0){
                                            $(this.i).val(this.cv + '%');
                                        }
                                    
                                    }
                                });
                            });
                        </script>
                    </div>
                    <div class="panel-footer">
                        {if $bwpercent|substr:0:-1 > 75}
                            <div class="text-danger text-center">
                                 {if $bwpercent|substr:0:-1 > 100}
                                    {lang key='usageStatsBwOverLimit'}
                                {else}
                                    {lang key='usageStatsBwLimitNear'}
                                {/if}
                                {if $packagesupgrade}
                                    <a href="upgrade.php?type=package&id={$serviceid}" class="btn btn-xs btn-danger">
                                        <i class="fa fa-arrow-circle-up"></i>
                                         {lang key='usageUpgradeNow'}
                                    </a>
                                {/if}
                            </div>
                        {elseif $diskpercent|substr:0:-1 > 75}
                            <div class="text-danger text-center">
                                {if $diskpercent|substr:0:-1 > 100}
                                    {lang key='usageStatsDiskOverLimit'}
                                {else}
                                    {lang key='usageStatsDiskLimitNear'}
                                {/if}
                                {if $packagesupgrade}
                                    <a href="upgrade.php?type=package&id={$serviceid}" class="btn btn-xs btn-danger">
                                        <i class="fa fa-arrow-circle-up"></i>
                                        {lang key='usageUpgradeNow'}
                                    </a>
                                {/if}
                            </div>
                        {else}
                            <div class="text-center text-center">
                                {lang key='usageLastUpdated'} {$lastupdate}
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>    

    {if count($wpInstances) || $allowWpClientInstall}
        <div class="section">
            <h3>WordPress®</h3>
            <div class="panel panel-default" id="pleskWordPress" data-service-id="{$serviceId}" data-wp-domain="{$wpDomain}">
                <div {if count($wpInstances)}class="panel-body"{else} style="display: none" {/if}>
                    <div class="row{if !$allowWpClientInstall} no-margin{/if}" id="wordpressInstanceRow">
                        <div class="col-md-4">
                            <select class="form-control" id="wordPressInstances">
                                {foreach $wpInstances as $wpInstance}
                                    <option value="{$wpInstance.instanceUrl}">
                                        {$wpInstance.blogTitle}
                                        {if $wpInstance.path} ({$wpInstance.path}){/if}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline btn-block" id="btnGoToWordPressHome">
                                <i class="ls ls-dashboard"></i>
                                {lang key='wptk.goToWebsite'}
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline btn-block" id="btnGoToWordPressAdmin">
                                <i class="ls ls-sign-in"></i>
                                {lang key='wptk.goToAdmin'}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="panel-body panel-form">        
                    <div class="row" {if !$allowWpClientInstall}style="display: none"{/if}>
                        <div class="col-md-12">
                            <h5>{lang key='wptk.createNew'}</h5>
                            <p class="small" id="newWordPressFullUrlPreview">https://{$wpDomain}/</p>
                        </div>
                        <div class="col-md-12" id="wordPressInstallResultRow" style="display: none">
                            <div class="alert alert-lagom alert-primary alert-success" style="display: none">
                                {lang key='wptk.installationSuccess'}
                            </div>
                            <div class="alert alert-lagom alert-primary alert-danger" style="display: none">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="wpNewBlogTitle" placeholder="New Blog Title" />
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="wpNewPath" placeholder="Path (optional)" />
                        </div>
                        <div class="col-md-3">
                            <input type="password" class="form-control" id="wpAdminPass" placeholder="Admin Password" />
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary btn-block" id="btnInstallWordpress">
                                <i class="fal fa-plus"></i>
                                {lang key='wptk.installWordPressShort'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/if}

    <div class="section">
        <div class="section-header">
            <h2 class="section-title">{lang key='quickShortcuts'}</h2>
        </div>
        <div class="section-body">
            <div class="panel panel-default" id="cPanelQuickShortcutsPanel">
                <div class="panel-body">
                    <div class="row cpanel-feature-row">
                        <div class="col-sm-3 col-xs-6">
                            <a href="clientarea.php?action=productdetails&id={$serviceid}&dosinglesignon=1&success_redirect_url=%2Fsmb%2Femail-address%2Flist%2FdomainId%2F{$domainId}"
                            target="_blank" class="btn btn-link btn-block">
                                <i class="lm lm-envelope"></i>
                                {lang key='plesk.mail'}
                            </a>
                        </div>
                        <div class="col-sm-3 col-xs-6">
                            <a href="clientarea.php?action=productdetails&id={$serviceid}&dosinglesignon=1&success_redirect_url=%2Fsmb%2Fapp%2Finstalled%2FdomainId%2F{$domainId}"
                            target="_blank" class="btn btn-link btn-block">
                                <i class="lm lm-apps-fat"></i>
                                {lang key='plesk.applications'}
                            </a>
                        </div>
                        <div class="col-sm-3 col-xs-6">
                            <a href="clientarea.php?action=productdetails&id={$serviceid}&dosinglesignon=1&success_redirect_url=%2Fsmb%2Ffile-manager%2Flist%2FdomainId%2F{$domainId}"
                            target="_blank" class="btn btn-link btn-block">
                                <i class="lm lm-folder"></i>
                                {lang key='fileManager'}
                            </a>
                        </div>
                        <div class="col-sm-3 col-xs-6">
                            <a href="clientarea.php?action=productdetails&id={$serviceid}&dosinglesignon=1&success_redirect_url=%2Fsmb%2Fdatabase%2Flist%2FdomainId%2F{$domainId}"
                            target="_blank" class="btn btn-link btn-block">
                                <i class="lm lm-database"></i>
                                {lang key='mysqlDatabases'}
                            </a>
                        </div>
                        <div class="col-sm-3 col-xs-6">
                            <a href="clientarea.php?action=productdetails&id={$serviceid}&dosinglesignon=1&success_redirect_url=%2Fsmb%2Faccount%2Fswitch%2Fid%2F{$domainId}%3FhideNotice%3D1%26returnUrl%3D%2Fsmb%2Fstatistics%2Fdetails"
                            target="_blank" class="btn btn-link btn-block">
                                <i class="lm lm-line-graph"></i>
                                {lang key='plesk.statistics'}
                            </a>
                        </div>
                        <div class="col-sm-3 col-xs-6">
                            <a href="clientarea.php?action=productdetails&id={$serviceid}&dosinglesignon=1&success_redirect_url=%2Fsmb%2Fuser%2Flist"
                            target="_blank" class="btn btn-link btn-block">
                                <i class="lm lm-user"></i>
                                {lang key='plesk.users'}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="section">
        <div class="section-body">
            <div class="panel panel-default">
            <div class="panel-nav">
                <ul class="nav nav-tabs">
                    <li>
                        <a href="#billingInfo" class="active" data-toggle="tab"><i class="ls ls-wallet"></i> {lang key='billingOverview'}</a>
                    </li>
                    <li>
                        <a href="#domainInfo" data-toggle="tab" aria-expanded="true"><i class="ls ls-location"></i>{$LANG.clientareahostingdomain}</a>
                    </li>
                </ul>
            </div> 
            <div class="tab-content">
                <div class="panel-body tab-pane active billingOverview" id="billingInfo">
                    <div class="row">	
                        {if $firstpaymentamount neq $recurringamount}
                            <div class="col-sm-6 col-md-3 m-b-2x" id="firstPaymentAmount">
                                <div class="text-faded text-small">
                                    {$LANG.firstpaymentamount}
                                </div>
                                <div>
                                    {$firstpaymentamount}
                                </div>
                            </div>
                            {/if}
                            <div class="col-sm-6 col-md-3 m-b-2x" id="registrationDate">
                                <div class="text-faded text-small">
                                    {$LANG.clientareahostingregdate}
                                </div>
                                <div>
                                    {$regdate}
                                </div>
                            </div>
                            {if $billingcycle != $LANG.orderpaymenttermonetime && $billingcycle != $LANG.orderfree}
                                <div class="col-sm-6 col-md-3 m-b-2x" id="recurringAmount">
                                    <div class="text-faded text-small">
                                        {$LANG.recurringamount}
                                    </div>
                                    <div>
                                        {$recurringamount}
                                    </div>
                                </div>
                            {/if}
                            <div class="col-sm-6 col-md-3 m-b-2x" id="nextDueDate">
                                <div class="text-faded text-small">
                                    {$LANG.clientareahostingnextduedate}
                                </div>
                                <div>
                                    {$nextduedate}
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3 m-b-2x" id="billingCycle">
                                <div class="text-faded text-small">
                                    {$LANG.orderbillingcycle}
                                </div>
                                <div>
                                    {$billingcycle}
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3 m-b-2x" id="paymentMethod">
                                <div class="text-faded text-small">
                                    {$LANG.orderpaymentmethod}
                                </div>
                                <div>
                                    {$paymentmethod}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body tab-pane" id="domainInfo">
                        <div class="cpanel-actions-container">
                            <p class="text-center">{$domain}</p>
                            <div class="cpanel-actions-btn text-center">
                                <a href="http://{$domain}" class="btn btn-primary" target="_blank">{$LANG.visitwebsite}</a>
                                <a href="{$ssoLoginUrl}" class="btn btn-success" target="_blank">{lang key='plesk.manageDomains'}</a>
                            </div>
                        </div>
                    </div>
                </div>           
            </div>
        </div>
    </div>
    {* Package Options Section *}
    {* <div class="section">
        <div class="section-body">
            <div class="panel panel-default">
                <div class="panel-nav">
                    <ul class="nav nav-tabs">
                        <li>
                            <a href="#packageOptions" class="active" data-toggle="tab">Package Options</a>
                        </li>
                    </ul>
                </div> 
                <div class="tab-content">
                    <div class="panel-body tab-pane active" id="packageOptions">
                        <div class="row">	
                            {foreach $configurableoptions as $option}
                                <div class="col-sm-6 col-md-3 m-b-2x" id="firstPaymentAmount">
                                    <div class="text-faded text-small">
                                        {$option.optionname}
                                    </div>
                                    <div>
                                        {$option.selectedoption}
                                    </div>
                                </div>
                            {/foreach}
                        </div>
                    </div>
                    <div class="panel-footer panel-footer-sm">
                        <a class="panel-footer-link" href="/upgrade.php?type=configoptions&id={$serviceId}">{lang key='upgradedowngradeconfigoptions'}</a>
                    </div>
                </div>           
            </div>
        </div>
    </div> *}

    {if $availableAddonProducts}
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">{lang key='addonsExtras'}</h3>
            </div>
            <div class="section-body">
                <div class="panel panel-form" id="cPanelExtrasPurchasePanel">
                    <div class="panel-body">
                        <form method="post" action="{$WEB_ROOT}/cart.php?a=add">
                            <input type="hidden" name="serviceid" value="{$serviceid}" />
                            <div class="row row-sm">
                                <div class="col-sm-8">
                                <select name="aid" class="form-control">
                                    {foreach $availableAddonProducts as $addonId => $addonName}
                                        <option value="{$addonId}">{$addonName}</option>
                                    {/foreach}
                                </select>
                                </div>
                                <div class="col-sm-4">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        {lang key='purchaseActivate'}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>    
    {/if}

    {if !$isSitejetActive && $availableSitejetAddons->count()}
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">{lang key='sitejetBuilder.get.title'}</h3>
            </div>
            <div class="section-body">
                <div class="panel panel-form">
                    <div class="panel-body">
                        <div class="row row-sm">
                            <div class="col-md-8">
                                <p>{lang key='sitejetBuilder.upsellDescription'}</p>
                            </div>
                            <div class="col-md-4">
                                {foreach $availableSitejetAddons as $availableSitejetAddon}
                                    <a href="cart.php?a=add&aid={$availableSitejetAddon->id}&serviceid={$serviceId}"class="btn btn-primary btn-block">
                                        {lang key='activateNowFor' price=$availableSitejetAddon->pricing()->best()->breakdownPrice()}
                                    </a>
                                    {break}
                                {/foreach}
                            </div>
                        </div>    
                    </div>
                </div>
            </div>
        </div>    
    {elseif !$isSitejetActive && $availableSitejetProductUpgrades->count()}
        <div class="section" id="pleskGetSitejet">
            <div class="section-header">
                <h3 class="section-title">{lang key='sitejetBuilder.upgradeTo.title'}</h3>
            </div>
            <div class="section-body">
                <div class="panel panel-form">
                    <div class="panel-body">
                        <div class="row row-sm">
                            <div class="col-md-8">
                                <p>{lang key='sitejetBuilder.upsellDescription'}</p>
                            </div>
                            <div class="col-md-4">
                                {foreach $availableSitejetProductUpgrades as $availableSitejetProductUpgrade}
                                        <form method="post" action="upgrade.php">
                                            <input type="hidden" name="step" value="2">
                                            <input type="hidden" name="type" value="package">
                                            <input type="hidden" name="id" value="{$serviceId}">
                                            <input type="hidden" name="pid" value="{$availableSitejetProductUpgrade->id}">
                                            <input type="hidden" name="billingcycle" value="{$availableSitejetProductUpgrade->pricing()->best()->cycle()}">
                                            <button type="submit" name="upgradeSitejet" class="btn btn-primary btn-block">
                                                {lang key='upgradeToFor'
                                                package=$availableSitejetProductUpgrade->name
                                                price=$availableSitejetProductUpgrade->pricing()->best()->breakdownPrice()
                                                }
                                            </button>
                                        </form>
                                    {break}
                                {/foreach}
                            </div>
                        </div>    
                    </div>
                </div>
            </div>
        </div>   
    {/if}
{/if}