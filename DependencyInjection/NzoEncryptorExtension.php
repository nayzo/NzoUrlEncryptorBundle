<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakfpro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Nzo\UrlEncryptorBundle\DependencyInjection;

use Nzo\UrlEncryptorBundle\Annotations\AnnotationResolver;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Nzo\UrlEncryptorBundle\Exception\DecryptionException;
use Nzo\UrlEncryptorBundle\Exception\ExceptionInterface;
use Nzo\UrlEncryptorBundle\Twig\EncryptorExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Extension\AbstractExtension;

class NzoEncryptorExtension extends Extension
{
    private const MAX_LENGTH = 100;

    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{
         *     secret_key: scalar,
         *     previous_secret_keys: array<scalar>,
         *     secret_iv: scalar|null,
         *     cipher_algorithm: string,
         *     base64_encode: bool,
         *     format_base64_output: bool,
         *     random_pseudo_bytes: bool,
         *     query_string_params: bool,
         *     legacy_decryption: bool,
         * } $config
         */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $cipherAlgorithm = $config['cipher_algorithm'];
        if (!\in_array($cipherAlgorithm, openssl_get_cipher_methods(true), true)) {
            throw new \InvalidArgumentException("NzoEncryptor: Unknown cipher algorithm {$cipherAlgorithm}");
        }

        $this->assertCipherAlgorithmIsSupported($cipherAlgorithm);

        if (false === $config['random_pseudo_bytes'] && empty($config['secret_iv'])) {
            throw new \InvalidArgumentException("NzoEncryptor: 'secret_iv' cannot be empty when 'random_pseudo_bytes' is set to FALSE !");
        }

        // A key made of digits only is read as a number from a YAML file
        $cleanKey = fn (mixed $key): string => $this->cleanKey($container, \is_scalar($key) ? (string) $key : null);

        $secretKey = $cleanKey($config['secret_key']);
        $previousSecretKeys = array_map($cleanKey, array_values($config['previous_secret_keys']));
        if (\in_array('', [$secretKey, ...$previousSecretKeys], true)) {
            throw new \InvalidArgumentException("NzoEncryptor: 'secret_key' and 'previous_secret_keys' cannot hold an empty key.");
        }

        $container->setParameter('nzo_encryptor.secret_key', $secretKey);
        $container->setParameter('nzo_encryptor.previous_secret_keys', $previousSecretKeys);
        $container->setParameter('nzo_encryptor.secret_iv', $cleanKey($config['secret_iv']));
        $container->setParameter('nzo_encryptor.cipher_algorithm', $cipherAlgorithm);
        $container->setParameter('nzo_encryptor.base64_encode', $config['base64_encode']);
        $container->setParameter('nzo_encryptor.format_base64_output', $config['format_base64_output']);
        $container->setParameter('nzo_encryptor.random_pseudo_bytes', $config['random_pseudo_bytes']);
        $container->setParameter('nzo_encryptor.query_string_params', $config['query_string_params']);
        $container->setParameter('nzo_encryptor.legacy_decryption', $config['legacy_decryption']);

        $container
            ->register('nzo_encryptor', Encryptor::class)
            ->setArguments([
                '%nzo_encryptor.secret_key%',
                '%nzo_encryptor.cipher_algorithm%',
                '%nzo_encryptor.base64_encode%',
                '%nzo_encryptor.format_base64_output%',
                '%nzo_encryptor.random_pseudo_bytes%',
                '%nzo_encryptor.previous_secret_keys%',
                '%nzo_encryptor.legacy_decryption%',
            ])
            ->addMethodCall('setSecretIv', ['%nzo_encryptor.secret_iv%'])
        ;
        $container->setAlias(Encryptor::class, 'nzo_encryptor');

        $container
            ->register('nzo.annotation_resolver', AnnotationResolver::class)
            ->setArguments([new Reference('nzo_encryptor'), '%nzo_encryptor.query_string_params%'])
            ->addTag('kernel.event_listener', ['event' => KernelEvents::CONTROLLER, 'method' => 'onKernelController'])
        ;

        if (class_exists(AbstractExtension::class)) {
            $container
                ->register('nzo.twig.encryptor_extension', EncryptorExtension::class)
                ->setArguments([new Reference('nzo_encryptor')])
                ->addTag('twig.extension')
            ;
        }
    }

    /**
     * Encrypts a few values of different lengths: the modes that need more than a key and an IV fail on some of them.
     */
    private function assertCipherAlgorithmIsSupported(string $cipherAlgorithm): void
    {
        $encryptor = new Encryptor('cipher-algorithm-check', $cipherAlgorithm);

        try {
            foreach (['', 'a', str_repeat('a', 40)] as $plainText) {
                // AEAD modes raise a warning on top of failing: the exception below says it better
                $readBack = $encryptor->decrypt(@$encryptor->encrypt($plainText));
                if ($plainText !== $readBack) {
                    throw new DecryptionException();
                }
            }
        } catch (ExceptionInterface $e) {
            throw new \InvalidArgumentException(sprintf('NzoEncryptor: The cipher algorithm "%s" is not supported. Use one that only needs a key and an IV, such as "aes-256-ctr" or "aes-256-cbc". The AEAD modes (GCM, CCM, OCB, ChaCha20-Poly1305) are not: the bundle already authenticates every value.', $cipherAlgorithm), 0, $e);
        }
    }

    private function cleanKey(ContainerBuilder $container, ?string $key): string
    {
        if (null === $key || '' === $key || '0' === $key) {
            return '';
        }

        // A value read from the environment is only known at runtime: what is here is its placeholder, and
        // shortening it would break it
        $usedEnvs = [];
        $container->resolveEnvPlaceholders($key, null, $usedEnvs);
        if ([] !== $usedEnvs) {
            return $key;
        }

        $key = trim($key);
        if (\strlen($key) > self::MAX_LENGTH) {
            $key = substr($key, 0, self::MAX_LENGTH);
        }

        return $key;
    }
}
