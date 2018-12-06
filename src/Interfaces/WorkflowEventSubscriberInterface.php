<?php namespace RingierInternationalMarketplaceUnit\StateWorkflow\Interfaces;

/**
 * Interface WorkflowEventSubscriberInterface
 * @package RingierInternationalMarketplaceUnit\StateWorkflow\Interfaces
 */
interface WorkflowEventSubscriberInterface
{

    /**
     * Register the listeners for the subscriber.
     *
     * $event->listen(
     *   "RingierInternationalMarketplaceUnit\StateWorkflow\Events\GuardEvent",
     *   "App\Listeners\UserEventSubscriber@onGuard"
     * );
     * $event->listen(
     *   "workflow.user.guard.activate",
     *   "App\Listeners\UserEventSubscriber@guardActivate"
     * );
     *
     * @param \Illuminate\Events\Dispatcher $event
     */
    public function subscribe($event);
}
