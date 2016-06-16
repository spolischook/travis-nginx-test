<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Provider;

use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;
use OroPro\Bundle\EwsBundle\Manager\DTO\Email;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;
use OroPro\Bundle\EwsBundle\Provider\EwsEmailIterator;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;

class IteratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var EwsEmailIterator */
    protected $iterator;

    /** @var EwsEmailManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $ewsManagerMock;

    /** @var SearchQuery */
    protected $searchQueryMock;

    protected function setUp()
    {
        $this->ewsManagerMock  = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Manager\EwsEmailManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchQueryMock = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery')
            ->disableOriginalConstructor()
            ->getMock();

        $this->iterator = new EwsEmailIterator($this->ewsManagerMock, $this->searchQueryMock);
    }

    protected function tearDown()
    {
        unset($this->searchQueryMock, $this->ewsManagerMock, $this->iterator);
    }

    public function testIteration()
    {
        $emails = [
            new Email($this->ewsManagerMock)
        ];

        $findType = new EwsType\FindItemType();
        $this->ewsManagerMock->expects($this->at(0))
            ->method('getEmails')
            ->will($this->returnCallback(
                function ($searchQueryMock, $closure) use ($findType, $emails) {
                    $closure($findType);
                    return $emails;
                }
            ));

        $this->ewsManagerMock->expects($this->at(1))
            ->method('getEmails')
            ->will($this->returnValue([]));

        $i = 0;
        foreach ($this->iterator as $email) {
            $this->assertInstanceOf('OroPro\Bundle\EwsBundle\Manager\DTO\Email', $email);
            $i++;
        }

        $this->assertEquals(1, $i);

        $this->assertInstanceOf(
            'OroPro\Bundle\EwsBundle\Ews\EwsType\IndexedPageViewType',
            $findType->IndexedPageItemView
        );
    }
}
