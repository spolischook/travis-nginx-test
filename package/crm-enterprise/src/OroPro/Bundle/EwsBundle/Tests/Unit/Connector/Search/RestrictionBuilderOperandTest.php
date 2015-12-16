<?php
namespace OroPro\Bundle\EwsBundle\Tests\Unit\Connector\Search;

use OroPro\Bundle\EwsBundle\Connector\Search\RestrictionBuilderOperand;

class RestrictionBuilderOperandTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $type = 'testType';
        $element = 'testElement';
        $obj = new RestrictionBuilderOperand($type, $element);

        $this->assertEquals($type, $obj->getType());
        $this->assertEquals($element, $obj->getElement());
    }

    public function testSettersAndGetters()
    {
        $obj = new RestrictionBuilderOperand('1', '1');

        $type = 'testType';
        $element = 'testElement';

        $obj->setType($type);
        $obj->setElement($element);

        $this->assertEquals($type, $obj->getType());
        $this->assertEquals($element, $obj->getElement());
    }
}
