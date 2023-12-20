<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakfpro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\DependencyInjection\Compiler;

use Nzo\UrlEncryptorBundle\Annotations\AnnotationResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LoadAnnotationService implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container
            ->register('nzo.annotation_resolver', AnnotationResolver::class)
            ->addArgument($container->getDefinition('nzo_encryptor'))
            ->addArgument($container->has('annotations.reader') ? $container->getDefinition('annotations.reader') : null)
            ->addTag('kernel.event_listener', ['event' => 'kernel.controller', 'method' => 'onKernelController'])
        ;
    }
}
