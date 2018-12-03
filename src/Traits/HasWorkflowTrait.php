<?php namespace Oneafricamedia\StateWorkflow\Traits;

use Oneafricamedia\StateWorkflow\Interfaces\WorkflowRegistryInterface;
use Oneafricamedia\StateWorkflow\Workflow\StateWorkflow;

/**
 * Trait HasWorkflowTrait
 */
trait HasWorkflowTrait
{
    /** @var StateWorkflow */
    protected $workflow;

    /**
     * @return StateWorkflow
     * @throws \ReflectionException
     */
    public function workflow()
    {
        if (!$this->workflow) {
            $this->workflow = app(WorkflowRegistryInterface::class)->get($this, $this->configName());
        }
        return $this->workflow;
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     */
    public function state()
    {
        return $this->workflow()->getState($this);
    }

    /**
     * @param $transition
     * @return \Symfony\Component\Workflow\Marking
     * @throws \ReflectionException
     */
    public function applyTransition($transition)
    {
        return $this->workflow()->apply($this, $transition);
    }

    /**
     * @param $transition
     * @return bool
     * @throws \ReflectionException
     */
    public function canTransition($transition)
    {
        return $this->workflow()->can($this, $transition);
    }

    /**
     * Return authenticated user id
     *
     * @return int|null
     */
    public function authenticatedUserId()
    {
        return auth()->id();
    }

    /**
     * Model configuration name on config/workflow.php
     *
     * @return string
     * @throws \ReflectionException
     */
    public function configName()
    {
        return lcfirst((new \ReflectionClass($this))->getShortName());
    }

    /**
     * @return string
     */
    public function authUserForeignKey()
    {
        return 'user_id';
    }

    /**
     * @return string
     */
    public function modelPrimaryKey()
    {
        return 'id';
    }
}
