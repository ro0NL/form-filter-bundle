<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\DependencyInjection;

use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        return (new TreeBuilder('spiriit_form_filter'))->getRootNode()
            ->children()
                ->arrayNode('listeners')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('doctrine_orm')->defaultTrue()->end()
                    ->end()
                ->end()

                ->scalarNode('where_method')
                    ->defaultValue('and')
                    ->info('Defined the doctrine query builder method the bundle will use to add the entire filter condition.')
                    ->validate()
                        ->ifNotInArray([null, 'and', 'or'])
                        ->thenInvalid('Invalid value, please use "null", "and", "or".')
                    ->end()
                ->end()

                ->scalarNode('condition_pattern')
                    ->defaultValue('text.starts')
                    ->info('Default condition pattern for TextFilterType')
                    ->validate()
                        ->ifNotInArray([null, 'text.equals', 'text.ends', 'text.contains', 'text.starts'])
                        ->thenInvalid('Invalid value, please use "null", "text.contains", "text.starts", "text.ends", "text.equals".')
                    ->end()
                ->end()

                ->booleanNode('force_case_insensitivity')
                    ->info('Whether to do case insensitive LIKE comparisons.')
                    ->defaultFalse()
                ->end()

                ->scalarNode('encoding')
                    ->info('Encoding for case insensitive LIKE comparisons.')
                    ->defaultNull()
                ->end()
            ->end()
        ->end();
    }
}
