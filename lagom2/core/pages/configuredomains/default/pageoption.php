<?php

return [
    'display_name' => 'Default',
    'preview'      => 'thumb.png',
    'settings'     => [
        'displayProminentHosting' => [
            'type' => 'checkbox',
            'name' => 'displayProminentHosting',
            'label' => 'Display "No Hosting" information more prominent',
            'tooltip' => 'Choose wheter you would like to display "No Hosting" information more prominent on this page.'    
        ],
        'hideNameserversSection' => [
            'type' => 'checkbox',
            'name' => 'hideNameserversSection',
            'label' => 'Hide "Nameservers" section',
            'tooltip' => 'Choose to hide the "Nameservers" field during domain purchase to provide a more straightforward checkout flow.'
        ],
    ]
];