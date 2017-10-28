<?php

namespace StdCmp\Log\Traits;

trait PlaceholderReplacement
{
    /**
     * Perform the recursive replacement of placeholders with the provided replacements in the input string.
     *
     * Placeholders must be surrounded by curly braces (ie: {placeholder}).
     * The are replaced by the first matching value found in the replacement array.
     * "{placeholder}" is replaced by "thevalue" when replacements = ["placeholder" => "thevalue"]
     *
     * Composite placeholder and nested replacements may be used:
     * "{place.holder}" is replaced by "thevalue" when replacements = ["place" => ["holder" => "thevalue"]]
     *
     * Values that are arrays are encoded in JSON.
     *
     * @param string $input
     * @param array $replacements
     * @param string $fullKey Only used for recursive calls
     *
     * @throws \UnexpectedValueException when values are objects not implementing __tostring().
     * @return string
     */
    public function replacePlaceholders(string $input, array $replacements, string $baseKey = ""): string
    {
        if ($baseKey !== "") {
            $baseKey .= ".";
        }

        foreach ($replacements as $key => $value) {
            $fullKey = $baseKey . $key;
            $placeholder = '{' . $fullKey . '}';
            $valueIsArray = is_array($value);
            $valueIsEmpty = empty($value);

            if (strpos($input, $placeholder) !== false) {
                // placeholder for this key
                if (is_object($value) && !method_exists($value, "__tostring")) {
                    $value = (array)$value;
                    $valueIsArray = true;
                    $valueIsEmpty = empty($value);
                }

                if ($valueIsArray) {
                    if ($valueIsEmpty) {
                        $value = "";
                    } else {
                        $value = json_encode($value); // could use var_export ?
                    }
                }

                $input = str_replace($placeholder, $value, $input);
                unset($replacements[$key]);
            } elseif ($valueIsArray && !$valueIsEmpty) {
                $input = $this->replacePlaceholders($input, $value, $fullKey);
            }
        }

        return $input;
    }
}
