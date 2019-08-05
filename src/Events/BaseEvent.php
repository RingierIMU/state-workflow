<?php

namespace Ringierimu\StateWorkflow\Events;

use Symfony\Component\Workflow\Event\Event;

/**
 * Class BaseEvent.
 */
abstract class BaseEvent
{
    /**
     * @var Event
     */
    protected $originalEvent;

    /**
     * BaseEvent constructor.
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->originalEvent = $event;
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
