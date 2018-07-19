<?php

namespace Mcfedr\SqsQueueDriverBundle\Queue;

use Mcfedr\QueueManagerBundle\Queue\AbstractRetryableJob;

class SqsJob extends AbstractRetryableJob
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $delay;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $receiptHandle;

    /**
     * @var int
     */
    private $visibilityTimeout;

    /**
     * @param string $name
     * @param array  $arguments
     * @param string $id
     * @param int    $delay
     * @param string $url
     * @param int    $retryCount
     * @param string $receiptHandle
     * @param int    $visibilityTimeout
     */
    public function __construct($name, $arguments, $delay, $url, $id = null, $retryCount = 0, $receiptHandle = null, $visibilityTimeout = null)
    {
        parent::__construct($name, $arguments, $retryCount);
        $this->id = $id;
        $this->delay = $delay;
        $this->url = $url;
        $this->receiptHandle = $receiptHandle;
        $this->visibilityTimeout = $visibilityTimeout;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return SqsJob
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return int
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * @return string
     */
    public function getReceiptHandle()
    {
        return $this->receiptHandle;
    }

    /**
     * @return int|null
     */
    public function getVisibilityTimeout()
    {
        return $this->visibilityTimeout;
    }

    public function getMessageBody()
    {
        return json_encode([
            'name' => $this->getName(),
            'arguments' => $this->getArguments(),
            'retryCount' => $this->getRetryCount(),
            'visibilityTimeout' => $this->getVisibilityTimeout(),
        ]);
    }
}
