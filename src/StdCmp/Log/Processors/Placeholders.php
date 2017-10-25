<?php

namespace StdCmp\Log\Processors;

use StdCmp\Log\Traits;

class Placeholders
{
    use Traits\PlaceholderReplacement;

    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record): array
    {
        $record["message"] = $this->replacePlaceholders($record["message"], $record["context"]);

        return $record;
    }
}
