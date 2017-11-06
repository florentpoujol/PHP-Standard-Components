<?php

namespace StdCmp\Event;

class AbstractEvent implements EventInterface
{
    protected $name = "";

    protected $propagationStopped = false;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param bool $stop
     */
    public function stopPropagation(bool $stop = true)
    {
        $this->propagationStopped = $stop;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
