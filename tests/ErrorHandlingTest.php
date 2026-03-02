<?php

use Illuminate\Support\Facades\Event;

test('exception in guard subscriber handler propagates to caller', function () {
    Event::listen('workflow.guard', function () {
        throw new \RuntimeException('Subscriber error');
    });

    $this->user->applyTransition('create');
})->throws(\RuntimeException::class, 'Subscriber error');

test('transition does not silently succeed when subscriber throws', function () {
    Event::listen('workflow.transition', function () {
        throw new \RuntimeException('Transition handler failed');
    });

    $caught = false;

    try {
        $this->user->applyTransition('create');
    } catch (\RuntimeException $e) {
        $caught = true;
        expect($e->getMessage())->toBe('Transition handler failed');
    }

    expect($caught)->toBeTrue();

    // Model state may have partially changed depending on where in lifecycle
    // the exception fires, but the exception DID propagate
    $this->user = $this->user->refresh();
    expect($this->user)->not->toBeNull();
});

test('exception in entered event handler propagates', function () {
    Event::listen('workflow.entered', function () {
        throw new \RuntimeException('Entered handler error');
    });

    $this->user->applyTransition('create');
})->throws(\RuntimeException::class, 'Entered handler error');
