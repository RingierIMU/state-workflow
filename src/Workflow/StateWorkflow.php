<?php namespace Ringierimu\StateWorkflow\Workflow;

use Ringierimu\StateWorkflow\Interfaces\StateWorkflowInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Component\Workflow\Workflow;

/**
 * Class StateWorkflow
 * @package Ringierimu\StateWorkflow\Workflow
 */
class StateWorkflow extends Workflow implements StateWorkflowInterface
{
    /** @var array */
    protected $config;

    /**
     * StateWorkflow constructor.
     * @param Definition $definition
     * @param MarkingStoreInterface|null $markingStore
     * @param EventDispatcherInterface|null $dispatcher
     * @param string $name
     * @param array $config
     */
    public function __construct(
        array $config,
        Definition $definition,
        MarkingStoreInterface $markingStore = null,
        EventDispatcherInterface $dispatcher = null,
        string $name = 'unnamed'
    ) {
        parent::__construct($definition, $markingStore, $dispatcher, $name);
        $this->config = $config;
    }

    /**
     * Returns the current state
     *
     * @param $object
     * @return mixed
     */
    public function getState($object)
    {
        $accessor = new PropertyAccessor();
        $propertyPath = isset($this->config['property_path']) ? $this->config['property_path'] : 'current_state';
        return $accessor->getValue($object, $propertyPath);
    }
}
