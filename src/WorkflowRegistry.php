<?php namespace Ringierimu\StateWorkflow;

use Exception;
use Illuminate\Support\Facades\Event;
use ReflectionClass;
use Ringierimu\StateWorkflow\Interfaces\WorkflowEventSubscriberInterface;
use Ringierimu\StateWorkflow\Interfaces\WorkflowRegistryInterface;
use Ringierimu\StateWorkflow\Subscribers\WorkflowSubscriber;
use Ringierimu\StateWorkflow\Workflow\StateWorkflow;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Component\Workflow\MarkingStore\MultipleStateMarkingStore;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;

/**
 * Class WorkflowRegistry
 * @package Ringierimu\StateWorkflow
 */
class WorkflowRegistry implements WorkflowRegistryInterface
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * WorkflowRegistry constructor.
     * @param array $config
     * @throws \ReflectionException
     */
    public function __construct(array $config)
    {
        $this->registry = new Registry();
        $this->config = $config;
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
     * {@inheritDoc}
     */
    public function get($subject, $workflowName = null)
    {
        return $this->registry->get($subject, $workflowName);
    }

    /**
     * Add a workflow to the subject
     *
     * @param StateWorkflow $workflow
     * @param $supportStrategy
     */
    public function add(StateWorkflow $workflow, $supportStrategy)
    {
        $this->registry->addWorkflow($workflow, new InstanceOfSupportStrategy($supportStrategy));
    }

    /**
     * Add a workflow to the registry from array
     *
     * @param $name
     * @param array $workflowData
     * @throws \ReflectionException
     */
    public function addWorkflows($name, array $workflowData)
    {
        $builder = new DefinitionBuilder($workflowData['states']);

        foreach ($workflowData['transitions'] as $transitionName => $transition) {
            if (!is_string($transitionName)) {
                $transitionName = $transition['name'];
            }

            foreach ((array)$transition['from'] as $form) {
                $builder->addTransition(new Transition($transitionName, $form, $transition['to']));
            }
        }

        $definition = $builder->build();
        $markingStore = $this->getMarkingStoreInstance($workflowData);
        $workflow = $this->getWorkflowInstance($name, $workflowData, $definition, $markingStore);

        $this->add($workflow, $workflowData['class']);
    }

    /**
     * Return the workflow instance
     *
     * @param $name
     * @param array $workflowData
     * @param Definition $definition
     * @param MarkingStoreInterface $markingStore
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
     * @param array $workflowData
     * @param bool $override
     * @return mixed|string
     */
    private function getWorkflowClass(array $workflowData, $override = true)
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
     * Return the making store instance
     *
     * @param array $workflowData
     * @return object|MarkingStoreInterface
     * @throws \ReflectionException
     */
    protected function getMarkingStoreInstance(array $workflowData)
    {
        $markingStoreData = isset($workflowData['marking_store']) ? $workflowData['marking_store'] : [];
        $arguments = isset($workflowData['property_path']) ? [$workflowData['property_path']] : ['current_state'];

        if (isset($markingStoreData['class'])) {
            $className = $markingStoreData['class'];
        } elseif (isset($markingStoreData['type']) && $markingStoreData['type'] === 'multiple_state') {
            $className = MultipleStateMarkingStore::class;
        } else {
            $className = SingleStateMarkingStore::class;
        }

        $class = new \ReflectionClass($className);

        return $class->newInstanceArgs($arguments);
    }

    /**
     * Register workflow subscribers
     *
     * @param $class
     * @param $name
     * @return void
     * @throws \ReflectionException
     * @throws Exception
     */
    public function addSubscriber($class, $name)
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
