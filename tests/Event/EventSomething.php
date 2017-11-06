<?php

namespace Tests\Event;

use StdCmp\Event\AbstractEvent;

class EventSomething extends AbstractEvent
{

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public $data = [];
}
