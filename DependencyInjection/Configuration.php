<?php
namespace Hip\I18nRoutingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder
            ->root('hip_i18n_routing')
                ->validate()
                    ->always()
                    ->then(function($v) {
                        if (!in_array($v['default_locale'], $v['locales'], true)) {
                            $ex = new InvalidConfigurationException('Invalid configuration at path "jms_i18n_routing.default_locale": The default locale must be one of the configured locales.');
                            $ex->setPath('hip_i18n_routing.default_locale');
                            throw $ex;
                        }

                        return $v;
                    })
                ->end()
                ->children()
                    ->scalarNode('default_locale')->isRequired()->end()
                        ->arrayNode('locales')
                            ->beforeNormalization()
                                ->ifString()
                                ->then(function($v) { return preg_split('/\s*,\s*/', $v); })
                            ->end()
                            ->requiresAtLeastOneElement()
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
        ;

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
