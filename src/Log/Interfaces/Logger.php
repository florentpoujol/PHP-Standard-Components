<?php

namespace StdCmp\Log\Interfaces;

use Psr\Log\LogLevel;

interface Logger extends \Psr\Log\LoggerInterface, HelperAware
{
    /**
     * @var string[]
     */
    const LEVELS = [
        // set in the order of the built-in log constants
        // LOG_EMERg, LOG_ALERT, ...
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

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
     * @throws \TypeError When a writer is not a callable.
     * @return void
     */
    public function setWriters(array $writers);
}
