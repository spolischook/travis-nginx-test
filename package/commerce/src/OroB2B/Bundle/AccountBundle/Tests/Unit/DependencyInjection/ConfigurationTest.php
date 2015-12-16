<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\DependencyInjection;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Symfony\Component\Config\Definition\Processor;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test Configuration
     */
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();

        $this->assertInstanceOf(
            'Symfony\Component\Config\Definition\Builder\TreeBuilder',
            $configuration->getConfigTreeBuilder()
        );
    }

    /**
     * @dataProvider processConfigurationDataProvider
     * @param array $configs
     * @param array $expected
     */
    public function testProcessConfiguration(array $configs, array $expected)
    {
        $configuration = new Configuration();
        $processor     = new Processor();
        $this->assertEquals($expected, $processor->processConfiguration($configuration, $configs));
    }

    /**
     * @return array
     */
    public function processConfigurationDataProvider()
    {
        return [
            'empty' => [
                'configs'  => [[]],
                'expected' => [
                    'settings' => [
                        'resolved' => 1,
                        'default_account_owner' => [
                            'value' => 1,
                            'scope' => 'app'
                        ],
                        'registration_allowed' => [
                            'value' => true,
                            'scope' => 'app'
                        ],
                        'confirmation_required' => [
                            'value' => true,
                            'scope' => 'app'
                        ],
                        'send_password_in_welcome_email' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'category_visibility' => [
                            'value' => CategoryVisibility::VISIBLE,
                            'scope' => 'app'
                        ],
                        'product_visibility' => [
                            'value' => ProductVisibility::VISIBLE,
                            'scope' => 'app'
                        ]
                    ]
                ]
            ]
        ];
    }
}
