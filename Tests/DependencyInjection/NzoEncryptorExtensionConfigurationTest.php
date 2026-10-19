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

namespace Nzo\UrlEncryptorBundle\Tests\DependencyInjection;

use Nzo\UrlEncryptorBundle\Annotations\AnnotationResolver;
use Nzo\UrlEncryptorBundle\DependencyInjection\NzoEncryptorExtension;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Nzo\UrlEncryptorBundle\Twig\EncryptorExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NzoEncryptorExtensionConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $container = $this->load(['secret_key' => 'encryptionKeyText']);

        $this->assertSame('encryptionKeyText', $container->getParameter('nzo_encryptor.secret_key'));
        $this->assertSame([], $container->getParameter('nzo_encryptor.previous_secret_keys'));
        $this->assertSame('', $container->getParameter('nzo_encryptor.secret_iv'));
        $this->assertSame('aes-256-ctr', $container->getParameter('nzo_encryptor.cipher_algorithm'));
        $this->assertTrue($container->getParameter('nzo_encryptor.base64_encode'));
        $this->assertTrue($container->getParameter('nzo_encryptor.format_base64_output'));
        $this->assertTrue($container->getParameter('nzo_encryptor.random_pseudo_bytes'));
        $this->assertFalse($container->getParameter('nzo_encryptor.query_string_params'));
        $this->assertFalse($container->getParameter('nzo_encryptor.legacy_decryption'));
    }

    public function testEveryOptionCanBeSet(): void
    {
        $container = $this->load([
            'secret_key' => ' encryptionKeyText ',
            'previous_secret_keys' => ['oldKey', ' olderKey '],
            'secret_iv' => 'encryptionIvText',
            'cipher_algorithm' => 'aes-256-cbc',
            'base64_encode' => false,
            'format_base64_output' => false,
            'random_pseudo_bytes' => false,
            'query_string_params' => true,
            'legacy_decryption' => true,
        ]);

        $this->assertSame('encryptionKeyText', $container->getParameter('nzo_encryptor.secret_key'));
        $this->assertSame(['oldKey', 'olderKey'], $container->getParameter('nzo_encryptor.previous_secret_keys'));
        $this->assertSame('encryptionIvText', $container->getParameter('nzo_encryptor.secret_iv'));
        $this->assertSame('aes-256-cbc', $container->getParameter('nzo_encryptor.cipher_algorithm'));
        $this->assertFalse($container->getParameter('nzo_encryptor.base64_encode'));
        $this->assertFalse($container->getParameter('nzo_encryptor.format_base64_output'));
        $this->assertFalse($container->getParameter('nzo_encryptor.random_pseudo_bytes'));
        $this->assertTrue($container->getParameter('nzo_encryptor.query_string_params'));
        $this->assertTrue($container->getParameter('nzo_encryptor.legacy_decryption'));
    }

    public function testKeyLongerThanTheLimitIsShortened(): void
    {
        $container = $this->load(['secret_key' => str_repeat('k', 150), 'previous_secret_keys' => [str_repeat('p', 150)]]);

        $this->assertSame(str_repeat('k', 100), $container->getParameter('nzo_encryptor.secret_key'));
        $this->assertSame([str_repeat('p', 100)], $container->getParameter('nzo_encryptor.previous_secret_keys'));
    }

    public function testKeyMadeOfDigitsIsReadAsAString(): void
    {
        $container = $this->load(['secret_key' => 1234567890, 'previous_secret_keys' => [987654321], 'secret_iv' => 42]);

        $this->assertSame('1234567890', $container->getParameter('nzo_encryptor.secret_key'));
        $this->assertSame(['987654321'], $container->getParameter('nzo_encryptor.previous_secret_keys'));
        $this->assertSame('42', $container->getParameter('nzo_encryptor.secret_iv'));
    }

    public function testServicesAreRegistered(): void
    {
        $container = $this->load(['secret_key' => 'encryptionKeyText']);

        $this->assertSame(Encryptor::class, $container->getDefinition('nzo_encryptor')->getClass());
        $this->assertSame('nzo_encryptor', (string) $container->getAlias(Encryptor::class));

        $resolver = $container->getDefinition('nzo.annotation_resolver');
        $this->assertSame(AnnotationResolver::class, $resolver->getClass());
        $this->assertSame('nzo_encryptor', (string) $resolver->getArgument(0));
        $this->assertSame([['event' => 'kernel.controller', 'method' => 'onKernelController']], $resolver->getTag('kernel.event_listener'));

        $twigExtension = $container->getDefinition('nzo.twig.encryptor_extension');
        $this->assertSame(EncryptorExtension::class, $twigExtension->getClass());
        $this->assertTrue($twigExtension->hasTag('twig.extension'));
    }

    public function testEncryptorServiceIsBuiltFromTheConfiguration(): void
    {
        $container = $this->load([
            'secret_key' => 'newKey',
            'previous_secret_keys' => ['oldKey'],
            'secret_iv' => 'encryptionIvText',
            'random_pseudo_bytes' => false,
        ]);
        $container->getDefinition('nzo_encryptor')->setPublic(true);
        $container->compile();

        $encryptor = $container->get('nzo_encryptor');

        $this->assertInstanceOf(Encryptor::class, $encryptor);
        $this->assertSame($encryptor->encrypt('plain_text'), $encryptor->encrypt('plain_text'));
        $this->assertSame('plain_text', $encryptor->decrypt((new Encryptor('oldKey'))->encrypt('plain_text')));
        $this->assertSame('plain_text', (new Encryptor('newKey'))->decrypt($encryptor->encrypt('plain_text')));
    }

    public function testSecretKeyIsRequired(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load([]);
    }

    /**
     * @dataProvider provideKeysThatAreEmptyOnceCleaned
     *
     * @param array<string, mixed> $config
     */
    public function testKeyThatIsEmptyOnceCleanedIsRefused(array $config): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot hold an empty key');

        $this->load($config);
    }

    public function provideKeysThatAreEmptyOnceCleaned(): \Iterator
    {
        yield 'spaces' => [['secret_key' => '   ']];
        yield 'zero' => [['secret_key' => '0']];
        yield 'previous key' => [['secret_key' => 'encryptionKeyText', 'previous_secret_keys' => ['   ']]];
    }

    public function testPreviousSecretKeyCannotBeEmpty(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load(['secret_key' => 'encryptionKeyText', 'previous_secret_keys' => ['']]);
    }

    public function testSecretIvIsRequiredWhenRandomPseudoBytesIsDisabled(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("'secret_iv' cannot be empty");

        $this->load(['secret_key' => 'encryptionKeyText', 'random_pseudo_bytes' => false]);
    }

    public function testUnknownCipherAlgorithmIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown cipher algorithm not-a-cipher');

        $this->load(['secret_key' => 'encryptionKeyText', 'cipher_algorithm' => 'not-a-cipher']);
    }

    /**
     * @dataProvider provideUnsupportedCipherAlgorithms
     */
    public function testUnsupportedCipherAlgorithmIsRefused(string $cipherAlgorithm): void
    {
        if (!\in_array($cipherAlgorithm, openssl_get_cipher_methods(true), true)) {
            $this->markTestSkipped(sprintf('The cipher algorithm "%s" is not available.', $cipherAlgorithm));
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The cipher algorithm "%s" is not supported', $cipherAlgorithm));

        $this->load(['secret_key' => 'encryptionKeyText', 'cipher_algorithm' => $cipherAlgorithm]);
    }

    public function provideUnsupportedCipherAlgorithms(): \Iterator
    {
        yield ['aes-256-gcm'];
        yield ['aes-256-ccm'];
        yield ['aes-256-ocb'];
        yield ['chacha20-poly1305'];
        yield ['aes-256-xts'];
        yield ['id-aes256-wrap'];
    }

    /**
     * @dataProvider provideSupportedCipherAlgorithms
     */
    public function testSupportedCipherAlgorithmIsAccepted(string $cipherAlgorithm): void
    {
        $container = $this->load(['secret_key' => 'encryptionKeyText', 'cipher_algorithm' => $cipherAlgorithm]);

        $this->assertSame($cipherAlgorithm, $container->getParameter('nzo_encryptor.cipher_algorithm'));
    }

    public function provideSupportedCipherAlgorithms(): \Iterator
    {
        yield ['aes-256-ctr'];
        yield ['aes-256-cbc'];
        yield ['aes-128-cbc'];
        yield ['aes-256-cfb'];
        yield ['AES-256-CBC'];
    }

    private function load(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new NzoEncryptorExtension())->load([$config], $container);

        return $container;
    }
}
