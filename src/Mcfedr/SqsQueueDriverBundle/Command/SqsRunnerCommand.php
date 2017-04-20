<?php

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

    /**
     * @var int
     */
    private $visibilityTimeout = 30;

    /**
     * @var int
     */
    private $batchSize = 10;

    /**
     * @var int
     */
    private $waitTime = 20;

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
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'The visibility timeout for SQS')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Number of messages to fetch at once', 10)
            ->addOption('wait-time', null, InputOption::VALUE_OPTIONAL, 'Wait time between 0-20 seconds');
    }

    protected function getJobs()
    {
        if ($this->debug) {
            return [];
        }

        foreach ($this->urls as $url) {
            $jobs = $this->getJobsFromUrl($url);
            if (count($jobs)) {
                return $jobs;
            }
        }

        return [];
    }

    private function getJobsFromUrl($url)
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $url,
            'WaitTimeSeconds' => $this->waitTime,
            'VisibilityTimeout' => $this->visibilityTimeout,
            'MaxNumberOfMessages' => $this->batchSize
        ]);

        if (isset($response['Messages'])) {
            $jobs = [];
            $exception = null;
            $toDelete = [];

            /** @var array $message */
            foreach ($response['Messages'] as $message) {
                $data = json_decode($message['Body'], true);
                if (!is_array($data) || !isset($data['name']) || !isset($data['arguments']) || !isset($data['retryCount'])) {
                    $toDelete[] = $message['ReceiptHandle'];
                    $exception = new UnexpectedJobDataException('Sqs message(s) missing data fields name, arguments and retryCount');
                    continue;
                }

                $jobs[] = new SqsJob($data['name'], $data['arguments'], 0, $url, $message['MessageId'], $data['retryCount'], $message['ReceiptHandle']);
            }

            if (count($toDelete)) {
                $count = 0;
                $this->sqs->deleteMessageBatch([
                    'QueueUrl' => $url,
                    'Entries' => array_map(function ($handle) use (&$count) {
                        ++$count;

                        return [
                            'Id' => "E{$count}",
                            'ReceiptHandle' => $handle
                        ];
                    }, $toDelete)
                ]);
            }

            if ($exception) {
                if (count($jobs)) {
                    $this->logger && $this->logger->warning('Found unexpected job data in the queue', [
                        'message' => $exception->getMessage()
                    ]);
                } else {
                    throw $exception;
                }
            }

            return $jobs;
        }

        return [];
    }

    protected function finishJobs(array $okJobs, array $retryJobs, array $failedJobs)
    {
        if ($this->debug) {
            return;
        }

        if (count($retryJobs)) {
            $count = 0;
            $this->sqs->sendMessageBatch([
                'QueueUrl' => $retryJobs[0]->getUrl(),
                'Entries' => array_map(function (SqsJob $job) use (&$count) {
                    ++$count;
                    $job->incrementRetryCount();

                    return [
                        'Id' => "R{$count}",
                        'MessageBody' => $job->getMessageBody(),
                        'DelaySeconds' => min($this->getRetryDelaySeconds($job->getRetryCount()), 900) //900 is the max delay
                    ];
                }, $retryJobs)
            ]);
        }

        /** @var SqsJob[] $allJobs */
        $allJobs = array_merge($okJobs, $retryJobs, $failedJobs);
        if (count($allJobs)) {
            $count = 0;
            $this->sqs->deleteMessageBatch([
                'QueueUrl' => $allJobs[0]->getUrl(),
                'Entries' => array_map(function (SqsJob $job) use (&$count) {
                    ++$count;

                    return [
                        'Id' => "J{$count}",
                        'ReceiptHandle' => $job->getReceiptHandle()
                    ];
                }, array_merge($okJobs, $retryJobs, $failedJobs))
            ]);
        }
    }

    protected function handleInput(InputInterface $input)
    {
        if (($url = $input->getOption('url'))) {
            $this->urls = explode(',', $url);
        } elseif (($queue = $input->getOption('queue'))) {
            $this->urls = array_map(function ($queue) {
                return $this->queues[$queue];
            }, explode(',', $queue));
        } else {
            $this->urls = [$this->defaultUrl];
        }

        if (count($this->urls) > 1) {
            $this->waitTime = 0;
        }

        if (($timeout = $input->getOption('timeout'))) {
            $this->visibilityTimeout = (int) $timeout;
        }

        if (($batch = $input->getOption('batch-size'))) {
            $this->batchSize = (int) $batch;
        }

        if (($waitTime = $input->getOption('wait-time'))) {
            $this->waitTime = (int) $waitTime;
        }
    }
}
