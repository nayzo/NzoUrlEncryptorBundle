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
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class NzoUrlEncryptorExtension
 * @package Nzo\UrlEncryptorBundle\DependencyInjection
 */
class NzoUrlEncryptorExtension extends Extension
{
    const MAX_LENGTH = 100;

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('nzo_url_encryptor.secret_key', $this->cleanKey($config['secret_key']));
        $container->setParameter('nzo_url_encryptor.secret_iv', $this->cleanKey($config['secret_iv']));
        $container->setParameter('nzo_url_encryptor.cipher_algorithm', $config['cipher_algorithm']);
    }

    /**
     * @param string $key
     * @return string
     */
    private function cleanKey($key)
    {
        if ('' !== $key) {
            $key = trim($key);
            if (strlen($key) > self::MAX_LENGTH) {
                $key = substr($key, 0, self::MAX_LENGTH);
            }
        }

        return $key;
    }
}
