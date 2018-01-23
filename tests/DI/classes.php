<?php

namespace DI;

interface LoggerInterface { function log(); }

class MonoLogger implements LoggerInterface
{
    function log()
    {
        return __class__;
    }
}

class OtherLogger implements LoggerInterface
{
    function log()
    {
        return __class__;
    }
}

class SimpleAutowire
{
    /**
     * @var LoggerInterface
     */
    public $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}

class OnlyParams
{
    public $priority = -999;

    /**
     * @var MonoLogger
     */
    public $monoLogger;

    public $string = "";

    public function __construct(int $priority, $monoLogger, $string)
    {
        $this->priority = $priority;
        $this->monoLogger = $monoLogger;
        $this->string = $string;
    }
}

class AutowirePlusParams extends OnlyParams
{
    /**
     * @var OtherLogger
     */
    public $logger;

    public function __construct(int $priority, OtherLogger $logger, $monoLogger, $string)
    {
        parent::__construct($priority, $monoLogger, $string);
        $this->logger = $logger;
    }
}
