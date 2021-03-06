<?php

namespace Knp\Bundle\GaufretteBundle\DependencyInjection\Factory;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AclAwareAmazonS3AdapterFactory implements AdapterFactoryInterface
{
    /**
    * Creates the adapter, registers it and returns its id
    *
    * @param  ContainerBuilder $container  A ContainerBuilder instance
    * @param  string           $id         The id of the service
    * @param  array            $config     An array of configuration
    */
    public function create(ContainerBuilder $container, $id, array $config)
    {
        $container
            ->setDefinition($id.'.delegate', new DefinitionDecorator('knp_gaufrette.adapter.amazon_s3'))
            ->addArgument(new Reference($config['amazon_s3_id']))
            ->addArgument($config['bucket_name'])
            ->addArgument($config['create'])
        ;

        $def = $container
            ->setDefinition($id, new DefinitionDecorator('knp_gaufrette.adapter.acl_aware_amazon_s3'))
            ->addArgument(new Reference($id.'.delegate'))
            ->addArgument(new Reference($config['amazon_s3_id']))
            ->addArgument($config['bucket_name'])
        ;

        if (isset($config['acl'])) {
            $def->addMethodCall('setAclConstant', array($config['acl']));
        }

        if (isset($config['users'])) {
            $def->addMethodCall('setUsers', array($config['users']));
        }
    }

    /**
     * Returns the key for the factory configuration
     *
     * @return string
     */
    public function getKey()
    {
        return 'acl_aware_amazon_s3';
    }

    /**
     * Adds configuration nodes for the factory
     *
     * @param  NodeBuilder $builder
     */
    public function addConfiguration(NodeDefinition $builder)
    {
        $builder
            ->validate()
                ->always(function($v) {
                    if (isset($v['acl']) && isset($v['users'])) {
                        throw new \Exception('"acl", and "users" cannot be set both at the same time.');
                    }

                    return $v;
                })
            ->end()
            ->fixXmlConfig('user')
            ->children()
                ->scalarNode('amazon_s3_id')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('bucket_name')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('acl')->cannotBeEmpty()->end()
                ->arrayNode('users')
                    ->prototype('array')
                        ->validate()
                            ->always(function($v) {
                                if (isset($v['group']) === isset($v['id'])) {
                                    throw new \Exception('Either "group", or "id" must be set.');
                                }

                                return $v;
                            })
                        ->end()
                        ->children()
                            ->scalarNode('group')->cannotBeEmpty()->end()
                            ->scalarNode('id')->cannotBeEmpty()->end()
                            ->scalarNode('permission')->isRequired()->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('create')->defaultFalse()->end()
            ->end()
        ;
    }
}