<?php

use Illuminate\Support\Facades\Event;

test('workflow subscriber emits events on transition', function () {
    Event::fake();

    $this->user->applyTransition('create');

    // Guard events (3: generic, workflow-specific, transition-specific)
    Event::assertDispatched('workflow.guard');
    Event::assertDispatched('workflow.user.guard');
    Event::assertDispatched('workflow.user.guard.create');

    // Leave events (3: generic, workflow-specific, state-specific)
    Event::assertDispatched('workflow.leave');
    Event::assertDispatched('workflow.user.leave');
    Event::assertDispatched('workflow.user.leave.new');

    // Transition events
    Event::assertDispatched('workflow.transition');
    Event::assertDispatched('workflow.user.transition');
    Event::assertDispatched('workflow.user.transition.create');

    // Enter events
    Event::assertDispatched('workflow.enter');
    Event::assertDispatched('workflow.user.enter');
    Event::assertDispatched('workflow.user.enter.pending_activation');

    // Entered events
    Event::assertDispatched('workflow.entered');
    Event::assertDispatched('workflow.user.entered');
    Event::assertDispatched('workflow.user.entered.pending_activation');

    // Completed events
    Event::assertDispatched('workflow.completed');
    Event::assertDispatched('workflow.user.completed');
    Event::assertDispatched('workflow.user.completed.create');

    // Guard checks for available transitions after state change
    Event::assertDispatched('workflow.user.guard.activate');
    Event::assertDispatched('workflow.user.guard.block');
});
