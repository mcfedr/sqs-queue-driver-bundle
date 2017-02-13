<?php

namespace Mcfedr\SqsQueueDriverBundle\DependencyInjection;

use Mcfedr\SqsQueueDriverBundle\Command\SqsRunnerCommand;
use Mcfedr\SqsQueueDriverBundle\Manager\SqsQueueManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class McfedrSqsQueueDriverExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
    }

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        // get all Bundles
        $bundles = $container->getParameter('kernel.bundles');
        // determine if McfedrQueueManagerBundle is registered
        if (isset($bundles['McfedrQueueManagerBundle'])) {
            $container->prependExtensionConfig('mcfedr_queue_manager', [
                'drivers' => [
                    'sqs' => [
                        'class' => SqsQueueManager::class,
                        'options' => [
                            'queues' => []
                        ],
                        'command_class' => SqsRunnerCommand::class
                    ]
                ]
            ]);
        }
    }
}
