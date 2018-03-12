<?php

namespace Mcfedr\SqsQueueDriverBundle\Manager;

use Aws\Sqs\SqsClient;

/**
 * @internal
 */
trait SqsClientTrait
{
    /**
     * @var string
     */
    private $defaultUrl;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var SqsClient
     */
    private $sqs;

    /**
     * @var array
     */
    private $sqsOptions;

    /**
     * @var string[]
     */
    private $queues;

    private function setOptions(array $options)
    {
        $this->defaultUrl = $options['default_url'];
        $this->debug = $options['debug'];
        $this->queues = $options['queues'];
        if (!array_key_exists('default', $this->queues)) {
            $this->queues['default'] = $this->defaultUrl;
        }
        if (!$this->debug) {
            $sqsOptions = [
                'region' => $options['region'],
                'version' => '2012-11-05',
            ];
            if (array_key_exists('credentials', $options)) {
                $sqsOptions['credentials'] = $options['credentials'];
            }
            $this->sqsOptions = $sqsOptions;
        }
    }

    private function getSqs()
    {
        if (!$this->sqs) {
            $this->sqs = new SqsClient($this->sqsOptions);
        }

        return $this->sqs;
    }
}
