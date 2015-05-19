<?php

namespace OroCRMPro\Bundle\OutlookBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /** @var  Translator */
    protected $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Get translator
     *
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Set translator
     *
     * @param Translator $translator
     *
     * @return Configuration
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder            = new TreeBuilder();
        $rootNode               = $treeBuilder->root('oro_crm_pro_outlook');
        $sideBarPanelLayoutPath =
            __DIR__ .
            str_replace('/', DIRECTORY_SEPARATOR, '/../Resources/views/layouts/side-bar-panel-layout.xaml');

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

        $layouts = [
            [
                'side_bar_panel_layout' => $this->getTranslatedLayout($sideBarPanelLayoutPath),

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
                'layouts'                        => ['value' => $layouts, 'type' => 'array']
            ]
        );

        return $treeBuilder;
    }

    /**
     * Fetch layout and translate it.
     *
     * @param string $path
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function getTranslatedLayout($path)
    {
        if (!is_file($path)) {
            throw new FileNotFoundException($path);
        }
        $content = file_get_contents($path);

        return preg_replace_callback('/<%(.+)%>/', function ($input) {
            return $this->translator->trans($input[1], [], 'xaml');
        }, $content);
    }
}
