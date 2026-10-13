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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nzo_encryptor');

        $treeBuilder
            ->getRootNode()
            ->children()
                ->scalarNode('secret_key')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('secret_iv')
                    ->defaultValue('')
                ->end()
                ->scalarNode('cipher_algorithm')
                    ->defaultValue('aes-256-ctr')
                ->end()
                ->booleanNode('base64_encode')
                    ->defaultValue(true)
                ->end()
                ->booleanNode('format_base64_output')
                    ->defaultValue(true)
                ->end()
                ->booleanNode('random_pseudo_bytes')
                    ->defaultValue(true)
                ->end()
                ->booleanNode('query_string_params')
                    ->info('Whether ParamEncryptor and ParamDecryptor also look for their parameters in the query string.')
                    ->defaultValue(false)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
