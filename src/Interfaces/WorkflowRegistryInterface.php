<?php namespace Oneafricamedia\StateWorkflow\Interfaces;

use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Interface WorkflowRegistryInterface
 * @package Oneafricamedia\StateWorkflow\Interfaces
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
