<?php

use Illuminate\Support\Facades\Event;

test('event lifecycle follows correct ordering: guard before leave before transition before enter before entered before completed', function () {
    $eventOrder = [];

    Event::listen('workflow.guard', function () use (&$eventOrder) {
        $eventOrder[] = 'guard';
    });
    Event::listen('workflow.leave', function () use (&$eventOrder) {
        $eventOrder[] = 'leave';
    });
    Event::listen('workflow.transition', function () use (&$eventOrder) {
        $eventOrder[] = 'transition';
    });
    Event::listen('workflow.enter', function () use (&$eventOrder) {
        $eventOrder[] = 'enter';
    });
    Event::listen('workflow.entered', function () use (&$eventOrder) {
        $eventOrder[] = 'entered';
    });
    Event::listen('workflow.completed', function () use (&$eventOrder) {
        $eventOrder[] = 'completed';
    });

    $this->user->applyTransition('create');

    // Verify milestone ordering (not exact sequence of all events)
    $guardIndex = array_search('guard', $eventOrder);
    $leaveIndex = array_search('leave', $eventOrder);
    $transitionIndex = array_search('transition', $eventOrder);
    $enterIndex = array_search('enter', $eventOrder);
    $enteredIndex = array_search('entered', $eventOrder);
    $completedIndex = array_search('completed', $eventOrder);

    expect($guardIndex)->toBeLessThan($leaveIndex);
    expect($leaveIndex)->toBeLessThan($transitionIndex);
    expect($transitionIndex)->toBeLessThan($enterIndex);
    expect($enterIndex)->toBeLessThan($enteredIndex);
    expect($enteredIndex)->toBeLessThan($completedIndex);
});

test('all six lifecycle phases fire during a transition', function () {
    $phases = [];

    Event::listen('workflow.guard', function () use (&$phases) {
        $phases[] = 'guard';
    });
    Event::listen('workflow.leave', function () use (&$phases) {
        $phases[] = 'leave';
    });
    Event::listen('workflow.transition', function () use (&$phases) {
        $phases[] = 'transition';
    });
    Event::listen('workflow.enter', function () use (&$phases) {
        $phases[] = 'enter';
    });
    Event::listen('workflow.entered', function () use (&$phases) {
        $phases[] = 'entered';
    });
    Event::listen('workflow.completed', function () use (&$phases) {
        $phases[] = 'completed';
    });

    $this->user->applyTransition('create');

    expect($phases)->toContain('guard');
    expect($phases)->toContain('leave');
    expect($phases)->toContain('transition');
    expect($phases)->toContain('enter');
    expect($phases)->toContain('entered');
    expect($phases)->toContain('completed');
});
