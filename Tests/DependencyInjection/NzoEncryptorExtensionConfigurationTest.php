<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakfpro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\Tests\DependencyInjection;

use Nzo\UrlEncryptorBundle\Annotations\AnnotationResolver;
use Nzo\UrlEncryptorBundle\DependencyInjection\Compiler\LoadAnnotationService;
use Nzo\UrlEncryptorBundle\DependencyInjection\NzoEncryptorExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NzoEncryptorExtensionConfigurationTest extends TestCase
{
    public function testQueryStringParamsAreDisabledByDefault(): void
    {
        $container = $this->load(['secret_key' => 'encryptionKeyText']);

        $this->assertFalse($container->getParameter('nzo_encryptor.query_string_params'));
    }

    public function testQueryStringParamsCanBeEnabled(): void
    {
        $container = $this->load(['secret_key' => 'encryptionKeyText', 'query_string_params' => true]);

        $this->assertTrue($container->getParameter('nzo_encryptor.query_string_params'));
    }

    public function testResolverReceivesTheQueryStringOption(): void
    {
        $container = $this->load(['secret_key' => 'encryptionKeyText', 'query_string_params' => true]);
        (new LoadAnnotationService())->process($container);

        $definition = $container->getDefinition('nzo.annotation_resolver');

        $this->assertSame(AnnotationResolver::class, $definition->getClass());
        $this->assertSame('%nzo_encryptor.query_string_params%', $definition->getArgument(2));
    }

    public function testDefaultsOfThePreviousVersionAreUnchanged(): void
    {
        $container = $this->load(['secret_key' => 'encryptionKeyText']);

        $this->assertSame('aes-256-ctr', $container->getParameter('nzo_encryptor.cipher_algorithm'));
        $this->assertTrue($container->getParameter('nzo_encryptor.base64_encode'));
        $this->assertTrue($container->getParameter('nzo_encryptor.format_base64_output'));
        $this->assertTrue($container->getParameter('nzo_encryptor.random_pseudo_bytes'));
    }

    private function load(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new NzoEncryptorExtension())->load([$config], $container);

        return $container;
    }
}
