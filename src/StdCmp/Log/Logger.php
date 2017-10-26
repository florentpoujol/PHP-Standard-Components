<?php

namespace StdCmp\Log;

class Logger implements Interfaces\Logger
{
    /**
     * The list of processors for this logger.
     *
     * @var callable[]
     */
    protected $processors = [];

    /**
     * The list of writers for this logger.
     *
     * @var callable[]
     */
    protected $writers = [];

    /**
     * {@inheritDoc}
     */
    public function addProcessor(callable $processor)
    {
        $this->processors[] = $processor;
    }

    /**
     * {@inheritDoc}
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }

    /**
     * {@inheritDoc}
     */
    public function setProcessors(array $processors)
    {
        $this->processors = [];
        foreach ($processors as $id => $processor) {
            if (!is_callable($processor)) {
                throw new \UnexpectedValueException("Processor n°$id is a " . gettype($processor) . " instead of a callable.");
            }

            $this->processors[] = $processor;
        }
    }

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

        foreach ($this->processors as $key => $processor) {
            $record = $processor($record);
            if (empty($record) || !is_array($record)) {
                throw new \UnexpectedValueException("Processor n°$key returns non array");
            }
        }

        if (count($this->writers) <= 0) {
            $msg = "The logger has no writer.";
            $msg .= " Message that was being logged with priority '$record[priority_name]': $message";
            throw new \LogicException($msg);
        }

        foreach ($this->writers as $writer) {
            $writer($record);
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
