<?php

return [
    'display_name' => 'Default',
    'preview'      => 'thumb.png',
    'variables' => [

    ],
    'settings'     => [
        'addonsColumns' => [
            'type' => 'select',
            'name' => 'addonsColumns',
            'label' => 'Addons Columns',
            'options' => '1,2',
            'default' => '2',
            'tooltip' => "Select amount of columns for addon boxes"    
        ],
        'removeProductGroupName' => [
            'type' => 'checkbox',
            'name' => 'removeProductGroupName',
            'label' => 'Hide Product Group Name From Order Summary',
            'tooltip' => "Upon activation, the product group name will be omitted from the product name in the order summary sidebar.",
        ],
    ]
];