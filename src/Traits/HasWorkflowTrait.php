<?php

namespace Ringierimu\StateWorkflow\Traits;

use Ringierimu\StateWorkflow\Interfaces\WorkflowRegistryInterface;
use Ringierimu\StateWorkflow\Models\StateWorkflowHistory;
use Ringierimu\StateWorkflow\Workflow\StateWorkflow;

/**
 * Trait HasWorkflowTrait.
 *
 * @author Norby Baruani <norbyb@roam.africa/>
 */
trait HasWorkflowTrait
{
    /** @var StateWorkflow */
    protected $workflow;

    /**
     * @var array
     */
    protected $context = [];

    /**
     * Model to save model change history from one state to another.
     *
     * @var string
     */
    private $stateHistoryModel = StateWorkflowHistory::class;

    /**
     * @throws \ReflectionException
     *
     * @return StateWorkflow
     */
    public function workflow()
    {
        if (!$this->workflow) {
            $this->workflow = app(WorkflowRegistryInterface::class)->get($this, $this->configName());
        }

        return $this->workflow;
    }

    /**
     * @throws \ReflectionException
     *
     * @return mixed
     */
    public function state()
    {
        return $this->workflow()->getState($this);
    }

    /**
     * @param $transition
     * @param array $context
     *
     * @throws \ReflectionException
     *
     * @return \Symfony\Component\Workflow\Marking
     */
    public function applyTransition($transition, $context = [])
    {
        $this->context = $context;

        return $this->workflow()->apply($this, $transition);
    }

    /**
     * @param $transition
     *
     * @throws \ReflectionException
     *
     * @return bool
     */
    public function canTransition($transition)
    {
        return $this->workflow()->can($this, $transition);
    }

    /**
     * Return object available transitions.
     *
     * @throws \ReflectionException
     *
     * @return array|\Symfony\Component\Workflow\Transition[]
     */
    public function getEnabledTransition()
    {
        return $this->workflow()->getEnabledTransitions($this);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stateHistory()
    {
        return $this->morphMany($this->stateHistoryModel, 'model', 'model_name', null, $this->modelPrimaryKey());
    }

    /**
     * Save Model changes and log changes to StateHistory table.
     *
     * @param array $transitionData
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function saveStateHistory(array $transitionData)
    {
        $transitionData[$this->authUserForeignKey()] = $this->authenticatedUserId();

        return $this->stateHistory()->create($transitionData);
    }

    /**
     * Return authenticated user id.
     *
     * @return int|null
     */
    public function authenticatedUserId()
    {
        return auth()->id();
    }

    /**
     * Model configuration name on config/workflow.php.
     *
     * @throws \ReflectionException
     *
     * @return string
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

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
