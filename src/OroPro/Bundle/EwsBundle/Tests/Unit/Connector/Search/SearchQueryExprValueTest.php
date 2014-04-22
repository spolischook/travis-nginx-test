<?php
namespace OroPro\Bundle\EwsBundle\Tests\Unit\Connector\Search;

use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryExprValue;

class SearchQueryExprValueTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $value = 'testValue';
        $match = 1;
        $obj = new SearchQueryExprValue($value, $match);

        $this->assertEquals($value, $obj->getValue());
        $this->assertEquals($match, $obj->getMatch());
    }

    public function testSettersAndGetters()
    {
        $obj = new SearchQueryExprValue('1', '1', '=', 0, false);

        $value = 'testValue';
        $match = 1;

        $obj->setValue($value);
        $obj->setMatch($match);

        $this->assertEquals($value, $obj->getValue());
        $this->assertEquals($match, $obj->getMatch());
    }
}
