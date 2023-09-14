<?php

namespace Ringierimu\StateWorkflow\Tests {
    use Ringierimu\StateWorkflow\WorkflowRegistry;

    /**
     * Class WorkflowSubscriberTest.
     */
    class WorkflowSubscriberTest extends TestCase
    {
        /**
         * @test
         *
         * @throws \ReflectionException
         */
        public function if_workflow_subscriber_emit_events()
        {
            global $events;
            $events = [];

            $workflowRegistry = new WorkflowRegistry($this->getWorflowConfig());
            $workflow = $workflowRegistry->get($this->user);

            $workflow->apply($this->user, 'create');
            $this->assertCount(24, $events);

            $this->assertEquals('workflow.guard', $events[0]);
            $this->assertEquals('workflow.user.guard', $events[1]);
            $this->assertEquals('workflow.user.guard.create', $events[2]);

            $this->assertEquals('workflow.leave', $events[3]);
            $this->assertEquals('workflow.user.leave', $events[4]);
            $this->assertEquals('workflow.user.leave.new', $events[5]);

            $this->assertEquals('workflow.transition', $events[6]);
            $this->assertEquals('workflow.user.transition', $events[7]);
            $this->assertEquals('workflow.user.transition.create', $events[8]);

            $this->assertEquals('workflow.enter', $events[9]);
            $this->assertEquals('workflow.user.enter', $events[10]);
            $this->assertEquals('workflow.user.enter.pending_activation', $events[11]);

            $this->assertEquals('workflow.entered', $events[12]);
            $this->assertEquals('workflow.user.entered', $events[13]);
            $this->assertEquals('workflow.user.entered.pending_activation', $events[14]);

            $this->assertEquals('workflow.completed', $events[15]);
            $this->assertEquals('workflow.user.completed', $events[16]);
            $this->assertEquals('workflow.user.completed.create', $events[17]);

            //Announce model next available transitions events
            $this->assertEquals('workflow.guard', $events[18]);
            $this->assertEquals('workflow.user.guard', $events[19]);
            $this->assertEquals('workflow.user.guard.activate', $events[20]);

            $this->assertEquals('workflow.guard', $events[21]);
            $this->assertEquals('workflow.user.guard', $events[22]);
            $this->assertEquals('workflow.user.guard.block', $events[23]);
        }
    }
}

namespace {
    $events = null;

    if (!function_exists('event')) {
        function event($ev)
        {
            global $events;
            $events[] = $ev;
        }
    }
}
