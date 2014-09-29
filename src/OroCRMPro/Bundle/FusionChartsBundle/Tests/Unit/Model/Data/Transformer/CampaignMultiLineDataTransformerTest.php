<?php

namespace OroCRMPro\Bundle\FusionChartsBundle\Tests\Unit\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\MappedData;
use OroCRM\Bundle\CampaignBundle\Entity\Campaign;
use OroCRMPro\Bundle\FusionChartsBundle\Model\Data\Transformer\CampaignMultiLineDataTransformer;

class CampaignMultiLineDataTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CampaignMultiLineDataTransformer
     */
    protected $transformer;

    protected function setUp()
    {
        $this->transformer = new CampaignMultiLineDataTransformer();
    }

    /**
     * @param array $data
     * @param array $chartOptions
     * @param array $expected
     *
     * @dataProvider dataProvider
     */
    public function testTransform(array $data, array $chartOptions, array $expected)
    {
        $sourceData = new ArrayData($data);

        $mapping = [
            'label' => 'label',
            'value' => 'value',
        ];

        $result = $this->transformer->transform(
            new MappedData($mapping, $sourceData),
            $chartOptions
        );

        $this->assertEquals($expected, $result->toArray());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProvider()
    {
        return [
            'fill_labels' => [
                [
                    [
                        'option' => 'o1',
                        'label'  => '2014-07-07',
                        'value'  => 1,
                    ],
                    [
                        'option' => 'o2',
                        'label'  => '2014-07-09',
                        'value'  => 1,
                    ]
                ],
                [
                    'data_schema'      => [
                        'label' => [
                            'field_name' => 'label'
                        ],
                        'value' => [
                            'field_name' => 'value'
                        ]
                    ],
                    'default_settings' => [
                        'groupingOption' => 'option',
                        'period'         => Campaign::PERIOD_DAILY
                    ]
                ],
                [
                    'categories' => [
                        'category' => [
                            ['label' => '2014-07-06'],
                            ['label' => '2014-07-07'],
                            ['label' => '2014-07-08'],
                            ['label' => '2014-07-09']
                        ]
                    ],
                    'dataset'    => [
                        [
                            'seriesname' => 'o1',
                            'data'       => [
                                ['value' => 0],
                                ['value' => 1],
                                ['value' => 0],
                                ['value' => 0],
                            ]
                        ],
                        [
                            'seriesname' => 'o2',
                            'data'       => [
                                ['value' => 0],
                                ['value' => 0],
                                ['value' => 0],
                                ['value' => 1],
                            ]
                        ]
                    ]
                ]
            ],
            'skip_labels' => [
                [
                    [
                        'option' => 'o1',
                        'label'  => '2014-07-07',
                        'value'  => 1,
                    ],
                    [
                        'option' => 'o2',
                        'label'  => '2014-07-09',
                        'value'  => 1,
                    ]
                ],
                [
                    'data_schema'      => [
                        'label' => [
                            'field_name' => 'label'
                        ],
                        'value' => [
                            'field_name' => 'value'
                        ]
                    ],
                    'default_settings' => [
                        'groupingOption' => 'option',
                        'period'         => Campaign::PERIOD_HOURLY
                    ]
                ],
                [
                    'categories' => [
                        'category' => [
                            ['label' => '2014-07-07'],
                            ['label' => '2014-07-09']
                        ]
                    ],
                    'dataset'    => [
                        [
                            'seriesname' => 'o1',
                            'data'       => [
                                ['value' => 1],
                                ['value' => 0],
                            ]
                        ],
                        [
                            'seriesname' => 'o2',
                            'data'       => [
                                ['value' => 0],
                                ['value' => 1],
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testEmptyData()
    {
        $sourceData   = new ArrayData([]);
        $data         = new MappedData([], $sourceData);
        $chartOptions = [
            'data_schema'      => [
                'label' => [
                    'field_name' => 'label'
                ],
                'value' => [
                    'field_name' => 'value'
                ]
            ],
            'default_settings' => [
                'groupingOption' => 'option',
                'period'         => Campaign::PERIOD_DAILY
            ]
        ];

        $result = $this->transformer->transform($data, $chartOptions);
        $this->assertEquals($sourceData, $result);
    }
}
