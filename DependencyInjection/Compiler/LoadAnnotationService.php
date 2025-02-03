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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LoadAnnotationService implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (false === $enableAnnotations = $container->getParameter('nzo_encryptor.annotations')) {
            return;
        }

        if (!($existDoctrineAnnotationReader = $container->has('annotations.reader')) && $enableAnnotations) {
            throw new \LogicException('The "nzo_encryptor.annotations" config for cannot be set to "true" without Doctrine annotations. Try running "composer require doctrine/annotations".');
        }

        if ($existDoctrineAnnotationReader) {
            $container->getDefinition('nzo.annotation_resolver')
                ->setArgument(1, $container->getDefinition('annotations.reader'));
        }
    }
}
