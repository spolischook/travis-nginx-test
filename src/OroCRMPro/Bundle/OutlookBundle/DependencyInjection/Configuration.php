<?php

namespace OroCRMPro\Bundle\OutlookBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_crm_pro_outlook');

        $contactKeys = [
            ['OroCRM' => 'lastName', 'Outlook' => 'LastName'],
            ['OroCRM' => 'firstName', 'Outlook' => 'FirstName'],
        ];
        $contactMapping = [
            ['OroCRM' => 'description', 'Outlook' => 'Body'],
            ['OroCRM' => 'jobTitle', 'Outlook' => 'JobTitle'],
            ['OroCRM' => 'firstName', 'Outlook' => 'FirstName'],
            ['OroCRM' => 'lastName', 'Outlook' => 'LastName'],
            ['OroCRM' => 'middleName', 'Outlook' => 'MiddleName'],
        ];

        SettingsBuilder::append(
            $rootNode,
            [
                'contacts_sync_direction'        => ['value' => 'Both'],
                'contacts_conflict_resolution'   => ['value' => 'OroCRMAlwaysWins'],
                'contacts_sync_interval_orocrm'  => ['value' => 120],
                'contacts_sync_interval_outlook' => ['value' => 30],
                'contacts_keys'                  => ['value' => $contactKeys, 'type'  => 'array'],
                'contacts_mapping'               => ['value' => $contactMapping, 'type'  => 'array']
            ]
        );

        return $treeBuilder;
    }
}
