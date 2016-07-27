<?php
/**
 * Created by mcfedr on 04/02/2016 10:22
 */

namespace Mcfedr\SqsQueueDriverBundle\Tests\Manager;

use Mcfedr\SqsQueueDriverBundle\Manager\SqsQueueManager;
use Mcfedr\SqsQueueDriverBundle\Queue\SqsJob;

class SqsQueueManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var SqsQueueManager */
    protected $manager;

    public function setUp()
    {
        $this->manager = new SqsQueueManager([
            'default_url' => 'http://sqs.com',
            'region' => 'eu-west-1',
            'debug' => true
        ]);
    }

    public function testPut()
    {
        $job = $this->manager->put('test_worker');

        $this->assertEquals('test_worker', $job->getName());
    }

    /**
     * @expectedException \Mcfedr\QueueManagerBundle\Exception\WrongJobException
     */
    public function testDelete()
    {
        $this->manager->delete(new SqsJob('test_worker', [], [], null, 0, 'url'));
    }
}
