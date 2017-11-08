<?php


class TestKernel extends Symfony\Component\HttpKernel\Kernel
{
    const ENV_DEFAULT_MANAGER = 'test';
    const ENV_NON_DEFAULT_MANAGER = 'non_default_manager';
    const ENV_MULTIPLE_MANAGERS = 'multiple_managers';

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Mcfedr\QueueManagerBundle\McfedrQueueManagerBundle(),
            new Mcfedr\SqsQueueDriverBundle\McfedrSqsQueueDriverBundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(\Symfony\Component\Config\Loader\LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config_'.$this->getEnvironment().'.yml');
    }
}
