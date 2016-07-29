<?php
/**
 * Created by mcfedr on 03/06/2014 21:50
 */

namespace Mcfedr\SqsQueueDriverBundle\Manager;

use Mcfedr\QueueManagerBundle\Exception\NoSuchJobException;
use Mcfedr\QueueManagerBundle\Exception\WrongJobException;
use Mcfedr\QueueManagerBundle\Manager\QueueManager;
use Mcfedr\QueueManagerBundle\Queue\Job;
use Mcfedr\SqsQueueDriverBundle\Queue\SqsJob;

class SqsQueueManager implements QueueManager
{
    use SqsClientTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $options)
    {
        $this->setOptions($options);
    }

    /**
     * {@inheritdoc}
     */
    public function put($name, array $arguments = [], array $options = [])
    {
        if (array_key_exists('url', $options)) {
            $url = $options['url'];
        } else if (array_key_exists('queue', $options)) {
            $url = $this->queues[$options['queue']];
        } else {
            $url = $this->defaultUrl;
        }

        $sendMessage = [
            'QueueUrl' => $url,
            'MessageBody' => json_encode([
                'name' => $name,
                'arguments' => $arguments
            ])
        ];

        $delay = null;
        if (isset($options['delay'])) {
            $sendMessage['DelaySeconds'] = $delay = $options['delay'];
        }

        $id = null;
        if (!$this->debug) {
            $result = $this->sqs->sendMessage($sendMessage);
            $id = $result['MessageId'];
        }

        return new SqsJob($name, $arguments, $options, $id, $delay, $url);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Job $job)
    {
        if (!$job instanceof SqsJob) {
            throw new WrongJobException('Sqs queue manager can only delete sqs jobs');
        }

        throw new NoSuchJobException('Sqs queue manager cannot delete jobs');
    }
}
