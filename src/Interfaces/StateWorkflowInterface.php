<?php namespace Oneafricamedia\StateWorkflow\Interfaces;

/**
 * Interface StateWorkflowInterface
 * @package Oneafricamedia\StateWorkflow\Interfaces
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
