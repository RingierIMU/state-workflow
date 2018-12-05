# Laravel State workflow

## Installation
```
$ composer require RingierInternationalMarketplaceUnit/state-workflow 
```

For Laravel versions lower than 5.5, this step is important after running above script.
-   Open your config/app.php file and add custom service provider:
```
RingierInternationalMarketplaceUnit\StateWorkflow\StateWorkflowServiceProvider::class
```
Publish config/workflow.php
```
$ php artisan vendor:publish --provider="RingierInternationalMarketplaceUnit\StateWorkflow\StateWorkflowServiceProvider"
```

## Usage
1. Open config/workflow.php
```
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
2. Replace sample values
3. Open your the Model class that you want to enable workflow and add the following trait
```
use HasWorkflowTrait;
```
