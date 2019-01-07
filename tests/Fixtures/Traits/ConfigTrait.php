<?php namespace Tests\Fixtures\Traits;

/**
 * Trait ConfigTrait
 * @package Tests\Fixtures\Traits
 */
trait ConfigTrait
{
    /**
     * @return array
     */
    public function getWorflowConfig()
    {
        return [
            // this should be your model name in camelcase. eg. PropertyListing::Class => propertyListing
            'user' => [
                // class of your domain object
                'class' => \Tests\Fixtures\Models\User::class,

                // Subscriber for this workflow which contains business rules
                'subscriber' => \Tests\Fixtures\Subscriber\UserEventSubscriber::class,

                // property of your object holding the actual state (default is "current_state")
                'property_path' => 'user_state',

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
                        'from' => 'new',
                        'to' => 'pending_activation',
                    ],
                    'activate' => [
                        'from' => 'pending_activation',
                        'to' =>  'activated',
                    ],
                    'block' => [
                        'from' => ['pending_activation', 'activated'],
                        'to' => 'blocked'
                    ],
                    'delete' => [
                        'from' => ['activated', 'blocked'],
                        'to' =>  'deleted',
                    ],
                ],
            ],
        ];
    }
}