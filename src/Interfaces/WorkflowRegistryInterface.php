<?php namespace RingierInternationalMarketplaceUnit\StateWorkflow\Interfaces;

use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Interface WorkflowRegistryInterface
 * @package RingierInternationalMarketplaceUnit\StateWorkflow\Interfaces
 */
interface WorkflowRegistryInterface
{
    /**
     * Returns SateWorkflow
     *
     * @param object $object
     * @param null $workflowName
     * @return WorkflowInterface
     */
    public function get($object, $workflowName = null);
}
