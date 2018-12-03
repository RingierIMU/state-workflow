<?php namespace Oneafricamedia\StateWorkflow\Events;

use Symfony\Component\Workflow\Event\GuardEvent as SymfonyGuardEvent;

/**
 * Validate whether the transition is allowed at all
 *
 * Class GuardEvent
 * @package Oneafricamedia\StateWorkflow\Events
 */
class GuardEvent extends BaseEvent
{
    public function __construct(SymfonyGuardEvent $event)
    {
        $this->originalEvent = $event;
    }
}
