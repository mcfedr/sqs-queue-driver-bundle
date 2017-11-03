<?php

namespace Mcfedr\SqsQueueDriverBundle\Tests\DependencyInjection;

use Mcfedr\SqsQueueDriverBundle\Manager\SqsQueueManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class McfedrSqsQueueDriverExtensionTest extends WebTestCase
{
    public function testConfiguration()
    {
        $client = static::createClient();
        $this->assertTrue($client->getContainer()->has(SqsQueueManager::class));
        $this->assertTrue($client->getContainer()->has('mcfedr_queue_manager.default'));
    }
}
