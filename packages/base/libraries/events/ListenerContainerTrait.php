<?php
namespace packages\base;
/**
 * user class must have prependNamespaceIfNeeded(string) method.
 */
trait ListenerContainerTrait {

	/** @var array */
	private $events = [];

	/**
	 * register a listener for a event.
	 * 
	 * @param string $event full event name.
	 * @param string $listener must be in format: {summerized listener class}@{method name}
	 * @return void
	 */
	public function addEvent(string $event, string $listener): void {
		$event = array(
			'name' => $this->prependNamespaceIfNeeded($event),
			'listener' => $this->prependNamespaceIfNeeded($listener)
		);
		$this->events[] = $event;
	}

	/**
	 * call the listen if there is any for this event.
	 * 
	 * @param packages\base\EventInterface $e
	 * @return void
	 */
	public function trigger(EventInterface $e): void {
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