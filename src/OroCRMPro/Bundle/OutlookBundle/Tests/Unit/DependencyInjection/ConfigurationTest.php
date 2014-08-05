<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;

use OroCRMPro\Bundle\OutlookBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $config    = new Configuration();
        $processor = new Processor();
        $this->assertEquals(
            [
                'settings' => [
                    'resolved'                       => true,
                    'contacts_sync_direction'        => [
                        'value' => 'Both',
                        'scope' => 'user'
                    ],
                    'contacts_conflict_resolution'   => [
                        'value' => 'OroCRMAlwaysWins',
                        'scope' => 'user'
                    ],
                    'contacts_sync_interval_orocrm'  => [
                        'value' => 120,
                        'scope' => 'user'
                    ],
                    'contacts_sync_interval_outlook' => [
                        'value' => 30,
                        'scope' => 'user'
                    ],
                    'contacts_mapping'               => [
                        'value' => [
                            ['OroCRM' => 'description', 'Outlook' => 'Body'],
                            ['OroCRM' => 'email', 'Outlook' => 'Email1Address'],
                            ['OroCRM' => 'jobTitle', 'Outlook' => 'JobTitle'],
                            ['OroCRM' => 'fax', 'Outlook' => 'HomeFaxNumber'],
                            ['OroCRM' => 'firstName', 'Outlook' => 'FirstName'],
                            ['OroCRM' => 'lastName', 'Outlook' => 'LastName'],
                            ['OroCRM' => 'middleName', 'Outlook' => 'MiddleName'],
                        ],
                        'scope' => 'user'
                    ],
                ]
            ],
            $processor->processConfiguration($config, [])
        );
    }
}
