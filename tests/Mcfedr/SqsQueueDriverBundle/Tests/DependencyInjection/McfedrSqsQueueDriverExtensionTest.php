<?php

namespace Mcfedr\SqsQueueDriverBundle\Tests\DependencyInjection;

use Mcfedr\SqsQueueDriverBundle\Manager\SqsQueueManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class McfedrSqsQueueDriverExtensionTest extends WebTestCase
{
    protected function setUp()
    {
        static::bootKernel();
    }

    public function testContainerHasClass()
    {
        $this->assertTrue(static::$kernel->getContainer()->has(SqsQueueManager::class));
    }

    /**
     * @group default_manager
     */
    public function testConfigurationWithDefaultManger()
    {
        if (static::$kernel->getEnvironment() !== \TestKernel::ENV_DEFAULT_MANAGER) {
            $this->markTestSkipped(sprintf('This test can only run with environment "%s"', \TestKernel::ENV_DEFAULT_MANAGER));
        }

        $this->assertTrue(static::$kernel->getContainer()->has('mcfedr_queue_manager.default'));
        $this->assertInstanceOf(SqsQueueManager::class, static::$kernel->getContainer()->get('mcfedr_queue_manager.default'));
    }

    /**
     * @group non_default_manager
     */
    public function testConfigurationNonDefaultManger()
    {
        if (static::$kernel->getEnvironment() !== \TestKernel::ENV_NON_DEFAULT_MANAGER) {
            $this->markTestSkipped(sprintf('This test can only run with environment "%s"', \TestKernel::ENV_NON_DEFAULT_MANAGER));
        }

        $this->assertFalse(static::$kernel->getContainer()->has('mcfedr_queue_manager.default'));
        $this->assertInstanceOf(SqsQueueManager::class, static::$kernel->getContainer()->get('mcfedr_queue_manager.non_default'));
    }

    /**
     * @dataProvider multipleManagerProvider
     *
     * @group multiple_managers
     */
    public function testConfigurationMultipleMangers($manager)
    {
        if (static::$kernel->getEnvironment() !== \TestKernel::ENV_MULTIPLE_MANAGERS) {
            $this->markTestSkipped(sprintf('This test can only run with environment "%s"', \TestKernel::ENV_MULTIPLE_MANAGERS));
        }

        $this->assertInstanceOf(SqsQueueManager::class, static::$kernel->getContainer()->get('mcfedr_queue_manager.'.$manager));
    }

    /**
     * @return array
     */
    public function multipleManagerProvider()
    {
        return [
            ['manager_1'],
            ['manager_2'],
        ];
    }
}
