<?php

namespace Mcfedr\SqsQueueDriverBundle\Worker;

use Mcfedr\QueueManagerBundle\Queue\Worker;
use Psr\Log\LoggerInterface;

class TestWorker implements Worker
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Called to start the queued task.
     *
     * @param array $options
     *
     * @throws \Exception
     */
    public function execute(array $options)
    {
        $this->logger->info('execute', ['options' => $options]);
    }
}
