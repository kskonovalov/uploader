<?php

namespace Konst\UploaderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class KonstUploaderExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter( 'konst_uploader_bundle.file_upload_rules', $config[ 'parameters' ][ 'file_upload_rules' ]);
        $container->setParameter( 'konst_uploader_bundle.servers_list', $config[ 'parameters' ][ 'servers_list' ]);
        $container->setParameter( 'konst_uploader_bundle.upload_path', $config[ 'parameters' ][ 'upload_path' ]);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
