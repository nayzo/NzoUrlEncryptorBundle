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
                    ->info('The key every value is encrypted with.')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('previous_secret_keys')
                    ->info('Keys replaced by "secret_key": the values they encrypted are still decrypted.')
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
                ->scalarNode('secret_iv')
                    ->info('Required when "random_pseudo_bytes" is disabled.')
                    ->defaultValue('')
                ->end()
                ->scalarNode('cipher_algorithm')
                    ->defaultValue('aes-256-ctr')
                ->end()
                ->booleanNode('base64_encode')
                    ->defaultValue(true)
                ->end()
                ->booleanNode('format_base64_output')
                    ->info('Makes the base64 output safe in a URL. Only used when "base64_encode" is enabled.')
                    ->defaultValue(true)
                ->end()
                ->booleanNode('random_pseudo_bytes')
                    ->info('Disable it to get the same output for the same input.')
                    ->defaultValue(true)
                ->end()
                ->booleanNode('query_string_params')
                    ->info('Whether ParamEncryptor and ParamDecryptor also look for their parameters in the query string.')
                    ->defaultValue(false)
                ->end()
                ->booleanNode('legacy_decryption')
                    ->info('Whether the values encrypted by a version lower than 7 are still decrypted. They are not authenticated.')
                    ->defaultValue(false)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
