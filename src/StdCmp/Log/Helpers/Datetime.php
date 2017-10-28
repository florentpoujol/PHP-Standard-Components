<?php

namespace StdCmp\Log\Helpers;

/**
 * Replace the record's timestamp by a formatted datetime (as a string)
 */
class Datetime
{
    /**
     * @var string
     */
    protected $datetimeFormat = "Y-m-d H:i:s";

    /**
     * @var \DateTimeZone
     */
    protected $timezone;

    /**
     * @param string|null $datetimeFormat
     * @param string|\DateTimeZone|null $timeZone
     */
    public function __construct(string $datetimeFormat = null, $timezone = null)
    {
        if ($datetimeFormat !== null) {
            $this->datetimeFormat = $datetimeFormat;
        }

        if ($timezone === null) {
            $timezone = date_default_timezone_get();
        }
        if (is_string($timezone)) {
            $timezone = new \DateTimeZone($timezone);
        }
        $this->timezone = $timezone;

    }

    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record): array
    {
        $datetime = new \DateTime();
        $datetime->setTimestamp($record["timestamp"]);
        $datetime->setTimezone($this->timezone);
        $record["timestamp"] = $datetime->format($this->datetimeFormat);

        return $record;
    }
}
