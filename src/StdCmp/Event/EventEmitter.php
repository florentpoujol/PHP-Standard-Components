<?php

namespace StdCmp\Event;

class EventEmitter
{
    /**
     * eventName => [priority => [listeners]]
     * @var array
     */
    protected $listeners = [];

    /**
     * @param string $eventName
     * @param callable $listener
     * @param int|null $priority
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0)
    {
        $this->validateEventName($eventName);

        if (! isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }
        if (! isset($this->listeners[$eventName][$priority])) {
            $this->listeners[$eventName][$priority] = [];
        }

        $this->listeners[$eventName][$priority][] = $listener;
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function hasListener(string $eventName): bool
    {
        if (! isset($this->listeners[$eventName])) {
            return false;
        }

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            if (! empty($listeners)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param object $subscriber
     */
    public function addSubscriber($subscriber)
    {
        if (!$subscriber instanceof SubscriberInterface) {
            throw new \InvalidArgumentException("Subscribers must be objects that implements the SubscriberInterface.");
        }

        $events = $subscriber->getSubscribedEvents();
        foreach ($events as $eventName => $info) {
            // info can be
            // - string: callable or method name
            // - array:
            //    - int: priority => string: callable or method name
            //    - int: priority => array: [string: callable or method name]

            if (is_string($info)) {
                if (method_exists($subscriber, $info)) {
                    $this->addListener($eventName, [$subscriber, $info]);
                } else {
                    $this->addListener($eventName, $info);
                }

                continue;
            }

            if (!is_array($info)) {
                // throw error ?
                continue;
            }

            foreach ($info as $priority => $listeners) {
                if (is_string($listeners)) {
                    $listeners = [$listeners];
                }

                foreach ($listeners as $listener) {
                    if (is_string($listener) && method_exists($subscriber, $listener)) {
                        $listener = [$subscriber, $listener];
                    }

                    $this->addListener($eventName, $listener, $priority);
                }
            }
        }
    }

    /**
     * @param string|AbstractEvent $eventName
     * @param mixed|null $data
     */
    public function emit($eventName, $data = null)
    {
        $useEventObject = false;
        if ($eventName instanceof EventInterface) {
            $useEventObject = true;
            $data = $eventName;
            $eventName = $data->getName();
        }

        if (! isset($this->listeners[$eventName])) {
            return;
        }

        krsort($this->listeners[$eventName]); // sort by priority, highest number first

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            foreach ($listeners as $listener) {
                if ($useEventObject) {
                    $returnedValue = $listener($data);
                } else {
                    $returnedValue = $listener($eventName, $data);
                }

                if (
                    $returnedValue === false ||
                    ($data instanceof EventInterface && $data->isPropagationStopped())
                ) {
                    return;
                }
            }
        }
    }

    /**
     * @param $eventName
     * @throws \Exception
     */
    protected function validateEventName($eventName)
    {
        if (preg_match("/[a-zA-Z0-9_\.-]+/", $eventName) !== 1) {
            throw new \Exception("Wrong event name format");
        }
    }
}
