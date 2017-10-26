<?php

namespace StdCmp\Log\Formatters;

use StdCmp\Log\Traits;

class Line
{
    use Traits\PlaceholderReplacement;

    /**
     * @var array
     */
    protected $config = [
        "line_format" => "[{datetime}]: {priority_name} ({priority}): {message} {context} {extra}\n",
        "datetime_format" => "Y-m-d H:i:s",
    ];

    /**
     * @param array $config
     */
    public function __construct(array $config = null)
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * @param array $record
     *
     * @return string
     */
    public function __invoke(array $record): string
    {
        if (strpos($this->config["line_format"], "{datetime}") !== false) {
            //$datetime = new \DateTime();
            //$datetime->setTimestamp($record["timestamp"]);
            //$datetime->setTimezone(); // ??
            //$datetime = $datetime->format($this->config["datetime_format"]);
            $record["datetime"] = date($this->config["datetime_format"], $record["timestamp"]);
        }

        return $this->replacePlaceholders($this->config["line_format"], $record);
    }
}
