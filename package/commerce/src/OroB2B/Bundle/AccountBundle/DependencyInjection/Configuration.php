<?php

namespace OroB2B\Bundle\AccountBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroB2BAccountExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'default_account_owner' => ['type' => 'string', 'value' => 1],
                'anonymous_account_group' => ['type' => 'integer', 'value' => null],
                'registration_allowed' => ['type' => 'boolean', 'value' => true],
                'confirmation_required' => ['type' => 'boolean', 'value' => true],
                'send_password_in_welcome_email' => ['type' => 'boolean', 'value' => false],
                'category_visibility' => ['value' => CategoryVisibility::VISIBLE],
                'product_visibility' => ['value' => ProductVisibility::VISIBLE],
            ]
        );

        return $treeBuilder;
    }
}
