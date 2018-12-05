# Laravel State workflow

## Installation
```php
$ composer require RingierInternationalMarketplaceUnit/state-workflow 
```

For Laravel versions lower than 5.5, this step is important after running above script.
-   Open your config/app.php file and add custom service provider:
```php
RingierInternationalMarketplaceUnit\StateWorkflow\StateWorkflowServiceProvider::class
```
Publish config/workflow.php file
```php
$ php artisan vendor:publish --provider="RingierInternationalMarketplaceUnit\StateWorkflow\StateWorkflowServiceProvider"
```

## Configuration
1. Open config/workflow.php and configure it
```php
'user' => [
        // class of your domain object
        'class' => App\User::class,

        // property of your object holding the actual state (default is "current_state")
        'property_path' => 'current_state',

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
```
2. Add `HasWorkflowTrait` to your model class
```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use RingierInternationalMarketplaceUnit\StateWorkflow\Traits\HasWorkflowTrait;

class Post extends Model
{
    use HasWorkflowTrait;
}
```

## Usage

## Fired Event
During state/workflow transition, the following events are fired:
```php
RingierInternationalMarketplaceUnit\StateWorkflow\Events\CompletedEvent
RingierInternationalMarketplaceUnit\StateWorkflow\Events\EnteredEvent
RingierInternationalMarketplaceUnit\StateWorkflow\Events\EnterEvent
RingierInternationalMarketplaceUnit\StateWorkflow\Events\GuardEvent
RingierInternationalMarketplaceUnit\StateWorkflow\Events\LeaveEvent
RingierInternationalMarketplaceUnit\StateWorkflow\Events\TransitionEvent
```

## Subscriber
