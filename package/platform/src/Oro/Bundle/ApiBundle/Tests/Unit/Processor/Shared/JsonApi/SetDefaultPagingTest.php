<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\SetDefaultPaging;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class SetDefaultPagingTest extends GetListProcessorTestCase
{
    /** @var SetDefaultPaging */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new SetDefaultPaging();
    }

    public function testProcessOnExistingQuery()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcess()
    {
        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertEquals(2, $filters->count());
        /** @var PageSizeFilter $pageSizeFilter */
        $pageSizeFilter = $filters->get('page[size]');
        $this->assertEquals(10, $pageSizeFilter->getDefaultValue());
        /** @var PageNumberFilter $pageNumberFilter */
        $pageNumberFilter = $filters->get('page[number]');
        $this->assertEquals(1, $pageNumberFilter->getDefaultValue());

        // check that filters are added in correct order
        $this->assertEquals(['page[size]', 'page[number]'], array_keys($filters->all()));
    }
}
