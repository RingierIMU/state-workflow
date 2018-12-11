# Laravel State workflow
Implement [Symfony Workflow](https://symfony.com/doc/current/components/workflow.html) component in Laravel

A workflow consist of state and actions to get from one place to another.
The actions are called transitions which describes how to get from one state to another.
## Installation
```
$ composer require ringierimu/state-workflow 
```

For Laravel versions lower than 5.5, this step is important after running above script.
-   Open your config/app.php file and add custom service provider:
```php
Ringierimu\StateWorkflow\StateWorkflowServiceProvider::class
```
Publish `config/workflow.php` file
```php
$ php artisan vendor:publish --provider="Ringierimu\StateWorkflow\StateWorkflowServiceProvider"
```
Run migrations
```
$ php artisan migrate
```
## Configuration
1. Open `config/workflow.php` and configure it
```php
// this should be your model name in camelcase. eg. PropertyListing::Class => propertyListing
'post' => [
        // class of your domain object
        'class' => \App\Post::class,

        // Register subscriber for this workflow which contains business rules. Uncomment line below to register subscriber
        //'subscriber' => \App\Listeners\UserEventSubscriber::class,
        
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
use Ringierimu\StateWorkflow\Traits\HasWorkflowTrait;

/**
 * Class Post
 * @package App
 */
class Post extends Model
{
    use HasWorkflowTrait;
}
```

## Usage
### Manage State/Workflow
```php
<?php
use App\Post;

$post = new Post();

//Apply transition
$post->applyTransition("create");
$post = $post->refresh();

//Return current_state value
$post->state(); //pending_activation

//Check if this transition is allowed
$post->canTransition("activate"); // True

//Return Model state history
$post->stateHistory();
```
### Fired Event
Each step has three events that are fired in order:
 - An event for every workflow
 - An event for the workflow concerned
 - An event for the workflow concerned with the specific transition or state name  

During state/workflow transition, the following events are fired in the following order:
1. Validate whether the transition is allowed at all.
Their event listeners are invoked every time a call to 
`workflow()->can()`, `workflow()->apply()` or `workflow()->getEnabledTransitions()` is executed. `Guard Event`
```php
workflow.guard
workflow.[workflow name].guard
workflow.[workflow name].guard.[transition name]
```
2. The subject is about to leave a state. `Leave Event`
```php
workflow.leave
workflow.[workflow name].leave
workflow.[workflow name].leave.[state name]
```
3. The subject is going through this transition. `Transition Event`
```php
workflow.transition
workflow.[workflow name].transition
workflow.[workflow name].transition.[transition name]
```
4. The subject is about to enter a new state.
This event is triggered just before the subject states are updated. `Enter Event`
```php
workflow.enter
workflow.[workflow name].enter
workflow.[workflow name].enter.[state name]
```
5. The subject has entered in the states and is updated. `Entered Event`
```php
workflow.entered
workflow.[workflow name].entered
workflow.[workflow name].entered.[state name]
```
6. The subject has completed this transition. `Completed Event`
```php
workflow.completed
workflow.[workflow name].completed
workflow.[workflow name].completed.[transition name]
```
### Subscriber
Create subscriber class to listen to those events and the class should `extends WorkflowSubscriberHandler`.

To register method to listen to specific even within subscriber use the following format for method name:
- on[Event] - `onGuard()`
- on[Event][Transition/State name] - `onGuardActivate()`
    
NB:
- Method name must start with `on` key word otherwise it will be ignored.
- `Subscriber` class must be register inside `workflow.php` config file with the appropriate workflow configuration.
- `Subscriber` class must extends `WorkflowSubscriberHandler`.
- `Guard`, `Transition` and `Completed` Event uses of transition name.
- `Leave`, `Enter` and `Entered` Event uses state name.
```php
<?php namespace App\Listeners;

use Ringierimu\StateWorkflow\Events\EnteredEvent;
use Ringierimu\StateWorkflow\Events\EnterEvent;
use Ringierimu\StateWorkflow\Events\GuardEvent;
use Ringierimu\StateWorkflow\Events\LeaveEvent;
use Ringierimu\StateWorkflow\Events\TransitionEvent;
use Ringierimu\StateWorkflow\Subscribers\WorkflowSubscriberHandler;

/**
 * Class PostEventSubscriber
 * @package App\Listeners
 */
class UserEventSubscriber extends WorkflowSubscriberHandler
{
    /**
     * Handle workflow guard events.
     * 
     * @param GuardEvent $event
     */
    public function onGuardActivate($event)
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
    public function onLeavePendingActivation($event)
    {
    }
    
    /**
     * Handle workflow transition event.
     * 
     * @param TransitionEvent $event
     */
    public function onTransitionActivate($event)
    {
    }
    
    /**
     * Handle workflow enter event.
     * 
     * @param EnterEvent $event
     */
    public function onEnterActivated($event)
    {
    }

    /**
     * Handle workflow entered event.
     * 
     * @param EnteredEvent $event
     */
    public function onEnteredActivated($event)
    {
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
