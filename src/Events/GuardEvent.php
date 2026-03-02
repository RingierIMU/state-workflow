<?php

namespace Ringierimu\StateWorkflow\Events;

use Symfony\Component\Workflow\Event\GuardEvent as SymfonyGuardEvent;

/**
 * Validate whether the transition is allowed at all.
 *
 * Class GuardEvent
 */
class GuardEvent extends BaseEvent
{
    /**
     * GuardEvent constructor.
     */
    public function __construct(SymfonyGuardEvent $event)
    {
        $this->originalEvent = $event;
    }

    /**
     * @return \Symfony\Component\Workflow\Event\Event|SymfonyGuardEvent
     */
    #[\Override]
    public function getOriginalEvent()
    {
        return $this->originalEvent;
    }
}
