<?php

return [
    'user' => [
        // class of your domain object
        'class' => [App\User::class],

        // property of your object holding the actual state (default is "state")
        'property_path' => 'state',

        // list of all possible states
        'states' => [
            'new',
            'pending_review',
            'awaiting_changes',
            'accepted',
            'published',
            'rejected',
        ],

        // list of all possible transitions
        'transitions' => [
            'create' => [
                'from' => ['new'],
                'to' => 'pending_review',
            ],
            'ask_for_changes' => [
                'from' =>  ['pending_review', 'accepted'],
                'to' => 'awaiting_changes',
            ],
            'cancel_changes' => [
                'from' => ['awaiting_changes'],
                'to' => 'pending_review',
            ],
            'submit_changes' => [
                'from' => ['awaiting_changes'],
                'to' =>  'pending_review',
            ],
            'approve' => [
                'from' => ['pending_review', 'rejected'],
                'to' =>  'accepted',
            ],
            'publish' => [
                'from' => ['accepted'],
                'to' =>  'published',
            ],
        ],
    ],
];
