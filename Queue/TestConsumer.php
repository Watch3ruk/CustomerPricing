<?php
namespace TR\CustomerPricing\Queue;

use Psr\Log\LoggerInterface;

class TestConsumer
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $message
     * @return void
     */
    public function process($message)
    {
        $this->logger->info("TEST CONSUMER PROCESSED MESSAGE: " . $message);
    }
}