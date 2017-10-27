<?php

namespace StdCmp\Log;

use StdCmp\Log\Traits;

class Logger implements Interfaces\Logger
{
    use Traits\Helpable;

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
                throw new \UnexpectedValueException("Writer n°$id is a " . gettype($writer) . " instead of a callable.");
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
            $this->addWriter(new Writers\Stream($streamPath));
        }
    }

    /**
     * Logs with an arbitrary priority.
     *
     * @param int $priority
     * @param string $message
     * @param array $context
     *
     * @throws \LogicException when the logger has no writer.
     *
     * @return void
     */
    public function log(int $priority, string $message, array $context = [])
    {
        $record = [
            "priority" => $priority,
            "priority_name" => self::PRIORITY_NAMES[$priority],
            "message" => $message,
            "context" => $context,
            "timestamp" => time(),
            "extra" => [],
        ];

        $record = $this->processHelpers($record);
        if ($record === false) {
            return;
        }

        if (count($this->writers) <= 0) {
            $msg = "The logger has no writer.";
            $msg .= " Message that was being logged with priority '$record[priority_name]': $message";
            throw new \LogicException($msg);
        }

        foreach ($this->writers as $writer) {
            if ($writer($record) === false) {
                return;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function emergency(string $message, array $context = array())
    {
        $this->log(LOG_EMERG, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function alert(string $message, array $context = array())
    {
        $this->log(LOG_ALERT, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function critical(string $message, array $context = array())
    {
        $this->log(LOG_CRIT, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function error(string $message, array $context = array())
    {
        $this->log(LOG_ERR, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function warning(string $message, array $context = array())
    {
        $this->log(LOG_WARNING, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function notice(string $message, array $context = array())
    {
        $this->log(LOG_NOTICE, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function info(string $message, array $context = array())
    {
        $this->log(LOG_INFO, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function debug(string $message, array $context = array())
    {
        $this->log(LOG_DEBUG, $message, $context);
    }
}
