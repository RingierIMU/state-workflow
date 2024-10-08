<?php

namespace Ringierimu\StateWorkflow\Subscribers;

use Ringierimu\StateWorkflow\Interfaces\WorkflowEventSubscriberInterface;

/**
 * Class WorkflowSubscriberHandler.
 *
 * Dynamically register listener for workflow events
 *
 * @author Norby Baruani <norbyb@roam.africa/>
 */
abstract class WorkflowSubscriberHandler implements WorkflowEventSubscriberInterface
{
    /** @var null */
    protected $name;

    /**
     * WorkflowSubscriberHandler constructor.
     *
     * @param $workflowName
     */
    public function __construct($workflowName = null)
    {
        $this->name = $workflowName;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * $event->listen(
     *   "Ringierimu\StateWorkflow\Events\GuardEvent",
     *   "App\Listeners\UserEventSubscriber@onGuard"
     * );
     *
     * $event->listen(
     *   "workflow.user.guard.activate",
     *   "App\Listeners\UserEventSubscriber@onGuardActivate"
     * );
     *
     * @param \Illuminate\Events\Dispatcher $event
     */
    public function subscribe($event)
    {
        // get name of instantiated concrete class
        $class = get_called_class();
        // loop through each method of the class
        foreach (get_class_methods($class) as $method) {
            // if the method name starts with 'on'
            if (preg_match('/^on/', $method)) {
                // attach the event listener
                $event->listen($this->key($method), $class . '@' . $method);
            }
        }
    }

    /**
     * Generate event key from Subscriber method to match workflow event dispatcher names.
     *
     * Format on how to register method to listen to specific workflow events.
     *
     * eg.
     * 1. on[Event] - onGuard
     * 2. on[Event][Transition/State name] - onGuardActivate
     *
     * NB:
     * - Guard and Transition event uses of transition name
     * - Leave, Enter and Entered event uses state name
     *
     * ******* Fired Events *********
     * - Guard Event
     * workflow.guard
     * workflow.[workflow name].guard
     * workflow.[workflow name].guard.[transition name]
     *
     * - Leave Event
     * workflow.leave
     * workflow.[workflow name].leave
     * workflow.[workflow name].leave.[state name]
     *
     * - Transition Event
     * workflow.transition
     * workflow.[workflow name].transition
     * workflow.[workflow name].transition.[transition name]
     *
     * - Enter Event
     * workflow.enter
     * workflow.[workflow name].enter
     * workflow.[workflow name].enter.[state name]
     *
     * - Entered Event
     * workflow.entered
     * workflow.[workflow name].entered
     * workflow.[workflow name].entered.[state name]
     *
     * @param $name
     *
     * @return string
     */
    protected function key($name)
    {
        // remove on from beginning. eg. onGuard => Guard
        $name = ltrim($name, 'on');
        // prepend uppercase letters with . eg. EnteredDeleted => .Entered.Deleted
        $name = preg_replace_callback('/[A-Z]/', function ($m) {
            return ".{$m[0]}";
        }, $name);
        // remove trailing . eg. .Entered.Deleted => Entered.Deleted
        $name = ltrim($name, '.');
        // now that we have the dots we can lowercase the name. eg. Entered.Deleted => entered.deleted
        $name = strtolower($name);

        $segments = explode('.', $name);

        // add underscore for transition with underscore name
        if (count($segments) > 2) {
            $transition = $segments[0];
            unset($segments[0]);
            $flow = implode('_', $segments);
            $name = $transition . '.' . $flow;
        }

        return sprintf('workflow.%s.%s', $this->name, $name);
    }
}
