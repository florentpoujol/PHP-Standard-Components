<?php

namespace StdCmp\DI;

interface ContainerInterface
{
    /**
     * @param string $id Identifier of the entry to look for.
      * @return mixed Entry.
     */
    public function get(string $id);

    /**
     * @param string $id Identifier of the entry to look for.
     * @return bool
     */
    public function has(string $id): bool;
}
