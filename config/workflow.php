<?php

return [
    // this should be your model name in camelcase. eg. PropertyListing::Class => propertyListing
    'user' => [
        // class of your domain object
        'class' => App\User::class,

        // property of your object holding the actual state (default is "current_state")
        //'property_path' => 'current_state', //uncomment this line to override default value

        // list of all possible states
        'states' => [
            'new',
            'pending_activation',
            'activated',
            'deleted',
            'blocked'
        ],

        // list of all possible transitions
        'transitions' => [
            'create' => [
                'from' => ['new'],
                'to' => 'pending_activation',
            ],
            'activate' => [
                'from' => ['pending_activation'],
                'to' =>  'activated',
            ],
            'block' => [
                'from' => ['pending_activation', 'activated'],
                'to' => 'blocked'
            ],
            'delete' => [
                'from' => ['pending_activation', 'activated', 'blocked'],
                'to' =>  'deleted',
            ],
        ],
    ],
];
