<?php

namespace Nzo\UrlEncryptorBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * NzoUrlEncryptorExtension.
 *
 * @author Ala Eddine Khefifi <alakhefifi@gmail.com>
 * Website   www.alakhefifi.com
 */
class NzoUrlEncryptorExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        
        $secret = $config['secret'];
        if (!isset($config['secret'])) {
            throw new \InvalidArgumentException('The "secret" option must be set');
        }
        else if (strlen($secret) > 8) {
            $secret = substr($secret, 0, 8);
        }

        $container->setParameter('nzo_url_encryptor.secret_key', $secret);
        }
}
