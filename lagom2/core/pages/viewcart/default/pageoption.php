<?php

return [
    'display_name' => 'Default',
    'preview'      => 'thumb.png',
    'variables' => [

    ],
    'settings'     => [
        'hideProductBillingCycleDropdown' => [
            'type' => 'checkbox',
            'name' => 'hideProductBillingCycleDropdown',
            'label' => 'Hide Product Billing Cycle Dropdown',
            'tooltip' => "Choose whether you would like to show or hide the billing cycle dropdown displayed in the checkout table.",
        ],
        'hidePromoBox' => [
            'type' => 'checkbox',
            'name' => 'hidePromoBox',
            'label' => 'Hide Promo Box',
            'tooltip' => "Choose whether you would like to show or hide the promotion box.",
        ],
        'hideCreateSubAccount' => [
            'type' => 'checkbox',
            'name' => 'hideCreateSubAccount',
            'label' => 'Hide The Option To Add New Billing Address',
            'tooltip' => "Choose whether you would like to show or hide create sub-account - for logged in user only.",
        ],
        'tosLocation' => [
            'type' => 'select',
            'name' => 'tosLocation',
            'label' => 'TOS Location',
            'options' => 'Default,Above CTA button,End of order page',
            'default' => 'Default',
            'tooltip' => "Determine the TOS placement that best complements your checkout process: Stick with the default, make it unmissable above the CTA, or conclude with it at the order page's end."    
        ],
        'hideJoinToMailingList' => [
            'type' => 'checkbox',
            'name' => 'hideJoinToMailingList',
            'label' => 'Hide Marketing Email Box',
            'tooltip' => "Choose whether you would like to show or hide the marketing email box.",
        ],
        'removeProductGroupName' => [
            'type' => 'checkbox',
            'name' => 'removeProductGroupName',
            'label' => 'Hide Group Name From Summary Table',
            'tooltip' => "Upon activation, the product group name will be omitted from the product name in the view cart summary table.",
        ],
        'companyNameRequired' => [
            'type' => 'checkbox',
            'name' => 'companyNameRequired',
            'label' => 'Set "Company Name" Field As Required',
            'tooltip' => 'Choose whether you would like to make "Company Name" field as required in registration form.'
        ],
    ]
];