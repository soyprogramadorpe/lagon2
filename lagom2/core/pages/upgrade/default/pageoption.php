<?php

return [
    'display_name' => 'Default',
    'preview'      => 'thumb.png',
    'variables' => [

    ],
    'settings'     => [
        'productColumns' => [
            'type' => 'select',
            'name' => 'productColumns',
            'label' => 'Product Columns',
            'options' => '1,2,3',
            'default' => '3',
            'tooltip' => "Choose whether you would like to show or hide the content sidebar available on this page."    
        ],
        'hideUpgradeConfigOptions' => [
            'type' => 'multiselect',
            'name' => 'hideUpgradeConfigOptions',
            'default' => 'none',
            'customSelectClass' => 'm-w-dropdown-500',
            'dataAttr' => 'data-select-none',
            'label' => 'Hide Configurable Options',
            'tooltip' => "Select the Configurable Options you want to hide on the product's Upgrade/Downgrade page."
        ],
    ]
];