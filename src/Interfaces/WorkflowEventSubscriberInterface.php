<?php

namespace Ringierimu\StateWorkflow\Interfaces;

/**
 * Interface WorkflowEventSubscriberInterface.
 */
interface WorkflowEventSubscriberInterface
{
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
    public function subscribe($event);
}
