<?php

namespace StdCmp\Event;

interface SubscriberInterface
{
    /**
     * @return array
     */
    public function getSubscribedEvents(EventManagerInterface $manager): array;
}
