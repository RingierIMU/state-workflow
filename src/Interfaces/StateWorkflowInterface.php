<?php namespace Ringierimu\StateWorkflow\Interfaces;

/**
 * Interface StateWorkflowInterface
 * @package Ringierimu\StateWorkflow\Interfaces
 */
interface StateWorkflowInterface
{

    /**
     * Returns the current state
     *
     * @param $object
     * @return mixed
     */
    public function getState($object);
}
