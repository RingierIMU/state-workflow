<?php namespace RingierInternationalMarketplaceUnit\StateWorkflow\Traits;

use RingierInternationalMarketplaceUnit\StateWorkflow\Interfaces\WorkflowRegistryInterface;
use RingierInternationalMarketplaceUnit\StateWorkflow\Models\StateWorkflowHistory;
use RingierInternationalMarketplaceUnit\StateWorkflow\Workflow\StateWorkflow;

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
        return $this->hasMany($this->stateHistoryModel, 'model_id', $this->modelPrimaryKey());
    }

    /**
     * Save Model changes and log changes to StateHistory table
     *
     * @param array $transitionData
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function saveStateHistory(array $transitionData)
    {
        $this->save();

        $transitionData[$this->authUserForeignKey()] = $this->authenticatedUserId();
        $transitionData['model_name'] = get_class();

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
}
