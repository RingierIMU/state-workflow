<?php

namespace Ringierimu\StateWorkflow\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ReflectionClass;
use ReflectionException;
use Ringierimu\StateWorkflow\Interfaces\WorkflowRegistryInterface;
use Ringierimu\StateWorkflow\Models\StateWorkflowHistory;
use Ringierimu\StateWorkflow\Workflow\StateWorkflow;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

/**
 * Trait HasWorkflowTrait.
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
     * @throws ReflectionException
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
     * @throws ReflectionException
     */
    public function state()
    {
        return $this->workflow()->getState($this);
    }

    /**
     * @param array $context
     *
     * @throws ReflectionException
     *
     * @return Marking
     */
    public function applyTransition($transition, $context = [])
    {
        $this->context = $context;

        return $this->workflow()->apply($this, $transition);
    }

    /**
     * @throws ReflectionException
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
     * @throws ReflectionException
     *
     * @return array|Transition[]
     */
    public function getEnabledTransition()
    {
        return $this->workflow()->getEnabledTransitions($this);
    }

    /**
     * @return HasMany
     */
    public function stateHistory()
    {
        return $this->morphMany($this->stateHistoryModel, 'model', 'model_name', null, $this->modelPrimaryKey());
    }

    /**
     * Save Model changes and log changes to StateHistory table.
     *
     *
     * @return Model
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
     * @throws ReflectionException
     */
    public function configName(): string
    {
        return lcfirst((new ReflectionClass($this))->getShortName());
    }

    public function authUserForeignKey(): string
    {
        return 'user_id';
    }

    public function modelPrimaryKey(): string
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
