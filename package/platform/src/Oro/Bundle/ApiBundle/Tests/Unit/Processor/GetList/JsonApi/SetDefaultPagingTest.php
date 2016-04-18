<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\JsonApi;

use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Processor\GetList\JsonApi\SetDefaultPaging;
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

    public function testProcessForJSONAPIRequest()
    {
        $this->context->getRequestType()->clear();
        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertEquals(2, $filters->count());
        $this->assertEquals(10, $filters->get('page[size]')->getDefaultValue());
        $this->assertEquals(1, $filters->get('page[number]')->getDefaultValue());
        $expectedFiltersOrder = ['page[size]', 'page[number]'];
        $currentIndex = 0;
        foreach ($filters as $filterKey => $filterDefinition) {
            $this->assertEquals($expectedFiltersOrder[$currentIndex], $filterKey);
            $currentIndex++;
        }
    }

    public function testProcessForMixedRequest()
    {
        $pageSizeFilter = new PageSizeFilter('integer');
        $pageNumberFilter = new PageNumberFilter('integer');
        $filters = new FilterCollection();
        $filters->add('limit', $pageSizeFilter);
        $filters->add('page', $pageNumberFilter);
        $this->context->set('filters', $filters);

        $this->context->getRequestType()->clear();
        $this->context->getRequestType()->add(RequestType::REST);
        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->processor->process($this->context);

        $this->assertEquals(2, $filters->count());
        $expectedFiltersOrder = ['page[size]', 'page[number]'];
        $currentIndex = 0;
        foreach ($filters as $filterKey => $filterDefinition) {
            $this->assertEquals($expectedFiltersOrder[$currentIndex], $filterKey);
            $currentIndex++;
        }
    }
}
