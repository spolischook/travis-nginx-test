<?php
namespace OroPro\Bundle\EwsBundle\Tests\Unit\Connector\Search;

use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryExprItem;

class SearchQueryExprItemTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $name = 'testName';
        $value = 'testValue';
        $operator = '<';
        $match = 1;
        $ignoreCase = true;
        $obj = new SearchQueryExprItem($name, $value, $operator, $match, $ignoreCase);

        $this->assertEquals($name, $obj->getName());
        $this->assertEquals($value, $obj->getValue());
        $this->assertEquals($operator, $obj->getOperator());
        $this->assertEquals($match, $obj->getMatch());
        $this->assertEquals($ignoreCase, $obj->getIgnoreCase());
    }

    public function testSettersAndGetters()
    {
        $obj = new SearchQueryExprItem('1', '1', '=', 0, false);

        $name = 'testName';
        $value = 'testValue';
        $operator = '<';
        $match = 1;
        $ignoreCase = true;

        $obj->setName($name);
        $obj->setValue($value);
        $obj->setOperator($operator);
        $obj->setMatch($match);
        $obj->setIgnoreCase($ignoreCase);

        $this->assertEquals($name, $obj->getName());
        $this->assertEquals($value, $obj->getValue());
        $this->assertEquals($operator, $obj->getOperator());
        $this->assertEquals($match, $obj->getMatch());
        $this->assertEquals($ignoreCase, $obj->getIgnoreCase());
    }
}
