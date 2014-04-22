<?php
namespace OroPro\Bundle\EwsBundle\Tests\Unit\Connector\Search;

use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryExprRangeItem;

class SearchQueryExprRangeItemTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $name = 'testName';
        $fromValue = 'testValue1';
        $toValue = 'testValue2';
        $obj = new SearchQueryExprRangeItem($name, $fromValue, $toValue);

        $this->assertEquals($name, $obj->getName());
        $this->assertEquals($fromValue, $obj->getFromValue());
        $this->assertEquals($toValue, $obj->getToValue());
    }

    public function testSettersAndGetters()
    {
        $obj = new SearchQueryExprRangeItem('1', '1', '1');

        $name = 'testName';
        $fromValue = 'testValue1';
        $toValue = 'testValue2';

        $obj->setName($name);
        $obj->setFromValue($fromValue);
        $obj->setToValue($toValue);

        $this->assertEquals($name, $obj->getName());
        $this->assertEquals($fromValue, $obj->getFromValue());
        $this->assertEquals($toValue, $obj->getToValue());
    }
}
