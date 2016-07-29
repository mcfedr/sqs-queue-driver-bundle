<?php
/**
 * Created by mcfedr on 05/03/2016 15:45
 */

namespace Mcfedr\SqsQueueDriverBundle\Command;

use Mcfedr\QueueManagerBundle\Command\RunnerCommand;
use Mcfedr\QueueManagerBundle\Exception\UnexpectedJobDataException;
use Mcfedr\QueueManagerBundle\Manager\QueueManager;
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

    protected function getJobs()
    {
        if ($this->debug) {
            return [];
        }

        $waitTime = count($this->urls) ? 0 : 20;
        foreach ($this->urls as $url) {
            $jobs = $this->getJobsFromUrl($url, $waitTime);
            if (count($jobs)) {
                return $jobs;
            }
        }

        return [];
    }

    private function getJobsFromUrl($url, $waitTime)
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $url,
            'WaitTimeSeconds' => $waitTime,
            'VisibilityTimeout' => $this->visibilityTimeout,
            'MaxNumberOfMessages' => 20
        ]);

        if (isset($response['Messages'])) {
            return array_map(function($message) use($url) {
                $data = json_decode($message['Body'], true);
                if (!(isset($data['name']) && isset($data['arguments']))) {
                    throw new UnexpectedJobDataException();
                }
                return new SqsJob($data['name'], $data['arguments'], [], $message['MessageId'], 0, $url, $message['ReceiptHandle']);
            }, $response['Messages']);
        }

        return [];
    }

    protected function finishJobs(array $okJobs, array $retryJobs, array $failedJobs)
    {
        if ($this->debug) {
            return;
        }

        //$retryJobs are not deleted and will be recycled by sqs

        /** @var SqsJob $job */
        foreach ($okJobs as $job) {
            $this->sqs->deleteMessage([
                'QueueUrl' => $job->getUrl(),
                'ReceiptHandle' => $job->getReceiptHandle()
            ]);
        }

        /** @var SqsJob $job */
        foreach ($failedJobs as $job) {
            $this->sqs->deleteMessage([
                'QueueUrl' => $job->getUrl(),
                'ReceiptHandle' => $job->getReceiptHandle()
            ]);
        }
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
