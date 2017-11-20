<?php

namespace Tests\Event;

use StdCmp\Event\EventManagerInterface;
use StdCmp\Event\SubscriberInterface;

class EventSubscriber implements SubscriberInterface
{
    /**
     * @return array
     */
    public function getSubscribedEvents(EventManagerInterface $manager): array
    {
        $manager->attach("sub.method", [$this, "onMinus10Method"], -10);

        return [
            "sub.method" => "onMethod"
        ];
    }

    public $data = [];

    function onMinus10Method($event)
    {
        $this->data[] = $event->getName() . "-10";
    }

    function onMethod($event)
    {
        $this->data[] = $event->getName() . "0";
    }
}
