<?php

namespace Tests\Event;

use StdCmp\Event\Event;

class EventSomething extends Event
{

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
