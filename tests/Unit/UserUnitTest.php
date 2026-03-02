<?php

use Ringierimu\StateWorkflow\Workflow\StateWorkflow;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;

test('it returns workflow instance', function () {
    expect($this->user->workflow())->toBeInstanceOf(StateWorkflow::class);
});

test('current state is new', function () {
    expect($this->user->state())->toBe('new');
});

test('can apply transition', function () {
    expect($this->user->canTransition('create'))->toBeTrue();
    expect($this->user->canTransition('block'))->toBeFalse();
});

test('invalid transition throws exception', function () {
    $this->user->applyTransition('block');
})->throws(NotEnabledTransitionException::class);

test('apply transitions through full lifecycle', function () {
    $this->user->applyTransition('create');
    $this->user = $this->user->refresh();
    expect($this->user->state())->toBe('pending_activation');
    expect($this->user->stateHistory()->count())->toBe(1);

    $this->user->applyTransition('activate');
    $this->user = $this->user->refresh();
    expect($this->user->state())->toBe('activated');
    expect($this->user->stateHistory()->count())->toBe(2);

    $this->user->applyTransition('block');
    $this->user = $this->user->refresh();
    expect($this->user->state())->toBe('blocked');
    expect($this->user->stateHistory()->count())->toBe(3);

    $this->user->applyTransition('delete');
    $this->user = $this->user->refresh();
    expect($this->user->state())->toBe('deleted');
    expect($this->user->stateHistory()->count())->toBe(4);
});
