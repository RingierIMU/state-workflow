<?php

namespace Ringierimu\StateWorkflow\Tests\Unit;

use Ringierimu\StateWorkflow\Tests\TestCase;
use Ringierimu\StateWorkflow\Workflow\StateWorkflow;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;

/**
 * Class UserTest.
 */
class UserUnitTest extends TestCase
{
    public function test_it_return_workflow_instance()
    {
        $this->assertInstanceOf(StateWorkflow::class, $this->user->workflow());
    }

    public function test_is_current_state_new()
    {
        $this->assertEquals('new', $this->user->state());
    }

    public function test_can_apply_transition()
    {
        $this->assertTrue($this->user->canTransition('create'));
        $this->assertFalse($this->user->canTransition('block'));
    }

    public function test_invalid_transition_throws_exception()
    {
        $this->expectException(NotEnabledTransitionException::class);

        $this->user->applyTransition('block');
    }

    public function test_apply_transitions()
    {
        $this->user->applyTransition('create');
        $this->user = $this->user->refresh();
        $this->assertEquals('pending_activation', $this->user->state());
        $this->assertEquals(1, $this->user->stateHistory()->count());

        $this->user->applyTransition('activate');
        $this->user = $this->user->refresh();
        $this->assertEquals('activated', $this->user->state());
        $this->assertEquals(2, $this->user->stateHistory()->count());

        $this->user->applyTransition('block');
        $this->user = $this->user->refresh();
        $this->assertEquals('blocked', $this->user->state());
        $this->assertEquals(3, $this->user->stateHistory()->count());

        $this->user->applyTransition('delete');
        $this->user = $this->user->refresh();
        $this->assertEquals('deleted', $this->user->state());
        $this->assertEquals(4, $this->user->stateHistory()->count());
    }
}
