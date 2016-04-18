<?php

namespace OroCRMPro\Bundle\OutlookBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

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

        SettingsBuilder::append(
            $rootNode,
            [
                'contacts_enabled'                 => ['value' => true],
                'contacts_sync_direction'          => ['value' => 'Both'],
                'contacts_conflict_resolution'     => ['value' => 'OroCRMAlwaysWins'],
                'contacts_sync_interval_orocrm'    => ['value' => 120],
                'contacts_sync_interval_outlook'   => ['value' => 30],
                'contacts_keys'                    => ['value' => $contactKeys, 'type' => 'array'],
                'contacts_mapping'                 => ['value' => $contactMapping, 'type' => 'array'],
                'tasks_enabled'                    => ['value' => true],
                'calendar_events_enabled'          => ['value' => true],
                'side_bar_panel_layout'            => [
                    'value' => $this->getLayoutContent('side-bar-panel-layout.xaml'),
                    'type'  => 'string'
                ],
                'create_lead_dialog_layout'        => [
                    'value' => $this->getLayoutContent('create-lead-dialog-layout.xaml'),
                    'type'  => 'string'
                ],
                'create_opportunity_dialog_layout' => [
                    'value' => $this->getLayoutContent('create-opportunity-dialog-layout.xaml'),
                    'type'  => 'string'
                ],
                'create_case_dialog_layout'        => [
                    'value' => $this->getLayoutContent('create-case-dialog-layout.xaml'),
                    'type'  => 'string'
                ]

            ]
        );

        return $treeBuilder;
    }

    /**
     * Fetch layout content by $path
     *
     * @param string $fileName
     *
     * @return string
     *
     * @throws FileNotFoundException
     */
    protected function getLayoutContent($fileName)
    {
        $rootPath = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, '/../Resources/views/layouts/');
        $path     = $rootPath . $fileName;

        if (!is_file($path)) {
            throw new FileNotFoundException($path);
        }

        return file_get_contents($path);
    }
}
