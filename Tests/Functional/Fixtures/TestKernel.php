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

namespace Nzo\UrlEncryptorBundle\Tests\Functional\Fixtures;

use Nzo\UrlEncryptorBundle\NzoUrlEncryptorBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @var array<string, mixed>
     */
    private array $encryptorConfig;

    /**
     * @param array<string, mixed> $encryptorConfig
     */
    public function __construct(array $encryptorConfig)
    {
        parent::__construct('test', false);

        $this->encryptorConfig = $encryptorConfig;
    }

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new NzoUrlEncryptorBundle()];
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/nzo_url_encryptor/' . md5(serialize($this->encryptorConfig)) . '/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/nzo_url_encryptor/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
        ]);
        $container->extension('nzo_encryptor', $this->encryptorConfig);

        $services = $container->services();
        $services->set('logger', NullLogger::class);
        $services->set(ItemController::class)->public()->tag('controller.service_arguments');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('item_show', '/item/{id}')->controller([ItemController::class, 'show']);
        $routes->add('item_invoice', '/invoice/{id}')->controller([ItemController::class, 'invoice']);
        $routes->add('item_plain', '/plain')->controller([ItemController::class, 'plain']);
        $routes->add('item_search', '/search')->controller([ItemController::class, 'search']);
        $routes->add('item_link', '/link/{id}')->controller([ItemController::class, 'link']);
    }
}
