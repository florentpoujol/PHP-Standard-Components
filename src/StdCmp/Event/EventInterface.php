<?php

namespace StdCmp\Event;

interface EventInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param bool $stop
     */
    public function stopPropagation(bool $stop = true);

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool;
}
