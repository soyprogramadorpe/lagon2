<?php

return [
    'display_name' => 'Sidebar Login',
    'preview'      => 'thumb.png',
    'variables' => [
        'skipMainHeader' => true,
        'skipMainFooter' => true,
        'skipMainTop' => true,
        'noSocials' => true,
        'isFullPage' => true
    ],
    'settings'     => [
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
        // 'sidebarOnRightSide'=> [
        //     'type'  => 'checkbox',
        //     'name'  => 'sidebarOnRightSide',
        //     'label' => 'Sidebar on right side of the page',
        //     'tooltip' => 'Sample'    
        // ],
    ]
];