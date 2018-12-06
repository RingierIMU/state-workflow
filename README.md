# Laravel State workflow

## Installation
```
$ composer require RingierInternationalMarketplaceUnit/state-workflow 
```

For Laravel versions lower than 5.5, this step is important after running above script.
-   Open your config/app.php file and add custom service provider:
```php
RingierInternationalMarketplaceUnit\StateWorkflow\StateWorkflowServiceProvider::class
```
Publish `config/workflow.php` file
```php
$ php artisan vendor:publish --provider="RingierInternationalMarketplaceUnit\StateWorkflow\StateWorkflowServiceProvider"
```
Run migrations
```
$ php artisan migrate
```
## Configuration
1. Open `config/workflow.php` and configure it
```php
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
```
2. Add `HasWorkflowTrait` to your model class to support workflow
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
1. Validate whether the transition is allowed at all.
Their event listeners are invoked every time a call to `workflow()->can()`, `workflow()->apply()` or `workflow()->getEnabledTransitions()` is executed.
```php
RingierInternationalMarketplaceUnit\StateWorkflow\Events\GuardEvent
workflow.[workflow name].guard
workflow.[workflow name].guard.[transition name]
```
2. The subject is about to leave a state
```php
RingierInternationalMarketplaceUnit\StateWorkflow\Events\LeaveEvent
workflow.[workflow name].leave
workflow.[workflow name].leave.[state name]
```
3. The subject is going through this transition
```php
RingierInternationalMarketplaceUnit\StateWorkflow\Events\TransitionEvent
workflow.[workflow name].transition
workflow.[workflow name].transition.[transition name]
```
4. The subject is about to enter a new state. This event is triggered just before the subject states are updated.
```php
RingierInternationalMarketplaceUnit\StateWorkflow\Events\EnterEvent
workflow.[workflow name].enter
workflow.[workflow name].enter.[state name]
```
5. The subject has entered in the states and is updated 
```php
RingierInternationalMarketplaceUnit\StateWorkflow\Events\EnteredEvent
workflow.[workflow name].entered
workflow.[workflow name].entered.[state name]
```
6. The subject has completed this transition.
```php
RingierInternationalMarketplaceUnit\StateWorkflow\Events\CompletedEvent
workflow.[workflow name].completed
workflow.[workflow name].completed.[transition name]
```
## Subscriber
Open `Providers/EventServiceProvider.php` and register your subscribers
```php
<?php namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\UserEventSubscriber;

/**
 * Class EventServiceProvider
 * @package App\Providers
 */
class EventServiceProvider extends ServiceProvider
{
    
    /**
     * The subscriber classes to register.
     *  
     * @var array 
     */
    protected $subscribe = [
        UserEventSubscriber::class,
    ];
}

``` 
Create subscriber class to listen to those events and the class should `implements WorkflowEventSubscriberInterface`
```php
<?php namespace App\Listeners;

use RingierInternationalMarketplaceUnit\StateWorkflow\Events\EnteredEvent;
use RingierInternationalMarketplaceUnit\StateWorkflow\Events\EnterEvent;
use RingierInternationalMarketplaceUnit\StateWorkflow\Events\GuardEvent;
use RingierInternationalMarketplaceUnit\StateWorkflow\Events\LeaveEvent;
use RingierInternationalMarketplaceUnit\StateWorkflow\Events\TransitionEvent;
use RingierInternationalMarketplaceUnit\StateWorkflow\Interfaces\WorkflowEventSubscriberInterface;
use Psr\Log\LoggerInterface;

/**
 * Class PostEventSubscriber
 * @package App\Listeners
 */
class UserEventSubscriber implements WorkflowEventSubscriberInterface
{
    private $logger;
    
    /**
     * UserEventSubscriber constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger) 
    {
        $this->logger = $logger;
    }
    /**
     * Handle workflow guard events.
     * 
     * @param GuardEvent $event
     */
    public function guardActivate($event)
    {
        $user = $event->getOriginalEvent()->getSubject();

        if (empty($user->dob)) {
            // Users with no dob should not be allowed
            $event->getOriginalEvent()->setBlocked(true);
        }
    }
    
    /**
     * Handle workflow leave event.
     * 
     * @param LeaveEvent $event
     */
    public function leavePendingActivation($event)
    {
        $this->logger->info(__METHOD__);
    }
    
    /**
     * Handle workflow transition event.
     * 
     * @param TransitionEvent $event
     */
    public function transitionActivate($event)
    {
        $this->logger->info(__METHOD__);
    }
    
    /**
     * Handle workflow enter event.
     * 
     * @param EnterEvent $event
     */
    public function enterActivated($event)
    {
        $this->logger->info(__METHOD__);
    }

    /**
     * Handle workflow entered event.
     * 
     * @param EnteredEvent $event
     */
    public function enteredActivated($event)
    {
        $this->logger->info(__METHOD__);
    }
    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $event
     */
    public function subscribe($event)
    {
        $event->listen(
            "workflow.user.guard.activate",
            "App\Listeners\UserEventSubscriber@guardActivate"
        );
        
        $event->listen(
            "workflow.user.leave.pending_activation",
            "App\Listeners\UserEventSubscriber@leavePendingActivation"
        );
        
        $event->listen(
            "workflow.user.transition.activate",
            "App\Listeners\UserEventSubscriber@transitionActivate"
        );
        
        $event->listen(
            "workflow.user.enter.activated",
            "App\Listeners\UserEventSubscriber@enterActivated"
        );
        
        $event->listen(
            "workflow.user.entered.activated",
            "App\Listeners\UserEventSubscriber@enteredActivated"
        );
    }
}
```

## Event Methods
Each workflow event has an instance of `Event`. This means that each event has access to the following information:
- `getOriginalEvent()`: Returns the Parent Event that dispatched the event which has the following children methods:
    - `getSubject()`: Returns the object that dispatches the event.
    - `getTransition()`: Returns the Transition that dispatches the event.
    - `getWorkflowName()`: Returns a string with the name of the workflow that triggered the event.
    - `isBlocked()`: Returns true/false if transition is blocked.
    - `setBlocked()`: Sets the blocked value.
