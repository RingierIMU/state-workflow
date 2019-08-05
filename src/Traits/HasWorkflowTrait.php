<?php namespace Ringierimu\StateWorkflow\Traits;

use Illuminate\Support\Facades\Log;
use Ringierimu\StateWorkflow\Interfaces\WorkflowRegistryInterface;
use Ringierimu\StateWorkflow\Models\StateWorkflowHistory;
use Ringierimu\StateWorkflow\Workflow\StateWorkflow;

/**
 * Trait HasWorkflowTrait
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
     * Model to save model change history from one state to another
     *
     * @var string
     */
    private $stateHistoryModel = StateWorkflowHistory::class;

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
     * @param array $context
     * @return \Symfony\Component\Workflow\Marking
     * @throws \ReflectionException
     */
    public function applyTransition($transition, $context = [])
    {
        $this->context = $context;
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
     * Return object available transitions
     *
     * @return array|\Symfony\Component\Workflow\Transition[]
     * @throws \ReflectionException
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
     * Save Model changes and log changes to StateHistory table
     *
     * @param array $transitionData
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function saveStateHistory(array $transitionData)
    {
        $this->save(); //@TODO: why?
        $transitionData[$this->authUserForeignKey()] = $this->authenticatedUserId();

        return $this->stateHistory()->create($transitionData);
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

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
