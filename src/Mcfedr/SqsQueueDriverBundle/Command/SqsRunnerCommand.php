<?php
/**
 * Created by mcfedr on 05/03/2016 15:45
 */

namespace Mcfedr\SqsQueueDriverBundle\Command;

use Mcfedr\QueueManagerBundle\Command\RunnerCommand;
use Mcfedr\SqsQueueDriverBundle\Manager\SqsClientTrait;
use Mcfedr\SqsQueueDriverBundle\Queue\SqsJob;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class SqsRunnerCommand extends RunnerCommand
{
    use SqsClientTrait;

    private $visibilityTimeout = 30;

    public function __construct($name, array $options)
    {
        parent::__construct($name, $options);
        $this->setOptions($options);
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'The url of SQS queue to run')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'The visibility timeout for SQS');
    }

    protected function getJob()
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $this->defaultUrl,
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
            return new SqsJob($data['name'], $data['arguments'], [], $message['MessageId'], 0, $this->defaultUrl);
        }
        
        return null;
    }

    protected function setInput(InputInterface $input)
    {
        if (($url = $input->getOption('url'))) {
            $this->defaultUrl = $url;
        }

        if (($timout = $input->getOption('timeout'))) {
            $this->visibilityTimeout = $timout;
        }
    }
}
