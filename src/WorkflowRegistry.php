<?php

namespace Ringierimu\StateWorkflow;

use Exception;
use Illuminate\Support\Facades\Event;
use ReflectionClass;
use Ringierimu\StateWorkflow\Interfaces\WorkflowEventSubscriberInterface;
use Ringierimu\StateWorkflow\Interfaces\WorkflowRegistryInterface;
use Ringierimu\StateWorkflow\Subscribers\WorkflowSubscriber;
use Ringierimu\StateWorkflow\Workflow\MethodMarkingStore;
use Ringierimu\StateWorkflow\Workflow\StateWorkflow;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;

/**
 * Class WorkflowRegistry.
 */
class WorkflowRegistry implements WorkflowRegistryInterface
{
    protected \Symfony\Component\Workflow\Registry $registry;

    protected \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher;

    /**
     * WorkflowRegistry constructor.
     *
     *
     * @throws \ReflectionException
     */
    public function __construct(protected array $config)
    {
        $this->registry = new Registry();
        $this->dispatcher = new EventDispatcher();

        $subscriber = new WorkflowSubscriber();
        $this->dispatcher->addSubscriber($subscriber);

        foreach ($this->config as $name => $workflowData) {
            $this->addWorkflows($name, $workflowData);
            if (array_key_exists('subscriber', $workflowData)) {
                $this->addSubscriber($workflowData['subscriber'], $name);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($subject, $workflowName = null): \Symfony\Component\Workflow\WorkflowInterface
    {
        return $this->registry->get($subject, $workflowName);
    }

    /**
     * Add a workflow to the subject.
     */
    public function registerWorkflow(StateWorkflow $workflow, string $className): void
    {
        $this->registry->addWorkflow($workflow, new InstanceOfSupportStrategy($className));
    }

    /**
     * Add a workflow to the registry from array.
     *
     * @param       $name
     *
     * @throws \ReflectionException
     */
    public function addWorkflows($name, array $workflowData): void
    {
        $definitionBuilder = new DefinitionBuilder($workflowData['states']);

        foreach ($workflowData['transitions'] as $transitionName => $transition) {
            if (!is_string($transitionName)) {
                $transitionName = $transition['name'];
            }

            foreach ((array) $transition['from'] as $form) {
                $definitionBuilder->addTransition(new Transition($transitionName, $form, $transition['to']));
            }
        }

        $definition = $definitionBuilder->build();
        $markingStore = $this->getMarkingStoreInstance($workflowData);
        $workflow = $this->getWorkflowInstance($name, $workflowData, $definition, $markingStore);

        $this->registerWorkflow($workflow, $workflowData['class']);
    }

    /**
     * Return the workflow instance.
     *
     * @param                       $name
     *
     * @return mixed
     */
    protected function getWorkflowInstance(
        $name,
        array $workflowData,
        Definition $definition,
        MarkingStoreInterface $markingStore
    ) {
        $className = $this->getWorkflowClass($workflowData);

        return new $className($workflowData, $definition, $markingStore, $this->dispatcher, $name);
    }

    /**
     * @param bool  $override
     * @return mixed|string
     */
    private function getWorkflowClass(array $workflowData, $override = true): string
    {
        if ($override) {
            $className = StateWorkflow::class;
        } elseif (isset($workflowData['type']) && $workflowData['type'] === 'state_machine') {
            $className = StateMachine::class;
        } else {
            $className = Workflow::class;
        }

        return $className;
    }

    /**
     * Return the making store instance.
     *
     *
     * @throws \ReflectionException
     *
     */
    protected function getMarkingStoreInstance(array $workflowData): object
    {
        $markingStoreData = $workflowData['marking_store'] ?? [];
        $propertyPath = $workflowData['property_path'] ?? 'current_state';

        $singleState = true;

        if (isset($markingStoreData['type']) && $markingStoreData['type'] === 'multiple_state') {
            $singleState = false; // true if the subject can be in only one state at a given time
        }

        if (isset($markingStoreData['class'])) {
            $className = $markingStoreData['class'];
        } else {
            $className = MethodMarkingStore::class;
        }

        $arguments = [$singleState, $propertyPath];
        $class = new ReflectionClass($className);

        return $class->newInstanceArgs($arguments);
    }

    /**
     * Register workflow subscribers.
     *
     * @param $class
     * @param $name
     *
     * @throws \ReflectionException
     * @throws Exception
     */
    public function addSubscriber($class, $name): void
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->implementsInterface(WorkflowEventSubscriberInterface::class)) {
            throw new Exception("$class must implements " . WorkflowEventSubscriberInterface::class);
        }

        if ($reflection->isInstantiable()) {
            Event::subscribe($reflection->newInstance($name));
        }
    }
}
