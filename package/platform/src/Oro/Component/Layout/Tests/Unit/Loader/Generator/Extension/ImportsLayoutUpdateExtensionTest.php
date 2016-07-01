<?php

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator\Extension;

use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Component\Layout\Loader\Generator\Extension\ImportsLayoutUpdateExtension;
use Oro\Component\Layout\Loader\Generator\Extension\ImportsLayoutUpdateVisitor;

class ImportsLayoutUpdateExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImportsLayoutUpdateExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new ImportsLayoutUpdateExtension();
    }

    /**
     * @dataProvider prepareDataProvider
     * @param array $source
     * @param array $expectedData
     */
    public function testPrepare(array $source, array $expectedData)
    {
        $collection = new VisitorCollection();
        $this->extension->prepare(new GeneratorData($source), $collection);
        $this->assertSameSize($expectedData, $collection);
        $this->assertEquals(new ImportsLayoutUpdateVisitor($expectedData), $collection[0]);
    }

    /**
     * @return array
     */
    public function prepareDataProvider()
    {
        return [
            [
                'source' => [
                    ImportsLayoutUpdateExtension::NODE_IMPORTS => [
                        [
                            'id' => 'import_id',
                            'root' => 'root_block_id',
                            'namespace' => 'import_namespace',
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id' => 'import_id',
                        'root' => 'root_block_id',
                        'namespace' => 'import_namespace',
                    ]
                ],
            ]
        ];
    }

    /**
     * @dataProvider emptyParametersDataProvider
     * @param array $source
     */
    public function testEmptyParameters(array $source)
    {
        $collection = new VisitorCollection();
        $this->extension->prepare(new GeneratorData($source), $collection);
        $this->assertEmpty($collection);
    }

    /**
     * @return array
     */
    public function emptyParametersDataProvider()
    {
        return [
            [
                'source' => []
            ],
            [
                'source' => [
                    ImportsLayoutUpdateExtension::NODE_IMPORTS => []
                ]
            ],
        ];
    }
}
