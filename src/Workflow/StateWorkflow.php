<?php

namespace Ringierimu\StateWorkflow\Workflow;

use Ringierimu\StateWorkflow\Interfaces\StateWorkflowInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Component\Workflow\Workflow;

/**
 * Class StateWorkflow.
 */
class StateWorkflow extends Workflow implements StateWorkflowInterface
{
    /**
     * StateWorkflow constructor.
     *
     * @param MarkingStoreInterface|null    $markingStore
     * @param EventDispatcherInterface|null $dispatcher
     */
    public function __construct(
        protected array $config,
        Definition $definition,
        MarkingStoreInterface $markingStore = null,
        EventDispatcherInterface $dispatcher = null,
        string $name = 'unnamed'
    ) {
        parent::__construct($definition, $markingStore, $dispatcher, $name);
    }

    /**
     * Returns the current state.
     *
     * @param $object
     */
    public function getState($object): mixed
    {
        $accessor = new PropertyAccessor();
        $propertyPath = $this->config['property_path'] ?? 'current_state';

        return $accessor->getValue($object, $propertyPath);
    }
}
