<?php

namespace Mcfedr\SqsQueueDriverBundle\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;

/**
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
class ServiceBasedCommandTest extends KernelTestCase
{
    /**
     * @dataProvider serviceBasedCommandsProvider
     */
    public function testExecute($command, $environment)
    {
        static::bootKernel(['environment' => $environment]);
        $application = new Application(static::$kernel);
        $this->assertInstanceOf(Command::class, $application->find($command));
    }

    /**
     * @return array
     */
    public function serviceBasedCommandsProvider()
    {
        return [
            ['mcfedr:queue:default-runner', \TestKernel::ENV_DEFAULT_MANAGER],
            ['mcfedr:queue:non_default-runner', \TestKernel::ENV_NON_DEFAULT_MANAGER],
            ['mcfedr:queue:manager_1-runner', \TestKernel::ENV_MULTIPLE_MANAGERS],
            ['mcfedr:queue:manager_2-runner', \TestKernel::ENV_MULTIPLE_MANAGERS],
        ];
    }
}
