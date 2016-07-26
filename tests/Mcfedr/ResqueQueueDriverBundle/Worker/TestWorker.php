<?php

namespace Mcfedr\SqsQueueDriverBundle\Worker;

use Mcfedr\QueueManagerBundle\Queue\Worker;
use Psr\Log\LoggerInterface;

/**
 * Created by mcfedr on 04/02/2016 09:34
 */
class TestWorker implements Worker
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * TestWorker constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Called to start the queued task
     *
     * @param array $options
     * @throws \Exception
     */
    public function execute(array $options)
    {
        $this->logger->info('execute', ['options' => $options]);
    }
}
