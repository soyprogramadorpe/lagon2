{if file_exists("templates/$template/includes/common/overwrites/seo.tpl")}
     {include file="{$template}/includes/common/overwrites/seo.tpl"}  
{else}
    <title>
        {if isset($pageSeo['title'][$language]) && isset($pageSeo['enabled']) && $pageSeo['enabled']}
            {$pageSeo['title'][$language]}
        {elseif $kbarticle.title}
            {$kbarticle.title}    
        {elseif $templatefile == "viewinvoice" || $templatefile == "viewquote" || $templatefile == "clientareahome" || $templatefile == "viewticket"}
            {$pagetitle}
        {elseif $templatefile == "products"}
            {$groupname}
        {else}
            {$displayTitle}
        {/if} - {$companyname}
    </title>
    {if $templatefile == "knowledgebasearticle" || $templatefile == "viewannouncement" || $templatefile == "knowledgebasecat"}
        <meta name="description" content="{if $templatefile == "viewannouncement"}{$summary|replace:'"':''|strip:" "|truncate:155:"..."}{elseif $templatefile == "knowledgebasearticle"}{$kbarticle.text|strip_tags|replace:'"':''|strip:" "|truncate:155:"..."}{elseif $templatefile == "knowledgebasecat"}{$catname|strip_tags|replace:'"':''|strip:" "|truncate:155:"..."}{/if}">
        {if ($templatefile == "knowledgebasearticle" || $templatefile == "viewannouncement") && $RSThemes.addonSettings.enable_hreflang_links != "displayed"}
            <link rel="alternate" hreflang="{$activeLocale.locale|replace:"_":"-"}" href="{$seoHost}{if $currentUrl|substr:0:1 eq '/'}{$currentUrl|substr:1}{else}{$currentUrl}{/if}"/>
        {/if}
    {elseif $templatefile == "products"}
        {if $productGroup.tagline}
            <meta name="description" content="{$productGroup.tagline|replace:'"':''|truncate:155:"..."}">
        {/if}
        {foreach from=$productgroups item=foo}
            {if $foo.gid == $gid}
                {assign var=productSlug value=$foo.slug}
            {/if}
        {/foreach}
        {if $RSThemes.addonSettings.enable_hreflang_links != "displayed"}
            <link rel="alternate" hreflang="{$activeLocale.locale|replace:"_":"-"}" href="{$seoHost}{if $currentUrl|substr:0:1 eq '/'}{$currentUrl|substr:1}{else}{$currentUrl}{/if}"/>
        {/if}
    
    {elseif $siteSeoDesc}
        {if $templatefile == "store/sitebuilder/index"}
            <meta name="description" content="{$rslang->trans('sitebuilder.banner.title')|replace:'"':''|truncate:155:"..."}">
        {else}
            <meta name="description" content="{lang key=$siteSeoDesc|replace:'"':''|truncate:155:"..."}">
        {/if}
        {if $RSThemes.addonSettings.enable_hreflang_links != "displayed"}
            <link rel="alternate" hreflang="{$activeLocale.locale|replace:"_":"-"}" href="{$seoHost}{if $currentUrl|substr:0:1 eq '/'}{$currentUrl|substr:1}{else}{$currentUrl}{/if}"/>
        {/if}                
    {/if}
    {if isset($pageSeo['enabled']) && $pageSeo['enabled']}
        {if isset($pageSeo['description'][$language])}<meta name="description" content="{$pageSeo['description'][$language]}">{/if}
        {if isset($activeDisplay) && $activeDisplay == 'CMS'}
            <meta name="robots" content="{if $pageSeo['robots'] == 0}noindex nofollow{else}index follow{/if}">
        {/if}
        <meta name="og:type" content="{if $templatefile == 'homepage'}website{else}article{/if}">
        <meta name="og:title" content="{if isset($pageSeo['title'][$language])}{$pageSeo['title'][$language]}{else}{$displayTitle}{/if}">
        {if isset($pageSeo['description'][$language])}<meta name="og:description" content="{$pageSeo['description'][$language]}">{/if}
        {if isset($pageSeo['image'])}<meta property="og:image" content="{$systemurl}templates/{$template}/assets/img/page-manager/{$pageSeo['image']}">{/if}
        <meta name="og:url" content="{$systemurl}{$smarty.server.REQUEST_URI|ltrim:'/'}">
        <meta name="twitter:title" content="{if isset($pageSeo['title'][$language])}{$pageSeo['title'][$language]}{else}{$displayTitle}{/if}">
        {if isset($pageSeo['description'][$language])}<meta name="twitter:description" content="{$pageSeo['description'][$language]}">{/if}
        {if isset($pageSeo['image'])}<meta property="twitter:image" content="{$systemurl}templates/{$template}/assets/img/page-manager/{$pageSeo['image']}">{/if}
    {/if}
    <link rel="canonical" href="{$seoHost}{if $currentUrl|substr:0:1 eq '/'}{$currentUrl|substr:1|replace:"?currency={$WHMCSCurrency.id}":""|replace:"&currency={$WHMCSCurrency.id}":""|replace:"?language={$language}":""|replace:"&language={$language}":""}{else}{$currentUrl|replace:"?currency={$WHMCSCurrency.id}":""|replace:"&currency={$WHMCSCurrency.id}":""|replace:"?language={$language}":""|replace:"&language={$language}":""}{/if}">
    {if $RSThemes.addonSettings.enable_hreflang_links == "displayed"}
        {if $currentUrl|substr:0:1 eq '/'}
            {assign var=changedCurrentUrl value=$currentUrl|substr:1|replace:"?currency={$WHMCSCurrency.id}":""|replace:"&currency={$WHMCSCurrency.id}":""|replace:"?language={$language}":""|replace:"&language={$language}":""}
        {else}
            {assign var=changedCurrentUrl value=$currentUrl|replace:"?currency={$WHMCSCurrency.id}":""|replace:"&currency={$WHMCSCurrency.id}":""|replace:"?language={$language}":""|replace:"&language={$language}":""}
        {/if}
        {if $changedCurrentUrl|strstr:"?"}
            {$divChar = "&amp;"}
        {else}
            {$divChar = "?"}
        {/if}
        <link rel="alternate" hreflang="x-default" href="{$seoHost}{if $currentUrl|substr:0:1 eq '/'}{$currentUrl|substr:1|replace:"?currency={$WHMCSCurrency.id}":""|replace:"&currency={$WHMCSCurrency.id}":""}{else}{$currentUrl|replace:"?currency={$WHMCSCurrency.id}":""|replace:"&currency={$WHMCSCurrency.id}":""}{/if}">
        {foreach from=$locales item=language}
            <link rel="alternate" hreflang="{$language.languageCode|replace:"_":"-"}" href="{$seoHost}{$changedCurrentUrl}{$divChar}language={$language.language}">
        {/foreach}
    {/if}
{/if}