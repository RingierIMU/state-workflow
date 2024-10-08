<?php

return [
    'setup' => [
        /*
        |--------------------------------------------------------------------------
        | User Providers
        |--------------------------------------------------------------------------
        |
        | This define Authentication user is model of your application.
        | Ideally it should match your `providers.users.model` found in `config/auth.php`
        | to leverage the default Laravel auth resolver
        |
        */
        'user_class' => \App\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain entity
    |--------------------------------------------------------------------------
    |
    | This should be your model name in camelCase.
    |
    | eg. UserProfile::Class => userProfile
    |
    | Attributes definition
    |
    | subscriber:
    | Register subscriber for this workflow which contains business rules.
    |
    | property_path:
    | Attribute on your domain entity holding the actual state (default is "current_state")
    |
    | states:
    | Define all possible state your domain entity can transition to
    |
    | transitions:
    | Define all allowed transitions to transit from one state to another
    */
    'user' => [
        // class of your domain object
        'class' => \App\User::class,

        'subscriber' => \App\Listeners\UserEventSubscriber::class,

        // Uncomment line below to override default attribute
        // 'property_path' => 'current_state',

        'states' => [
            'new',
            'pending_activation',
            'activated',
            'deleted',
            'blocked',
        ],

        'transitions' => [
            'create' => [
                'from' => 'new',
                'to' => 'pending_activation',
            ],
            'activate' => [
                'from' => 'pending_activation',
                'to' => 'activated',
            ],
            'block' => [
                'from' => ['pending_activation', 'activated'],
                'to' => 'blocked',
            ],
            'delete' => [
                'from' => ['pending_activation', 'activated', 'blocked'],
                'to' => 'deleted',
            ],
        ],
    ],
];
