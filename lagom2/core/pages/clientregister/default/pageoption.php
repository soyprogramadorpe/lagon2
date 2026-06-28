<?php

return [
    'display_name' => 'Default',
    'preview'      => 'thumb.png',
    'settings'     => [
        'isFullPage' => [
            'type' => 'checkbox',
            'name' => 'isFullPage',
            'label' => 'Full Page',
            'tooltip' => "Decide whether you would like to hide the Lagom theme navigation and footer."  
        ],
        'showLogo' => [
            'type'  => 'checkbox',
            'name'  => 'showLogo',
            'label' => 'Show Logo',
            'tooltip' => 'Sample'
        ],
        'hideJoinToMailingList' => [
            'type' => 'checkbox',
            'name' => 'hideJoinToMailingList',
            'label' => 'Hide Marketing Email Box',
            'tooltip' => "Choose whether you would like to show or hide the marketing email box.",
        ],
        'companyNameRequired' => [
            'type' => 'checkbox',
            'name' => 'companyNameRequired',
            'label' => 'Required "Company Name" Field',
            'tooltip' => 'Choose whether you would like to make "Company Name" field as required'
        ],
    ]
];