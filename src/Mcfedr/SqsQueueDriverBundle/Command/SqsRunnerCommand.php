<?php
/**
 * Created by mcfedr on 05/03/2016 15:45
 */

namespace Mcfedr\SqsQueueDriverBundle\Command;

use Mcfedr\QueueManagerBundle\Command\RunnerCommand;
use Mcfedr\QueueManagerBundle\Exception\UnexpectedJobDataException;
use Mcfedr\QueueManagerBundle\Exception\UnrecoverableJobException;
use Mcfedr\QueueManagerBundle\Exception\WrongJobException;
use Mcfedr\QueueManagerBundle\Manager\QueueManager;
use Mcfedr\QueueManagerBundle\Queue\Job;
use Mcfedr\SqsQueueDriverBundle\Manager\SqsClientTrait;
use Mcfedr\SqsQueueDriverBundle\Queue\SqsJob;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class SqsRunnerCommand extends RunnerCommand
{
    use SqsClientTrait;

    private $visibilityTimeout = 30;

    /**
     * @var string[]
     */
    private $urls;

    public function __construct($name, array $options, QueueManager $queueManager)
    {
        parent::__construct($name, $options, $queueManager);
        $this->setOptions($options);
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'The url of SQS queue to run, can be a comma separated list')
            ->addOption('queue', null, InputOption::VALUE_REQUIRED, 'The name of a queue in the config, can be a comma separated list')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'The visibility timeout for SQS');
    }

    protected function getJob()
    {
        if ($this->debug) {
            return null;
        }

        foreach ($this->urls as $url) {
            $job = $this->getJobFromUrl($url);
            if ($job) {
                return $job;
            }
        }

        return null;
    }

    private function getJobFromUrl($url)
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $url,
            'WaitTimeSeconds' => 20,
            'VisibilityTimeout' => $this->visibilityTimeout,
            'MaxNumberOfMessages' => 1
        ]);

        if (isset($response['Messages']) && count($response['Messages'])) {
            $message = $response['Messages'][0];
            $data = json_decode($message['Body'], true);
            if (!(isset($data['name']) && isset($data['arguments']))) {
                throw new UnexpectedJobDataException();
            }
            return new SqsJob($data['name'], $data['arguments'], [], $message['MessageId'], 0, $url, $message['ReceiptHandle']);
        }
    }

    protected function finishJob(Job $job)
    {
        if (!$job instanceof SqsJob) {
            throw new WrongJobException('Sqs runner should only finish sqs jobs');
        }

        if ($this->debug) {
            return;
        }

        $this->sqs->deleteMessage([
            'QueueUrl' => $job->getUrl(),
            'ReceiptHandle' => $job->getReceiptHandle()
        ]);
    }

    protected function failedJob(Job $job, \Exception $exception)
    {
        if (!$job instanceof SqsJob) {
            throw new WrongJobException('Sqs runner should only fail sqs jobs');
        }

        if ($job->isRetrying()) {
            return;
        }

        $this->finishJob($job);
    }

    protected function handleInput(InputInterface $input)
    {
        if (($url = $input->getOption('url'))) {
            $this->urls = explode(',', $url);
        } else if (($queue = $input->getOption('queue'))) {
            $this->urls = array_map(function($queue) {
                return $this->queues[$queue];
            }, explode(',', $queue));
        } else {
            $this->urls = [$this->defaultUrl];
        }

        if (($timeout = $input->getOption('timeout'))) {
            $this->visibilityTimeout = $timeout;
        }
    }
}
