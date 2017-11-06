<?php

namespace Tests\Event;

use StdCmp\Event\SubscriberInterface;

class EventSubscriber implements SubscriberInterface
{
    /**
     * @return array
     */
    public function getSubscribedEvents(): array
    {
        return [
            "sub.method" => "onMethod",
            "sub.prio.method" => [-10 => "onMinus10Method"],
            "sub.prio.multimethod" => [
                -5 => "onMinus5Method",
                1 => [
                    "on1Method",
                    [$this, "on1Method"]
                ],
            ],
        ];
    }

    public $data = [];

    function onMinus10Method($eventName, $data)
    {
        $this->data[] = $eventName . "-10";
    }

    function onMinus5Method($eventName, $data)
    {
        $this->data[] = $eventName . "-5";
    }

    function onMethod($eventName, $data)
    {
        $this->data[] = $eventName . "0";
    }

    function on1Method($eventName, $data)
    {
        $this->data[] = $eventName . "1";
    }
}
