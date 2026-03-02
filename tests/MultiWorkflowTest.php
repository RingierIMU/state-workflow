<?php

use Ringierimu\StateWorkflow\WorkflowRegistry;

test('two workflows can be registered on the same model', function () {
    $config = $this->getMultiWorflowConfig();
    app()['config']->set('workflow', $config);

    $registry = new WorkflowRegistry($config);

    $primaryWorkflow = $registry->get($this->user, 'user');
    $subscriptionUser = $this->user;
    $subscriptionUser->subscription_state = 'inactive';
    $subscriptionUser->save();
    $subscriptionWorkflow = $registry->get($subscriptionUser, 'user_subscription');

    expect($primaryWorkflow)->not->toBeNull();
    expect($subscriptionWorkflow)->not->toBeNull();
});

test('primary workflow transitions independently of subscription workflow', function () {
    $config = $this->getMultiWorflowConfig();
    app()['config']->set('workflow', $config);

    // Set initial subscription state
    $this->user->subscription_state = 'inactive';
    $this->user->save();

    $registry = new WorkflowRegistry($config);

    // Apply primary workflow transition
    $primaryWorkflow = $registry->get($this->user, 'user');
    $primaryWorkflow->apply($this->user, 'create');
    $this->user = $this->user->refresh();

    expect($this->user->state())->toBe('pending_activation');
    expect($this->user->subscription_state)->toBe('inactive');
});

test('subscription workflow transitions independently of primary workflow', function () {
    $config = $this->getMultiWorflowConfig();
    app()['config']->set('workflow', $config);

    // Set initial subscription state
    $this->user->subscription_state = 'inactive';
    $this->user->save();

    $registry = new WorkflowRegistry($config);

    // Apply subscription workflow transition
    $subscriptionWorkflow = $registry->get($this->user, 'user_subscription');
    $subscriptionWorkflow->apply($this->user, 'subscribe');
    $this->user = $this->user->refresh();

    // Primary state unchanged
    expect($this->user->state())->toBe('new');
    // Subscription state transitioned
    expect($this->user->subscription_state)->toBe('active');
});

test('both workflows can transition in sequence on same model', function () {
    $config = $this->getMultiWorflowConfig();
    app()['config']->set('workflow', $config);

    // Set initial subscription state
    $this->user->subscription_state = 'inactive';
    $this->user->save();

    $registry = new WorkflowRegistry($config);

    // Transition primary workflow
    $primaryWorkflow = $registry->get($this->user, 'user');
    $primaryWorkflow->apply($this->user, 'create');
    $this->user = $this->user->refresh();

    // Transition subscription workflow
    $subscriptionWorkflow = $registry->get($this->user, 'user_subscription');
    $subscriptionWorkflow->apply($this->user, 'subscribe');
    $this->user = $this->user->refresh();

    expect($this->user->state())->toBe('pending_activation');
    expect($this->user->subscription_state)->toBe('active');
});
