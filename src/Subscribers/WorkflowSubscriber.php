<?php namespace RingierInternationalMarketplaceUnit\StateWorkflow\Subscribers;

use RingierInternationalMarketplaceUnit\StateWorkflow\Events\CompletedEvent;
use RingierInternationalMarketplaceUnit\StateWorkflow\Events\EnteredEvent;
use RingierInternationalMarketplaceUnit\StateWorkflow\Events\EnterEvent;
use RingierInternationalMarketplaceUnit\StateWorkflow\Events\GuardEvent;
use RingierInternationalMarketplaceUnit\StateWorkflow\Events\LeaveEvent;
use RingierInternationalMarketplaceUnit\StateWorkflow\Events\TransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent as SymfonyGuardEvent;

/**
 * Class WorkflowSubscriber
 * @package RingierInternationalMarketplaceUnit\StateWorkflow\Subscribers
 */
class WorkflowSubscriber implements EventSubscriberInterface
{
    /**
     * Validate whether the transition is allowed at all
     *
     * @param SymfonyGuardEvent $event
     */
    public function guardEvent(SymfonyGuardEvent $event)
    {
        $workflowName   = $event->getWorkflowName();
        $transitionName = $event->getTransition()->getName();

        event(new GuardEvent($event));
        event(sprintf('workflow.%s.guard', $workflowName), $event);
        event(sprintf('workflow.%s.guard.%s', $workflowName, $transitionName), $event);
    }

    /**
     * The subject is about to leave a place.
     *
     * @param Event $event
     */
    public function leaveEvent(Event $event)
    {
        $places       = $event->getTransition()->getFroms();
        $workflowName = $event->getWorkflowName();

        event(new LeaveEvent($event));
        event(sprintf('workflow.%s.leave', $workflowName), $event);

        foreach ($places as $place) {
            event(sprintf('workflow.%s.leave.%s', $workflowName, $place), $event);
        }
    }

    /**
     * The subject is going through this transition
     *
     * @param Event $event
     */
    public function transitionEvent(Event $event)
    {
        $workflowName   = $event->getWorkflowName();
        $transitionName = $event->getTransition()->getName();

        event(new TransitionEvent($event));
        event(sprintf('workflow.%s.transition', $workflowName), $event);
        event(sprintf('workflow.%s.transition.%s', $workflowName, $transitionName), $event);
    }

    /**
     * The subject is about to enter a new place. This event is triggered just before the subject places are updated,
     * which means that the marking of the subject is not yet updated with the new places.
     *
     * @param Event $event
     */
    public function enterEvent(Event $event)
    {
        $places       = $event->getTransition()->getTos();
        $workflowName = $event->getWorkflowName();

        event(new EnterEvent($event));
        event(sprintf('workflow.%s.enter', $workflowName), $event);

        foreach ($places as $place) {
            event(sprintf('workflow.%s.enter.%s', $workflowName, $place), $event);
        }
    }

    /**
     * The subject has entered in the places and the marking is updated (making it a good place to flush data in Doctrine).
     *
     * @param Event $event
     */
    public function enteredEvent(Event $event)
    {
        $places       = $event->getTransition()->getTos();
        $workflowName = $event->getWorkflowName();

        $from = implode(",", $event->getTransition()->getFroms());
        $to = implode(",", $event->getTransition()->getTos());
        $model = $event->getSubject();

        if (method_exists($model, 'saveStateHistory')) {
            $model->saveStateHistory([
                "transition" => $event->getTransition()->getName(),
                "from" => $from,
                "to" => $to
            ]);
        }

        event(new EnteredEvent($event));
        event(sprintf('workflow.%s.entered', $workflowName), $event);

        foreach ($places as $place) {
            event(sprintf('workflow.%s.entered.%s', $workflowName, $place), $event);
        }
    }

    /**
     * The object has completed this transition.
     *
     * @param Event $event
     */
    public function completedEvent(Event $event)
    {
        $workflowName   = $event->getWorkflowName();
        $transitionName = $event->getTransition()->getName();

        event(new CompletedEvent($event));
        event(sprintf('workflow.%s.completed', $workflowName), $event);
        event(sprintf('workflow.%s.completed.%s', $workflowName, $transitionName), $event);
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            'workflow.guard'      => ['guardEvent'],
            'workflow.leave'      => ['leaveEvent'],
            'workflow.transition' => ['transitionEvent'],
            'workflow.enter'      => ['enterEvent'],
            'workflow.entered'    => ['enteredEvent'],
            'workflow.completed'  => ['completedEvent'],
        ];
    }
}
