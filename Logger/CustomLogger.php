<?php
namespace TR\CustomerPricing\Logger;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;

class CustomLogger extends MonologLogger
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/customerpricing.log';

    public function __construct()
    {
        parent::__construct('customerpricing'); // Initialize the logger
        $this->pushHandler(new StreamHandler(BP . $this->fileName, MonologLogger::DEBUG)); // Set the log handler
    }

    /**
     * Log a message with a specific level.
     *
     * @param int|string $level The log level (e.g., INFO, ERROR).
     * @param string|Stringable $message The message to log.
     * @param array $context Additional context to pass.
     * 
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        parent::log($level, $message, $context); // Calls Monolog's log method
    }
}