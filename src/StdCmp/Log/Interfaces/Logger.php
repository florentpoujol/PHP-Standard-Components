<?php

namespace StdCmp\Log\Interfaces;

interface Logger
{
    /**
     * @var string[]
     */
    const PRIORITY_NAMES = [
        "emergency", "alert", "critical", "error",
        "warning", "notice", "info", "debug"
    ];

    /**
     * @param callable $processor
     * @return void
     */
    public function addProcessor(callable $processor);

    /**
     * @return array
     */
    public function getProcessors(): array;

    /**
     * @param callable[] $processors
     * @return void
     */
    public function setProcessors(array $processors);

    /**
     * @param callable $writer
     * @return void
     */
    public function addWriter(callable $writer);

    /**
     * @return array
     */
    public function getWriters(): array;

    /**
     * @param callable[] $writers
     * @return void
     */
    public function setWriters(array $writers);

    /**
     * Logs with an arbitrary level.
     *
     * @param int $priority
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function log(int $priority, string $message, array $context = array());

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function emergency(string $message, array $context = array());

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function alert(string $message, array $context = array());

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function critical(string $message, array $context = array());

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function error(string $message, array $context = array());

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function warning(string $message, array $context = array());

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function notice(string $message, array $context = array());

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function info(string $message, array $context = array());

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function debug(string $message, array $context = array());
}
