<?php

namespace packages\base;

trait ListenerContainerTrait
{
    /** @var array */
    private $events = [];

    /**
     * register a listener for a event.
     *
     * @param string $event    full event name
     * @param string $listener must be in format: {summerized listener class}@{method name}
     */
    public function addEvent(string $event, string $listener): void
    {
        $event = [
            'name' => $event,
            'listener' => $listener,
        ];
        $this->events[] = $event;
    }

    /**
     * call the listen if there is any for this event.
     *
     * @param packages\base\EventInterface $e
     */
    public function trigger(EventInterface $e): void
    {
        $eventName = strtolower(get_class($e));
        foreach ($this->events as $event) {
            if ($event['name'] == $eventName) {
                list($listener, $method) = explode('@', $event['listener'], 2);
                $listener = new $listener();
                $listener->$method($e);
            }
        }
    }
}
