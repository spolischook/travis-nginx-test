<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\DependencyInjection;

use Oro\DBAL\Types\MoneyType;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use Symfony\Component\Config\Definition\Processor;

use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();

        $treeBuilder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $treeBuilder);
    }

    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor     = new Processor();

        $expected = [
            'settings' => [
                'resolved' => 1,
                'default_price_lists' => [
                        'value' => [],
                        'scope' => 'app'
                ],
                'rounding_type' => [
                    'value' => RoundingServiceInterface::ROUND_HALF_UP,
                    'scope' => 'app'
                ],
                'precision' => [
                    'value' => MoneyType::TYPE_SCALE,
                    'scope' => 'app'
                ]
            ]
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
