<?php

namespace Ringierimu\StateWorkflow\Interfaces;

/**
 * Interface StateWorkflowInterface.
 */
interface StateWorkflowInterface
{
    /**
     * Returns the current state.
     *
     * @param $object
     *
     * @return mixed
     */
    public function getState($object);
}
