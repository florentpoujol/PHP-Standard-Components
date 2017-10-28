<?php

namespace StdCmp\Log\Helpers;

use StdCmp\Log\Traits;

/**
 * Replace words surrounded by curly braces in the message by values found in the context.
 * Words can use the dot notation to represent nested elements within the context array.
 */
class MessagePlaceholders
{
    use Traits\PlaceholderReplacement;

    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record): array
    {
        $msg = $record["message"];
        if (strpos($msg, "{") === false) {
            return $record;
        }

        $record["message"] = $this->replacePlaceholders($msg, $record["context"]);
        return $record;
    }
}
