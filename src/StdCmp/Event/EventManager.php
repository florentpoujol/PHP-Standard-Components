<?php

namespace StdCmp\Event;

class EventManager implements EventManagerInterface
{
    /**
     * eventName => [priority => [listeners]]
     */
    protected $listeners = [];

    public function __construct(array $events = null)
    {
        if ($events !== null) {
            $this->attachEvents($events);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attach($eventName, $callback, $priority = 0)
    {
        $this->validateEventName($eventName);

        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }

        if (!isset($this->listeners[$eventName][$priority])) {
            $this->listeners[$eventName][$priority] = [];
        }

        $this->listeners[$eventName][$priority][] = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function detach($eventName, $callback)
    {
        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $priority => &$listeners) {
                $offset = array_search($callback, $listeners);
                if ($offset !== false) {
                    array_splice($listeners, $offset, 1);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clearListeners($eventName)
    {
        unset($this->listeners[$eventName]);
    }

    /**
     * {@inheritdoc}
     */
    public function trigger($eventName, $target = null, $params = null)
    {
        $event = null; // EventInterface
        if ($eventName instanceof EventInterface) {
            $event = $eventName;
            $eventName = $event->getName();
        } else {
            $event = new Event();
            $event->setName($eventName);
            if ($target !== null) {
                $event->setTarget($target);
            }
            if ($params !== null) {
                $event->setParams($params);
            }
        }

        if (!isset($this->listeners[$eventName])) {
            return null;
        }

        krsort($this->listeners[$eventName]); // sort by priority, highest number first

        $returnedValue = null;
        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            foreach ($listeners as $listener) {
                $_returnedValue = $listener($event);
                if ($_returnedValue !== null) {
                    $returnedValue = $_returnedValue;
                }

                if ($event->isPropagationStopped()) {
                    break;
                }
            }
        }
        return $returnedValue;
    }

    /**
     * @param array $events [eventName => callable] or [eventName => [callable]]
     */
    public function attachEvents(array $events)
    {
        foreach ($events as $eventName => $listener) {
            if (is_callable($listener)) {
                $this->attach($eventName, $listener);
                continue;
            }

            if (!is_array($listener)) {
                // throw error ?
                continue;
            }

            foreach ($listener as $_listener) {
                if (is_callable($_listener)) {
                    $this->attach($eventName, $_listener);
                }
            }
        }
    }

    public function hasListener(string $eventName): bool
    {
        if (!isset($this->listeners[$eventName])) {
            return false;
        }

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            if (!empty($listeners)) {
                return true;
            }
        }
        return false;
    }

    public function addSubscriber(SubscriberInterface $subscriber)
    {
        $events = $subscriber->getSubscribedEvents($this);

        foreach ($events as $eventName => $listener) {
            if (is_string($listener) && method_exists($subscriber, $listener)) {
                $listener = [$subscriber, $listener];
            }

            if (is_callable($listener)) {
                $this->attach($eventName, $listener);
                continue;
            }

            if (!is_array($listener)) {
                continue;
            }

            foreach ($listener as $_listener) {
                if (is_string($_listener) && method_exists($subscriber, $_listener)) {
                    $_listener = [$subscriber, $_listener];
                }

                if (is_callable($_listener)) {
                    $this->attach($eventName, $_listener);
                    continue;
                }
            }
        }
    }

    protected function validateEventName($eventName)
    {
        if (preg_match("/[a-zA-Z0-9_\.]+/", $eventName) !== 1) {
            throw new \InvalidArgumentException("Wrong event name format for event '$eventName'");
        }
    }
}
