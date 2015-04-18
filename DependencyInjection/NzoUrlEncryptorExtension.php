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
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

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

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $secret = $config['secret'];

        if (strlen($secret) < 24) {
            $secret = str_pad($secret, 24, "\0", STR_PAD_RIGHT);
        } else {
            $secret = substr($secret, 0, 24);
        }

        $container->setParameter('nzo_url_encryptor.secret_key', $secret);
    }
}
