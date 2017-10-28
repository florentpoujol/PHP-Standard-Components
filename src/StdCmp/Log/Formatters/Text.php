<?php

namespace StdCmp\Log\Formatters;

use StdCmp\Log\Traits;

class Text
{
    use Traits\PlaceholderReplacement;

    /**
     * @var string
     */
    protected $text = "{timestamp}: {priority_name} ({priority}): {message} {context}\n";

    /**
     * @param string|null $text The format of the string, with placeholders, to be returned to a writer.
     */
    public function __construct(string $text = null)
    {
        if ($text !== null) {
            $this->text = $text;
        }
    }

    /**
     * @param array $record
     * @return string
     */
    public function __invoke(array $record): string
    {
        return $this->replacePlaceholders($this->text, $record);
    }
}
