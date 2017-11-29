<?php

namespace StdCmp\Log;

use Psr\Log\InvalidArgumentException;
use StdCmp\Log\Traits;

class Logger implements Interfaces\Logger
{
    use Traits\Helper, \Psr\Log\LoggerTrait;

    /**
     * @var callable[]
     */
    protected $writers = [];

    /**
     * {@inheritDoc}
     */
    public function addWriter(callable $writer)
    {
        $this->writers[] = $writer;
    }

    /**
     * {@inheritDoc}
     */
    public function getWriters(): array
    {
        return $this->writers;
    }

    /**
     * {@inheritDoc}
     */
    public function setWriters(array $writers)
    {
        $this->writers = [];
        foreach ($writers as $id => $writer) {
            if (!is_callable($writer)) {
                throw new \TypeError("Writer n°$id is a " . gettype($writer) . " instead of a callable.");
            }

            $this->writers[] = $writer;
        }
    }

    /**
     * @param string|null $streamPath
     * @internal param null|string $path
     */
    public function __construct(string $streamPath = null)
    {
        if ($streamPath !== null) {
            $this->addHelper(new Helpers\Datetime());
            $this->addHelper(new Helpers\MessagePlaceholders());
            $this->addWriter(new Writers\Stream($streamPath));
        }
    }

    /**
     * Logs with an arbitrary priority.
     *
     * @param string|int $level
     * @param string $message
     * @param array $context
     *
     * @throws \InvalidArgumentException When the level is not one of the specified levels
     * @throws \LogicException when the logger has no writer.
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        if (is_int($level)) {
            $level = self::LEVELS[$level];
        }

        $level = strtolower($level);
        if (!in_array($level, self::LEVELS)) {
            throw new InvalidArgumentException("Log level is not one of " . implode(",", self::LEVELS));
        }

        $record = [
            "level" => $level,
            "message" => (string)$message,
            "context" => $context,
            "timestamp" => time(),
        ];

        $record = $this->processHelpers($record);
        if ($record === false) {
            return;
        }

        if (count($this->writers) <= 0) {
            $msg = "The logger has no writer.";
            $msg .= " Message that was being logged with level '$record[level]': '$message'";
            throw new \LogicException($msg);
        }

        foreach ($this->writers as $writer) {
            if ($writer($record) === false) {
                return;
            }
        }
    }
}
