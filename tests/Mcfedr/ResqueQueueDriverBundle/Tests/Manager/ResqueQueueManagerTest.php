<?php
/**
 * Created by mcfedr on 04/02/2016 10:22
 */

namespace Mcfedr\SqsQueueDriverBundle\Tests\Manager;

use Mcfedr\QueueManagerBundle\Exception\NoSuchJobException;
use Mcfedr\QueueManagerBundle\Exception\WrongJobException;
use Mcfedr\SqsQueueDriverBundle\Manager\SqsQueueManager;
use Mcfedr\SqsQueueDriverBundle\Queue\SqsJob;
use Mcfedr\SqsQueueDriverBundle\Resque\Job;

class ResqueQueueManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var SqsQueueManager */
    protected $manager;

    public function setUp()
    {
        $this->manager = new SqsQueueManager([
            'host' => '127.0.0.1',
            'port' => 6379,
            'default_queue' => 'default',
            'kernel_options' => [
                'kernel.root_dir' => __DIR__,
                'kernel.environment' => 'test',
                'kernel.debug'=> true
            ],
            'debug' => false,
            'prefix' => 'tests:',
            'track_status' => false
        ]);
    }

    /**
     * @dataProvider getValues
     */
    public function testPutFuture($name, $options, $queue, $when)
    {
        $value = $this->manager->put($name, $options, [
            'queue' => $queue,
            'when' => new \DateTime($when)
        ]);
        $this->assertInstanceOf(SqsJob::class, $value);
        $this->assertTrue($value->isFutureJob());
        $this->assertNull($value->getId());
        $this->assertEquals(Job::class, $value->getClass());
    }

    /**
     * @dataProvider getValues
     */
    public function testPutNow($name, $options, $queue, $when)
    {
        $value = $this->manager->put($name, $options, [
            'queue' => $queue
        ]);
        $this->assertInstanceOf(SqsJob::class, $value);
        $this->assertFalse($value->isFutureJob());
        $this->assertInternalType('string', $value->getId());
        $this->assertEquals(Job::class, $value->getClass());
        $this->assertEquals('../../../../tests/Mcfedr/SqsQueueDriverBundle/Tests/Manager/', $value->getResqueArguments()['kernel_options']['kernel.root_dir']);
    }

    /**
     * @dataProvider getValues
     */
    public function testDelete($name, $options, $queue, $when)
    {
        $job = $this->manager->put($name, $options, [
            'queue' => $queue,
            'when' => (new \DateTime($when))->add(new \DateInterval('P1M'))
        ]);
        $this->manager->delete($job);

        $this->setExpectedException(NoSuchJobException::class);
        $this->manager->delete($job);
    }

    /**
     * @dataProvider getValues
     */
    public function testDeleteNow($name, $options, $queue, $when)
    {
        $job = $this->manager->put($name, $options);

        $this->setExpectedException(WrongJobException::class);
        $this->manager->delete($job);
    }

    public function getValues()
    {
        return [
            ['test', [], 'default', 'next TUE 11:00'],
            ['test1', [], 'default', 'next WED 21:00']
        ];
    }
}
