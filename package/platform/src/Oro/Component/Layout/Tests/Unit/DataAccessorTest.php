<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\DataAccessor;
use Oro\Component\Layout\LayoutContext;

class DataAccessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var LayoutContext */
    protected $context;

    /** @var DataAccessor */
    protected $dataAccessor;

    protected function setUp()
    {
        $this->registry = $this->getMock('Oro\Component\Layout\LayoutRegistryInterface');
        $this->context  = new LayoutContext();

        $this->dataAccessor = new DataAccessor(
            $this->registry,
            $this->context
        );
    }

    public function testGet()
    {
        $name         = 'foo';
        $expectedId   = 'foo_id';
        $expectedData = new \stdClass();

        $dataProvider = $this->getMock('Oro\Component\Layout\DataProviderInterface');
        $dataProvider->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue($expectedId));
        $dataProvider->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($expectedData));

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue($dataProvider));

        $this->assertSame($expectedData, $this->dataAccessor->get($name));
        // test data provider identifier
        $this->assertEquals($expectedId, $this->dataAccessor->getIdentifier($name));
        // test that data provider is cached
        $this->assertSame($expectedData, $this->dataAccessor->get($name));
    }

    public function testArrayAccessGet()
    {
        $name         = 'foo';
        $expectedId   = 'foo_id';
        $expectedData = new \stdClass();

        $dataProvider = $this->getMock('Oro\Component\Layout\DataProviderInterface');
        $dataProvider->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue($expectedId));
        $dataProvider->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($expectedData));

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue($dataProvider));

        $this->assertSame($expectedData, $this->dataAccessor[$name]);
        // test data provider identifier
        $this->assertEquals($expectedId, $this->dataAccessor->getIdentifier($name));
        // test that data provider is cached
        $this->assertSame($expectedData, $this->dataAccessor[$name]);
    }

    public function testGetFromContextData()
    {
        $name                 = 'foo';
        $expectedData         = new \stdClass();
        $expectedDataId       = 'foo_id';
        $this->context[$name] = 'other';
        $this->context->getResolver()->setOptional([$name]);
        $this->context->data()->set($name, $expectedDataId, $expectedData);
        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue(null));

        $this->assertSame($expectedData, $this->dataAccessor->get($name));
        // test data provider identifier
        $this->assertEquals($expectedDataId, $this->dataAccessor->getIdentifier($name));
        // test that context data provider is cached
        $this->assertSame($expectedData, $this->dataAccessor->get($name));
    }

    public function testArrayAccessGetFromContextData()
    {
        $name                 = 'foo';
        $expectedData         = new \stdClass();
        $expectedDataId       = 'foo_id';
        $this->context[$name] = 'other';
        $this->context->getResolver()->setOptional([$name]);
        $this->context->data()->set($name, $expectedDataId, $expectedData);
        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue(null));

        $this->assertSame($expectedData, $this->dataAccessor[$name]);
        // test data provider identifier
        $this->assertEquals($expectedDataId, $this->dataAccessor->getIdentifier($name));
        // test that context data provider is cached
        $this->assertSame($expectedData, $this->dataAccessor[$name]);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Could not load the data provider "foo".
     */
    public function testGetFromContextThrowsExceptionIfContextDataDoesNotExist()
    {
        $name = 'foo';
        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue(null));

        $this->dataAccessor->get($name);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Could not load the data provider "foo".
     */
    public function testGetIdentifierFromContextThrowsExceptionIfContextDataDoesNotExist()
    {
        $name = 'foo';
        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue(null));

        $this->dataAccessor->getIdentifier($name);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Could not load the data provider "foo".
     */
    public function testArrayAccessGetFromContextThrowsExceptionIfContextDataDoesNotExist()
    {
        $name = 'foo';
        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue(null));

        $this->dataAccessor[$name];
    }

    public function testArrayAccessExistsForUnknownDataProvider()
    {
        $name = 'foo';
        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue(null));

        $this->assertFalse(isset($this->dataAccessor[$name]));
    }

    public function testArrayAccessExists()
    {
        $name         = 'foo';
        $expectedData = new \stdClass();
        $this->context->resolve();

        $dataProvider = $this->getMock('Oro\Component\Layout\DataProviderInterface');
        $dataProvider->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($expectedData));

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue($dataProvider));

        $this->assertTrue(isset($this->dataAccessor[$name]));
        // test that data provider is cached
        $this->assertSame($expectedData, $this->dataAccessor[$name]);
    }

    public function testArrayAccessExistsForContextData()
    {
        $name = 'foo';
        $this->context->data()->set($name, 'foo_id', 'val');
        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue(null));

        $this->assertTrue(isset($this->dataAccessor[$name]));
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testArrayAccessSetThrowsException()
    {
        $this->dataAccessor['foo'] = 'bar';
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testArrayAccessRemoveThrowsException()
    {
        unset($this->dataAccessor['foo']);
    }
}
