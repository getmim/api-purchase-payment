<?php

return [
    '__name' => 'api-purchase-payment',
    '__version' => '0.0.1',
    '__git' => 'git@github.com:getmim/api-purchase-payment.git',
    '__license' => 'MIT',
    '__author' => [
        'name' => 'Iqbal Fauzi',
        'email' => 'iqbalfawz@gmail.com',
        'website' => 'http://iqbalfn.com/'
    ],
    '__files' => [
        'modules/api-purchase-payment' => ['install','update','remove']
    ],
    '__dependencies' => [
        'required' => [
            [
                'purchase-payment' => NULL
            ],
            [
                'api' => NULL
            ]
        ],
        'optional' => []
    ],
    'autoload' => [
        'classes' => [
            'ApiPurchasePayment\\Controller' => [
                'type' => 'file',
                'base' => 'modules/api-purchase-payment/controller'
            ]
        ],
        'files' => []
    ],
    'routes' => [
        'api' => [
            'apiPurchasePayment' => [
                'path' => [
                    'value' => '/purchase/(:id)/payment'
                ],
                'method' => 'GET',
                'handler' => 'ApiPurchasePayment\\Controller\\Payment::single'
            ],
            'apiPurchasePaymentCreate' => [
                'path' => [
                    'value' => '/purchase/(:id)/payment'
                ],
                'method' => 'POST',
                'handler' => 'ApiPurchasePayment\\Controller\\Payment::create'
            ],
            'apiPurchasePaymentInstruction' => [
                'path' => [
                    'value' => '/purchase/(:id)/payment/instruction'
                ],
                'method' => 'GET',
                'handler' => 'ApiPurchasePayment\\Controller\\Payment::instruction'
            ],
            'apiPurchasePaymentMethod' => [
                'path' => [
                    'value' => '/purchase/(:id)/payment/method'
                ],
                'method' => 'GET',
                'handler' => 'ApiPurchasePayment\\Controller\\Payment::method'
            ]
        ]
    ],
    'libForm' => [
        'forms' => [
            'api-purchase-payment.create' => [
                'id' => [
                    'rules' => [
                        'required' => true
                    ]
                ],
                'handler' => [
                    'rules' => [
                        'required' => true
                    ]
                ]
            ]
        ]
    ]
];
