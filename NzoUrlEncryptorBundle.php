<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakfpro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle;

use Nzo\UrlEncryptorBundle\DependencyInjection\Compiler\LoadAnnotationService;
use Nzo\UrlEncryptorBundle\DependencyInjection\NzoEncryptorExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NzoUrlEncryptorBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new NzoEncryptorExtension();
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new LoadAnnotationService());
    }
}
