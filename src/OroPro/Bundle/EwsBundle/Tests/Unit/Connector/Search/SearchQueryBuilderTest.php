<?php
namespace OroPro\Bundle\EwsBundle\Tests\Unit\Connector\Search;

use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryOperator;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryMatch;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryBuilder;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryValueBuilder;
use OroPro\Bundle\EwsBundle\Connector\Search\QueryStringBuilder;
use OroPro\Bundle\EwsBundle\Connector\Search\RestrictionBuilder;

class SearchQueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider valueProvider
     */
    public function testValue($value, $match, $expectedQuery)
    {
        $query = self::createSearchQueryBuilder()
            ->value($value, $match)
            ->get()
            ->convertToQueryString();
        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testFrom($value, $match, $expectedQuery)
    {
        $this->simpleFieldTesting('from', $value, $match, $expectedQuery);
    }

    public function testFromWithClosure()
    {
        $this->simpleFieldTestingWithClosure('from');
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testTo($value, $match, $expectedQuery)
    {
        $this->simpleFieldTesting('to', $value, $match, $expectedQuery);
    }

    public function testToWithClosure()
    {
        $this->simpleFieldTestingWithClosure('to');
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testCc($value, $match, $expectedQuery)
    {
        $this->simpleFieldTesting('cc', $value, $match, $expectedQuery);
    }

    public function testCcWithClosure()
    {
        $this->simpleFieldTestingWithClosure('cc');
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testBcc($value, $match, $expectedQuery)
    {
        $this->simpleFieldTesting('bcc', $value, $match, $expectedQuery);
    }

    public function testBccWithClosure()
    {
        $this->simpleFieldTestingWithClosure('bcc');
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testParticipants($value, $match, $expectedQuery)
    {
        $this->simpleFieldTesting('participants', $value, $match, $expectedQuery);
    }

    public function testParticipantsWithClosure()
    {
        $this->simpleFieldTestingWithClosure('participants');
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testSubject($value, $match, $expectedQuery)
    {
        $this->simpleFieldTesting('subject', $value, $match, $expectedQuery);
    }

    public function testSubjectWithClosure()
    {
        $this->simpleFieldTestingWithClosure('subject');
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testBody($value, $match, $expectedQuery)
    {
        $this->simpleFieldTesting('body', $value, $match, $expectedQuery);
    }

    public function testBodyWithClosure()
    {
        $this->simpleFieldTestingWithClosure('body');
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testAttachment($value, $match, $expectedQuery)
    {
        $this->simpleFieldTesting('attachment', $value, $match, $expectedQuery);
    }

    public function testAttachmentWithClosure()
    {
        $this->simpleFieldTestingWithClosure('attachment');
    }

    public function testSent()
    {
        $this->rangeFieldTesting('sent');
    }

    public function testReceived()
    {
        $this->rangeFieldTesting('received');
    }

    public static function valueProvider()
    {
        return array(
            'default match' => array('product', SearchQueryMatch::DEFAULT_MATCH, 'product'),
            'another match' => array('product', SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH, '"product"'),
        );
    }

    public static function simpleProvider()
    {
        return array(
            'default match' => array('product', SearchQueryMatch::DEFAULT_MATCH, 'product'),
            'another match' => array('product', SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH, '"product"'),
        );
    }

    private function simpleFieldTesting($name, $value, $match, $expectedQuery)
    {
        $query = self::createSearchQueryBuilder()
            ->$name($value, $match)
            ->get()
            ->convertToQueryString();
        $this->assertEquals($name.':'.$expectedQuery, $query);
    }

    private function simpleFieldTestingWithClosure($name)
    {
        $query = self::createSearchQueryBuilder()
            ->$name(function ($builder) {
                /** @var SearchQueryValueBuilder $builder */
                $builder
                    ->value('val1')
                    ->value('val2');
            })
            ->get()
            ->convertToQueryString();
        $this->assertEquals($name.':(val1 AND val2)', $query);
    }

    private function rangeFieldTesting($name)
    {
        $query = self::createSearchQueryBuilder()
            ->$name('val')
            ->get()
            ->convertToQueryString();
        $this->assertEquals($name.':val', $query);

        $query = self::createSearchQueryBuilder()
            ->$name('val', null, false)
            ->get()
            ->convertToQueryString();
        $this->assertEquals($name.':>val', $query);

        $query = self::createSearchQueryBuilder()
            ->$name('val', null, true)
            ->get()
            ->convertToQueryString();
        $this->assertEquals($name.':>=val', $query);

        $query = self::createSearchQueryBuilder()
            ->$name(null, 'val', false)
            ->get()
            ->convertToQueryString();
        $this->assertEquals($name.':<val', $query);

        $query = self::createSearchQueryBuilder()
            ->$name(null, 'val', true)
            ->get()
            ->convertToQueryString();
        $this->assertEquals($name.':<=val', $query);

        $query = self::createSearchQueryBuilder()
            ->$name('val1', 'val2')
            ->get()
            ->convertToQueryString();
        $this->assertEquals($name.':val1..val2', $query);
    }

    private static function createSearchQueryBuilder()
    {
        return new SearchQueryBuilder(
            new SearchQuery(
                new QueryStringBuilder(),
                new RestrictionBuilder()
            ));
    }
}
