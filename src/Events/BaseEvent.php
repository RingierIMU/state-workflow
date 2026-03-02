<?php

namespace Ringierimu\StateWorkflow\Events;

use Symfony\Component\Workflow\Event\Event;

/**
 * Class BaseEvent.
 */
abstract class BaseEvent
{
    /**
     * BaseEvent constructor.
     */
    public function __construct(protected \Symfony\Component\Workflow\Event\Event $originalEvent)
    {
    }

    /**
     * Return the original event.
     *
     * @return Event
     */
    public function getOriginalEvent()
    {
        return $this->originalEvent;
    }
}
