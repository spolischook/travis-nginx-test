<?php

namespace OroCRMPro\Bundle\FusionChartsBundle\Tests\Unit\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use OroCRMPro\Bundle\FusionChartsBundle\Model\Data\Transformer\MultiSetDataTransformer;

class MultiSetDataTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $source
     * @param array $expected
     * @dataProvider transformDataProvider
     */
    public function testTransform(array $source, array $expected)
    {
        $transformer = new MultiSetDataTransformer();
        $this->assertEquals($expected, $transformer->transform(new ArrayData($source), [])->toArray());
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return [
            'minimum data' => [
                'source' => [],
                'expected' => []
            ],
            'full data' => [
                'source' => [
                    'foo' => [
                        ['label' => '2014-01-01', 'value' => 0],
                        ['label' => '2014-01-02', 'value' => 2],
                    ],
                    'bar' => [
                        ['label' => '2014-01-01', 'value' => 1],
                        ['label' => '2014-01-02', 'value' => 3],
                    ],
                ],
                'expected' => [
                    'categories' => [
                        'category' => [
                            ['label' => '2014-01-01'],
                            ['label' => '2014-01-02'],
                        ]
                    ],
                    'dataset' => [
                        [
                            'seriesname' => 'foo',
                            'data' => [
                                ['value' => 0],
                                ['value' => 2],
                            ]
                        ],
                        [
                            'seriesname' => 'bar',
                            'data' => [
                                ['value' => 1],
                                ['value' => 3],
                            ]
                        ]
                    ],
                ]
            ]
        ];
    }
}
