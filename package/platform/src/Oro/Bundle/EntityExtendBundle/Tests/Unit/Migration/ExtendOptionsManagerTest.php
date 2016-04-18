<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;

class ExtendOptionsManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExtendOptionsManager */
    private $manager;

    protected function setUp()
    {
        $this->manager = new ExtendOptionsManager();
    }

    public function testSetTableMode()
    {
        $this->manager->setTableMode('test_table', 'default');
        $this->assertEquals(
            ['test_table' => [ExtendOptionsManager::MODE_OPTION => 'default']],
            $this->manager->getExtendOptions()
        );
    }

    public function testSetColumnMode()
    {
        $this->manager->setColumnMode('test_table', 'test_clmn', 'default');
        $this->assertEquals(
            ['test_table!test_clmn' => [ExtendOptionsManager::MODE_OPTION => 'default']],
            $this->manager->getExtendOptions()
        );
    }

    public function testSetColumnType()
    {
        $this->manager->setColumnType('test_table', 'test_clmn', 'int');
        $this->assertEquals(
            ['test_table!test_clmn' => [ExtendOptionsManager::TYPE_OPTION => 'int']],
            $this->manager->getExtendOptions()
        );
    }

    /**
     * @dataProvider setTableOptionsProvider
     */
    public function testSetTableOptions(
        $tableName,
        array $options,
        array $prevValues,
        array $expected
    ) {
        if (!empty($prevValues)) {
            $this->setProtectedProperty($this->manager, 'options', $prevValues);
        }
        $this->manager->setTableOptions($tableName, $options);
        $this->assertEquals($expected, $this->manager->getExtendOptions());
    }

    /**
     * @dataProvider setColumnOptionsProvider
     */
    public function testSetColumnOptions(
        $tableName,
        $columnName,
        array $options,
        array $prevValues,
        array $expected
    ) {
        if (!empty($prevValues)) {
            $this->setProtectedProperty($this->manager, 'options', $prevValues);
        }
        $this->manager->setColumnOptions($tableName, $columnName, $options);
        $this->assertEquals($expected, $this->manager->getExtendOptions());
    }

    public function setTableOptionsProvider()
    {
        return $this->getSetOptionsData('test_table');
    }

    public function setColumnOptionsProvider()
    {
        return $this->getSetOptionsData('test_table', 'test_clmn');
    }

    /**
     * @param string      $tableName
     * @param string|null $columnName
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getSetOptionsData($tableName, $columnName = null)
    {
        $result = [];

        $this->addSetOptionsDataItem(
            'empty',
            [
                [],
                [],
                []
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'empty scope',
            [
                ['scope' => []],
                [],
                ['scope' => []]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'set new',
            [
                ['scope' => ['attr' => 'test']],
                [],
                ['scope' => ['attr' => 'test']]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'set new (array)',
            [
                ['scope' => ['attr' => ['test']]],
                [],
                ['scope' => ['attr' => ['test']]]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'replace existing attr',
            [
                ['scope' => ['attr' => 'test']],
                ['scope' => ['attr' => 'old', 'other_attr' => 'other']],
                ['scope' => ['attr' => 'test', 'other_attr' => 'other']]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'replace existing attr (array)',
            [
                ['scope' => ['attr' => ['test2']]],
                ['scope' => ['attr' => ['test1']]],
                ['scope' => ['attr' => ['test2']]]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'replace existing attr (null -> array)',
            [
                ['scope' => ['attr' => null]],
                ['scope' => ['attr' => ['test1']]],
                ['scope' => ['attr' => null]]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'append new',
            [
                $this->getAppendedOption('scope', 'attr', 'test'),
                [],
                [
                    'scope'                              => ['attr' => ['test']],
                    ExtendOptionsManager::APPEND_SECTION => ['scope' => ['attr']]
                ]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'append new (with existing another attr in the same scope)',
            [
                $this->getAppendedOption('scope', 'attr', 'test'),
                [
                    'scope' => ['another_attr' => 'test'],
                ],
                [
                    'scope'                              => ['attr' => ['test'], 'another_attr' => 'test'],
                    ExtendOptionsManager::APPEND_SECTION => ['scope' => ['attr']]
                ]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'append existing',
            [
                $this->getAppendedOption('scope', 'attr', 'test2'),
                ['scope' => ['attr' => ['test1']]],
                [
                    'scope'                              => ['attr' => ['test1', 'test2']],
                    ExtendOptionsManager::APPEND_SECTION => ['scope' => ['attr']]
                ]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'append existing empty',
            [
                $this->getAppendedOption('scope', 'attr', []),
                ['scope' => ['attr' => ['test1']]],
                [
                    'scope'                              => ['attr' => ['test1']],
                    ExtendOptionsManager::APPEND_SECTION => ['scope' => ['attr']]
                ]
            ],
            $result,
            $tableName,
            $columnName
        );

        return $result;
    }

    /**
     * @param string      $testName
     * @param array       $data
     * @param array       $result
     * @param string      $tableName
     * @param string|null $columnName
     */
    protected function addSetOptionsDataItem($testName, array $data, array &$result, $tableName, $columnName = null)
    {
        $key = $tableName;
        if (null !== $columnName) {
            $key .= '!' . $columnName;
        }

        $options      = $data[0];
        $prevData     = $this->processSetOptionsData($key, $data[1]);
        $expectedData = $this->processSetOptionsData($key, $data[2]);

        if (null === $columnName) {
            $result[$testName] = [$tableName, $options, $prevData, $expectedData];
        } else {
            $result[$testName] = [$tableName, $columnName, $options, $prevData, $expectedData];
        }
    }

    /**
     * @param string $key
     * @param array  $data
     * @return array
     */
    protected function processSetOptionsData($key, $data)
    {
        $result = [$key => $data];

        if (isset($result[$key][ExtendOptionsManager::APPEND_SECTION])) {
            $tmp = [$key => $result[$key][ExtendOptionsManager::APPEND_SECTION]];
            unset($result[$key][ExtendOptionsManager::APPEND_SECTION]);
            $result[ExtendOptionsManager::APPEND_SECTION] = $tmp;
        }

        return $result;
    }

    /**
     * @param string $scope
     * @param string $code
     * @param string $val
     * @return array
     */
    protected function getAppendedOption($scope, $code, $val)
    {
        $options = new OroOptions();
        $options->append($scope, $code, $val);

        return $options->toArray();
    }

    /**
     * @param mixed  $obj
     * @param string $propName
     * @param mixed  $val
     */
    protected function setProtectedProperty($obj, $propName, $val)
    {
        $class = new \ReflectionClass($obj);
        $prop  = $class->getProperty($propName);
        $prop->setAccessible(true);

        $prop->setValue($obj, $val);
    }
}
