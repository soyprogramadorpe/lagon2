<?php

return [
    'display_name' => 'Default',
    'preview'      => 'thumb.png',
    'settings'     => [
        'hideSidebar' => [
            'type' => 'checkbox',
            'name' => 'hideSidebar',
            'label' => 'Hide Sidebar',
            'tooltip' => "Choose whether you would like to show or hide the content sidebar available on this page."    
        ],
        'freeProductCancellation' => [
            'type' => 'checkbox',
            'name' => 'freeProductCancellation',
            'label' => 'Free Services Request Cancellation',
            'tooltip' => "Show Request Cancellation Option on Free Services."  
        ],
        'removeUrlFromDomainName' => [
            'type' => 'checkbox',
            'name' => 'removeUrlFromDomainName',
            'label' => 'Remove URL from the domain/host name',
            'tooltip' => "Remove anchor link from the domain/host name.",
        ],
        'removeProductGroupName' => [
            'type' => 'checkbox',
            'name' => 'removeProductGroupName',
            'label' => 'Hide Group Name From Product Header',
            'tooltip' => "Choose whether you would like to show or hide Product Group Name in product header.",
        ],
        'hideRightBoxWithDetailsUsage' => [
            'type' => 'checkbox',
            'name' => 'hideRightBoxWithDetailsUsage',
            'label' => 'Hide Right Box with Billing Details/Server Usage',
            'tooltip' => "Choose whether you would like to show or hide Billing Details/Server Usage box on this page.",
        ],
        'showProductAddonsId' => [
            'type' => 'checkbox',
            'name' => 'showProductAddonsId',
            'label' => 'Display addon ID',
            'tooltip' => "Choose whether you would like to show or hide addon ID in Addon tab.",
        ]
    ]
];