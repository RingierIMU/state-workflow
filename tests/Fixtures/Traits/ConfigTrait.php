<?php

namespace Ringierimu\StateWorkflow\Tests\Fixtures\Traits;

use Ringierimu\StateWorkflow\Tests\Fixtures\Models\User;
use Ringierimu\StateWorkflow\Tests\Fixtures\Subscriber\UserEventSubscriber;

/**
 * Trait ConfigTrait.
 */
trait ConfigTrait
{
    public function getWorflowConfig(): array
    {
        return [
            // this should be your model name in camelcase. eg. PropertyListing::Class => propertyListing
            'user' => [
                // class of your domain object
                'class' => User::class,

                // Subscriber for this workflow which contains business rules
                'subscriber' => UserEventSubscriber::class,

                // property of your object holding the actual state (default is "current_state")
                'property_path' => 'user_state',

                // list of all possible states
                'states' => [
                    'new',
                    'pending_activation',
                    'activated',
                    'deleted',
                    'blocked',
                ],

                // list of all possible transitions
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
                        'from' => ['activated', 'blocked'],
                        'to' => 'deleted',
                    ],
                ],
            ],
        ];
    }

    /**
     * Return config with both primary and subscription workflows for multi-workflow testing.
     */
    public function getMultiWorflowConfig(): array
    {
        return array_merge($this->getWorflowConfig(), [
            'user_subscription' => [
                'class' => User::class,
                'property_path' => 'subscription_state',
                'states' => [
                    'inactive',
                    'active',
                    'cancelled',
                ],
                'transitions' => [
                    'subscribe' => [
                        'from' => 'inactive',
                        'to' => 'active',
                    ],
                    'cancel' => [
                        'from' => 'active',
                        'to' => 'cancelled',
                    ],
                ],
            ],
        ]);
    }
}
