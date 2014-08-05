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

        $contactMapping = [
            ['OroCRM' => 'description', 'Outlook' => 'Body'],
            ['OroCRM' => 'email', 'Outlook' => 'Email1Address'],
            ['OroCRM' => 'jobTitle', 'Outlook' => 'JobTitle'],
            ['OroCRM' => 'fax', 'Outlook' => 'HomeFaxNumber'],
            ['OroCRM' => 'firstName', 'Outlook' => 'FirstName'],
            ['OroCRM' => 'lastName', 'Outlook' => 'LastName'],
            ['OroCRM' => 'middleName', 'Outlook' => 'MiddleName'],
        ];

        SettingsBuilder::append(
            $rootNode,
            [
                'contacts_sync_direction'        => ['value' => 'Both', 'scope' => 'user'],
                'contacts_conflict_resolution'   => ['value' => 'OroCRMAlwaysWins', 'scope' => 'user'],
                'contacts_sync_interval_orocrm'  => ['value' => 120, 'scope' => 'user'],
                'contacts_sync_interval_outlook' => ['value' => 30, 'scope' => 'user'],
                'contacts_mapping'               => [
                    'value' => $contactMapping,
                    'type'  => 'array',
                    'scope' => 'user'
                ]
            ]
        );

        return $treeBuilder;
    }
}
