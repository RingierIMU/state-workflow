<?php

namespace Ringierimu\StateWorkflow\Interfaces;

use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Interface WorkflowRegistryInterface.
 */
interface WorkflowRegistryInterface
{
    /**
     * Returns SateWorkflow.
     *
     * @param object $object
     * @param null $workflowName
     *
     * @return WorkflowInterface
     */
    public function get($object, $workflowName = null);

    /**
     * Register workflow subscribers.
     */
    public function addSubscriber($class, $name);
}
