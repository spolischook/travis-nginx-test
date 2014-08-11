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
                        'scope' => 'app'
                    ],
                    'contacts_conflict_resolution'   => [
                        'value' => 'OroCRMAlwaysWins',
                        'scope' => 'app'
                    ],
                    'contacts_sync_interval_orocrm'  => [
                        'value' => 120,
                        'scope' => 'app'
                    ],
                    'contacts_sync_interval_outlook' => [
                        'value' => 30,
                        'scope' => 'app'
                    ],
                    'contacts_mapping'               => [
                        'value' => [
                            ['OroCRM' => 'description', 'Outlook' => 'Body'],
                            ['OroCRM' => 'jobTitle', 'Outlook' => 'JobTitle'],
                            ['OroCRM' => 'firstName', 'Outlook' => 'FirstName'],
                            ['OroCRM' => 'lastName', 'Outlook' => 'LastName'],
                            ['OroCRM' => 'middleName', 'Outlook' => 'MiddleName'],
                        ],
                        'scope' => 'app'
                    ],
                ]
            ],
            $processor->processConfiguration($config, [])
        );
    }
}
