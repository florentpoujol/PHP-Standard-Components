<?php

namespace StdCmp\Log\Traits;

trait PlaceholderReplacement
{
    /**
     * Perform the recursive replacement of placeholders.
     *
     * @param string $input
     * @param array $replacements
     * @param string $fullKey
     * @return string
     */
    public function replacePlaceholders(string $input, array $replacements, string $fullKey = ""): string
    {
        if ($fullKey !== "") {
            $fullKey .= ".";
        }

        foreach ($replacements as $key => $value) {
            $fullKey .= $key;

            if (is_array($value)) {
                $this->replacePlaceholders($input, $value, $fullKey);
            } else {
                $input = str_replace('{' . $fullKey . '}', (string) $value, $input);
            }
        }

        return $input;
    }
}
