<?php

namespace Ringierimu\StateWorkflow\Interfaces;

/**
 * Interface StateWorkflowInterface.
 */
interface StateWorkflowInterface
{
    /**
     * Returns the current state.
     */
    public function getState($object);
}
