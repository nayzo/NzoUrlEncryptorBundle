<?php

/*
 * NzoUrlEncryptorExtension file.
 *
 * (c) Ala Eddine Khefifi <alakhefifi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class NzoUrlEncryptorExtension
 * @package Nzo\UrlEncryptorBundle\DependencyInjection
 */
class NzoUrlEncryptorExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $secret = $config['secret'];
        if (!isset($config['secret'])) {
            throw new \InvalidArgumentException('The "secret" option must be set');
        } else if (strlen($secret) > 24) {
            $secret = substr($secret, 0, 24);
        }

        $container->setParameter('nzo_url_encryptor.secret_key', $secret);
    }
}
