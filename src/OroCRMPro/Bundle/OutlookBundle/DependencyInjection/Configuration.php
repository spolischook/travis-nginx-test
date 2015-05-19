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
        $sideBarPanelLayoutPath =
            __DIR__ .
            str_replace('/', DIRECTORY_SEPARATOR, '/configuration/xaml/side-bar-panel-layout.xaml');

        $contactKeys    = [
            ['OroCRM' => 'lastName', 'Outlook' => 'LastName'],
            ['OroCRM' => 'firstName', 'Outlook' => 'FirstName'],
            ['OroCRM' => 'birthday', 'Outlook' => 'Birthday'],
            ['OroCRM' => 'gender', 'Outlook' => 'Gender'],
        ];
        $contactMapping = [
            ['OroCRM' => 'description', 'Outlook' => 'Body'],
            ['OroCRM' => 'jobTitle', 'Outlook' => 'JobTitle'],
            ['OroCRM' => 'nameSuffix', 'Outlook' => 'Suffix'],
            ['OroCRM' => 'firstName', 'Outlook' => 'FirstName'],
            ['OroCRM' => 'lastName', 'Outlook' => 'LastName'],
            ['OroCRM' => 'middleName', 'Outlook' => 'MiddleName'],
            ['OroCRM' => 'birthday', 'Outlook' => 'Birthday'],
            ['OroCRM' => 'gender', 'Outlook' => 'Gender'],
            ['OroCRM' => 'emails[primary=true].email', 'Outlook' => 'Email1Address'],
            ['OroCRM' => 'emails[primary=false][0].email', 'Outlook' => 'Email2Address'],
            ['OroCRM' => 'emails[primary=false][1].email', 'Outlook' => 'Email3Address'],

            ['OroCRM' => 'phones[primary=true][0].phone', 'Outlook' => 'PrimaryTelephoneNumber'],
            ['OroCRM' => 'fax', 'Outlook' => 'BusinessFaxNumber'],

            ['OroCRM' => 'addresses[0].region', 'Outlook' => 'BusinessAddressState'],
            ['OroCRM' => 'addresses[0].country', 'Outlook' => 'BusinessAddressCountry'],
            ['OroCRM' => 'addresses[0].city', 'Outlook' => 'BusinessAddressCity'],
            ['OroCRM' => 'addresses[0].street', 'Outlook' => 'BusinessAddressStreet'],
            ['OroCRM' => 'addresses[0].postalCode', 'Outlook' => 'BusinessAddressPostalCode'],

            ['OroCRM' => 'addresses[1].region', 'Outlook' => 'HomeAddressState'],
            ['OroCRM' => 'addresses[1].country', 'Outlook' => 'HomeAddressCountry'],
            ['OroCRM' => 'addresses[1].city', 'Outlook' => 'HomeAddressCity'],
            ['OroCRM' => 'addresses[1].street', 'Outlook' => 'HomeAddressStreet'],
            ['OroCRM' => 'addresses[1].postalCode', 'Outlook' => 'HomeAddressPostalCode'],

            ['OroCRM' => 'addresses[2].region', 'Outlook' => 'OtherAddressState'],
            ['OroCRM' => 'addresses[2].country', 'Outlook' => 'OtherAddressCountry'],
            ['OroCRM' => 'addresses[2].city', 'Outlook' => 'OtherAddressCity'],
            ['OroCRM' => 'addresses[2].street', 'Outlook' => 'OtherAddressStreet'],
            ['OroCRM' => 'addresses[2].postalCode', 'Outlook' => 'OtherAddressPostalCode'],
        ];

        $xamlLayouts = [
            [
                'side_bar_panel_layout' => file_get_contents($sideBarPanelLayoutPath),

            ]
        ];

        SettingsBuilder::append(
            $rootNode,
            [
                'contacts_enabled'               => ['value' => true],
                'contacts_sync_direction'        => ['value' => 'Both'],
                'contacts_conflict_resolution'   => ['value' => 'OroCRMAlwaysWins'],
                'contacts_sync_interval_orocrm'  => ['value' => 120],
                'contacts_sync_interval_outlook' => ['value' => 30],
                'contacts_keys'                  => ['value' => $contactKeys, 'type' => 'array'],
                'contacts_mapping'               => ['value' => $contactMapping, 'type' => 'array'],
                'tasks_enabled'                  => ['value' => true],
                'calendar_events_enabled'        => ['value' => true],
                'xaml_layouts'                   => ['value' => $xamlLayouts, 'type' => 'array']
            ]
        );

        return $treeBuilder;
    }
}
