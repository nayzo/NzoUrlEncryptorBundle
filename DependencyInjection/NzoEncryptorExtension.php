<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakfpro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class NzoEncryptorExtension extends Extension
{
    private const MAX_LENGTH = 100;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $cipherAlgorithm = $config['cipher_algorithm'];
        if (!\in_array($cipherAlgorithm, openssl_get_cipher_methods(true), true)) {
            throw new \InvalidArgumentException("NzoEncryptor: Unknown cipher algorithm {$cipherAlgorithm}");
        }

        if (false === (bool) $config['random_pseudo_bytes'] && empty($config['secret_iv'])) {
            throw new \InvalidArgumentException("NzoEncryptor: 'secret_iv' cannot be empty when 'random_pseudo_bytes' is set to FALSE !");
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $container->setParameter('nzo_encryptor.secret_key', $this->cleanKey($config['secret_key']));
        $container->setParameter('nzo_encryptor.secret_iv', $this->cleanKey($config['secret_iv']));
        $container->setParameter('nzo_encryptor.cipher_algorithm', $cipherAlgorithm);
        $container->setParameter('nzo_encryptor.base64_encode', (bool) $config['base64_encode']);
        $container->setParameter('nzo_encryptor.format_base64_output', (bool) $config['format_base64_output']);
        $container->setParameter('nzo_encryptor.random_pseudo_bytes', (bool) $config['random_pseudo_bytes']);
    }

    private function cleanKey(?string $key = null): string
    {
        if (null === $key || '' === $key || '0' === $key) {
            return '';
        }

        $key = trim($key);
        if (strlen($key) > self::MAX_LENGTH) {
            $key = substr($key, 0, self::MAX_LENGTH);
        }

        return $key;
    }
}
