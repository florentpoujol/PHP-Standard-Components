<?php

namespace StdCmp\Event;

/**
 * Interface for EventManager (Proposed PSR-14)
 */
interface EventManagerInterface
{
    /**
     * Attaches a listener to an event
     *
     * @param string $eventName the event to attach too
     * @param callable $callback a callable function
     * @param int $priority the priority at which the $callback executed
     * @return bool true on success false on failure
     */
    public function attach($eventName, $callback, $priority = 0);

    /**
     * Detaches a listener from an event
     *
     * @param string $eventName the event to attach too
     * @param callable $callback a callable function
     * @return bool true on success false on failure
     */
    public function detach($eventName, $callback);

    /**
     * Clear all listeners for a given event
     *
     * @param  string $eventName
     * @return void
     */
    public function clearListeners($eventName);

    /**
     * Trigger an event
     *
     * Can accept an EventInterface or will create one if not passed
     *
     * @param  string|EventInterface $event
     * @param  object|string $target
     * @param  array|object $argv
     * @return mixed
     */
    public function trigger($event, $target = null, $argv = array());
}
