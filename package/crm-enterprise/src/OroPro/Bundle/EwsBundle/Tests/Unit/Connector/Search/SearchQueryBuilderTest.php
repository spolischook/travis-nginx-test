<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Connector\Search;

use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;
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
        $this->rangeFieldTestingWithValue(
            $name,
            [
                'val'     => ['val', 'val'],
                'fromVal' => ['val1', 'val1'],
                'toVal'   => ['val2', 'val2'],
            ]
        );
        $this->rangeFieldTestingWithValue(
            $name,
            [
                'val'     => [new \DateTime('2013-05-15 10:20:30'), '05/15/2013'],
                'fromVal' => [new \DateTime('2013-06-16 10:20:30'), '06/16/2013'],
                'toVal'   => [new \DateTime('2013-07-17 10:20:30'), '07/17/2013'],
            ]
        );
    }

    private function rangeFieldTestingWithValue($name, $values)
    {
        $query = self::createSearchQueryBuilder()
            ->$name($values['val'][0])
            ->get()
            ->convertToQueryString();
        $this->assertEquals($name.':>='.$values['val'][1], $query);

        $query = self::createSearchQueryBuilder()
            ->$name($values['val'][0], null, null)
            ->get()
            ->convertToQueryString();
        $this->assertEquals($name.':'.$values['val'][1], $query);

        $query = self::createSearchQueryBuilder()
            ->$name($values['val'][0], null, true)
            ->get()
            ->convertToQueryString();
        $this->assertEquals($name.':>='.$values['val'][1], $query);

        $query = self::createSearchQueryBuilder()
            ->$name(null, $values['toVal'][0], false)
            ->get()
            ->convertToQueryString();
        $this->assertEquals($name.':<'.$values['toVal'][1], $query);

        $query = self::createSearchQueryBuilder()
            ->$name(null, $values['toVal'][0], true)
            ->get()
            ->convertToQueryString();
        $this->assertEquals($name.':<='.$values['toVal'][1], $query);

        $query = self::createSearchQueryBuilder()
            ->$name($values['fromVal'][0], $values['toVal'][0])
            ->get()
            ->convertToQueryString();
        $this->assertEquals($name.':'.$values['fromVal'][1].'..'.$values['toVal'][1], $query);
    }

    private static function createSearchQueryBuilder()
    {
        return new SearchQueryBuilder(
            new SearchQuery(
                new QueryStringBuilder(),
                new RestrictionBuilder()
            )
        );
    }
}
