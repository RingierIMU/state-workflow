<?php

namespace Tests {


    use RingierInternationalMarketplaceUnit\StateWorkflow\Events\CompletedEvent;
    use RingierInternationalMarketplaceUnit\StateWorkflow\Events\EnteredEvent;
    use RingierInternationalMarketplaceUnit\StateWorkflow\Events\EnterEvent;
    use RingierInternationalMarketplaceUnit\StateWorkflow\Events\GuardEvent;
    use RingierInternationalMarketplaceUnit\StateWorkflow\Events\LeaveEvent;
    use RingierInternationalMarketplaceUnit\StateWorkflow\Events\TransitionEvent;
    use RingierInternationalMarketplaceUnit\StateWorkflow\WorkflowRegistry;
    use Tests\Fixtures\SampleModel;

    /**
     * Class WorkflowSubscriberTest
     * @package Tests
     */
    class WorkflowSubscriberTest extends \PHPUnit\Framework\TestCase
    {

        /**
         * @test
         * @throws \ReflectionException
         */
        public function if_workflow_subscriber_emit_events()
        {
            global $events;
            $events = [];

            $workflowRegistry = new WorkflowRegistry($this->getConfig());
            $model = new SampleModel();
            $workflow = $workflowRegistry->get($model);

            $workflow->apply($model, 'create');
            $this->assertCount(21, $events);

            $this->assertInstanceOf(GuardEvent::class, $events[0]);
            $this->assertEquals('workflow.sampleModel.guard', $events[1]);
            $this->assertEquals('workflow.sampleModel.guard.create', $events[2]);

            $this->assertInstanceOf(LeaveEvent::class, $events[3]);
            $this->assertEquals('workflow.sampleModel.leave', $events[4]);
            $this->assertEquals('workflow.sampleModel.leave.new', $events[5]);

            $this->assertInstanceOf(TransitionEvent::class, $events[6]);
            $this->assertEquals('workflow.sampleModel.transition', $events[7]);
            $this->assertEquals('workflow.sampleModel.transition.create', $events[8]);

            $this->assertInstanceOf(EnterEvent::class, $events[9]);
            $this->assertEquals('workflow.sampleModel.enter', $events[10]);
            $this->assertEquals('workflow.sampleModel.enter.pending_activation', $events[11]);

            $this->assertInstanceOf(EnteredEvent::class, $events[12]);
            $this->assertEquals('workflow.sampleModel.entered', $events[13]);
            $this->assertEquals('workflow.sampleModel.entered.pending_activation', $events[14]);

            $this->assertInstanceOf(CompletedEvent::class, $events[15]);
            $this->assertEquals('workflow.sampleModel.completed', $events[16]);
            $this->assertEquals('workflow.sampleModel.completed.create', $events[17]);

            $this->assertInstanceOf(GuardEvent::class, $events[18]);
            $this->assertEquals('workflow.sampleModel.guard', $events[19]);
            $this->assertEquals('workflow.sampleModel.guard.activate', $events[20]);
        }

        /**
         * @return array
         */
        private function getConfig()
        {
            return [
                // this should be your model name in camelcase. eg. PropertyListing::Class => propertyListing
                'sampleModel' => [
                    // class of your domain object
                    'class' => \Tests\Fixtures\SampleModel::class,

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
                            'to' => 'activated',
                        ],
                    ],
                ],
            ];

        }
    }
}

namespace {

    $events = null;

    function event($ev)
    {
        global $events;
        $events[] = $ev;
    }
}
