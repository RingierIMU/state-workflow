<?php namespace RingierInternationalMarketplaceUnit\StateWorkflow\Interfaces;

/**
 * Interface StateWorkflowInterface
 * @package RingierInternationalMarketplaceUnit\StateWorkflow\Interfaces
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
