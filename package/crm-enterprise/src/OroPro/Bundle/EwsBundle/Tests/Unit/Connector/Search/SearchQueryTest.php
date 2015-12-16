<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Connector\Search;

use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryOperator;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryMatch;
use OroPro\Bundle\EwsBundle\Connector\Search\QueryStringBuilder;
use OroPro\Bundle\EwsBundle\Connector\Search\RestrictionBuilder;

class SearchQueryTest extends \PHPUnit_Framework_TestCase
{
    /** @var SearchQuery */
    private $query;

    protected function setUp()
    {
        $this->query = self::createSearchQuery();
    }

    /**
     * @dataProvider valueProvider
     */
    public function testValue($value, $match, $expectedQuery)
    {
        $this->query->value($value, $match);
        $this->assertEquals($expectedQuery, $this->query->convertToQueryString());
    }

    /**
     * @dataProvider valueProviderForInvalidArguments
     * @expectedException \InvalidArgumentException
     */
    public function testValueInvalidArguments($value, $match)
    {
        $this->query->value($value, $match);
    }

    /**
     * @dataProvider itemProvider
     */
    public function testItem($name, $value, $operator, $match, $expectedQuery)
    {
        $this->query->item($name, $value, $operator, $match);
        $this->assertEquals($expectedQuery, $this->query->convertToQueryString());
    }

    /**
     * @dataProvider itemProviderForInvalidArguments
     * @expectedException \InvalidArgumentException
     */
    public function testItemInvalidArguments($name, $value, $operator, $match)
    {
        $this->query->item($name, $value, $operator, $match);
    }

    /**
     * @dataProvider itemRangeProvider
     */
    public function testItemRange($name, $fromValue, $toValue, $expectedQuery)
    {
        $this->query->itemRange($name, $fromValue, $toValue);
        $this->assertEquals($expectedQuery, $this->query->convertToQueryString());
    }

    /**
     * @dataProvider itemRangeProviderForInvalidArguments
     * @expectedException \InvalidArgumentException
     */
    public function testItemRangeInvalidArguments($name, $fromValue, $toValue)
    {
        $this->query->itemRange($name, $fromValue, $toValue);
    }

    public function testAndOperator()
    {
        $this->query->andOperator();
        $this->assertEquals("AND", $this->query->convertToQueryString());
    }

    public function testOrOperator()
    {
        $this->query->orOperator();
        $this->assertEquals("OR", $this->query->convertToQueryString());
    }

    public function testNotOperator()
    {
        $this->query->notOperator();
        $this->assertEquals("NOT", $this->query->convertToQueryString());
    }

    public function testOpenParenthesis()
    {
        $this->query->openParenthesis();
        $this->assertEquals("(", $this->query->convertToQueryString());
    }

    public function testCloseParenthesis()
    {
        $this->query->closeParenthesis();
        $this->assertEquals(")", $this->query->convertToQueryString());
    }

    public function testComplexQuety()
    {
        $subQuery = self::createSearchQuery();
        $subQuery->value('val1');
        $subQuery->value('val2');

        $this->query->item('subject', 'product1');
        $this->query->item('subject', $subQuery);
        $this->query->orOperator();
        $this->query->openParenthesis();
        $this->query->item('subject', 'product3');
        $this->query->notOperator();
        $this->query->item('subject', 'product4');
        $this->query->closeParenthesis();
        $this->assertEquals(
            "subject:product1 AND subject:(val1 AND val2) OR (subject:product3 AND NOT subject:product4)",
            $this->query->convertToQueryString()
        );
    }

    /**
     * @param SearchQuery $query
     * @param $expectedResult
     *
     * @dataProvider isEmptyProvider
     */
    public function testIsEmpty($query, $expectedResult)
    {
        $this->assertEquals($expectedResult, $query->isEmpty());
    }

    /**
     * @param SearchQuery $query
     * @param $expectedResult
     *
     * @dataProvider isComplexProvider
     */
    public function testIsComplex($query, $expectedResult)
    {
        $this->assertEquals($expectedResult, $query->isComplex());
    }

    public static function valueProvider()
    {
        $sampleQuery = self::createSearchQuery();
        $sampleQuery->value('product');

        return array(
            'one word + DEFAULT_MATCH'
                => array('product', SearchQueryMatch::DEFAULT_MATCH, 'product'),
            'one word + PREFIX_MATCH'
                => array('product', SearchQueryMatch::PREFIX_MATCH, 'product'),
            'one word + PREFIX_WITH_ORDER_RESTRICTED_MATCH'
                => array('product', SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH, '"product"*'),
            'one word + EXACT_WITH_ORDER_RESTRICTED_MATCH'
                => array('product', SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH, '"product"'),
            'two words + DEFAULT_MATCH'
                => array('my product', SearchQueryMatch::DEFAULT_MATCH, 'my product'),
            'two words + PREFIX_MATCH'
                => array('my product', SearchQueryMatch::PREFIX_MATCH, 'my product'),
            'two words + PREFIX_WITH_ORDER_RESTRICTED_MATCH'
                => array('my product', SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH, '"my product"*'),
            'two words + EXACT_WITH_ORDER_RESTRICTED_MATCH'
                => array('my product', SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH, '"my product"'),

            'SearchQuery as value + DEFAULT_MATCH' => array($sampleQuery, SearchQueryMatch::DEFAULT_MATCH, 'product'),
        );
    }

    public static function valueProviderForInvalidArguments()
    {
        $complexQuery = self::createSearchQuery();
        $complexQuery->value('product1');
        $complexQuery->value('product2');

        return array(
            'SearchQuery as value + PREFIX_MATCH'
                => array($complexQuery, SearchQueryMatch::PREFIX_MATCH),
            'SearchQuery as value + PREFIX_WITH_ORDER_RESTRICTED_MATCH'
                => array($complexQuery, SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH),
            'SearchQuery as value + EXACT_WITH_ORDER_RESTRICTED_MATCH'
                => array($complexQuery, SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH),
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function itemProvider()
    {
        $sampleQuery = self::createSearchQuery();
        $sampleQuery->value('product');

        return array(
            'one word + EQ + DEFAULT_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::EQ,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:product'
            ),
            'one word + EQ + PREFIX_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::EQ,
                SearchQueryMatch::PREFIX_MATCH,
                'subject:product'
            ),
            'one word + EQ + PREFIX_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::EQ,
                SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH,
                'subject:"product"*'
            ),
            'one word + EQ + EXACT_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::EQ,
                SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH,
                'subject:"product"'
            ),
            'one word + NEQ + DEFAULT_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::NEQ,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:- product'
            ),
            'one word + NEQ + PREFIX_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::NEQ,
                SearchQueryMatch::PREFIX_MATCH,
                'subject:- product'
            ),
            'one word + NEQ + PREFIX_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::NEQ,
                SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH,
                'subject:- "product"*'
            ),
            'one word + NEQ + EXACT_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::NEQ,
                SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH,
                'subject:- "product"'
            ),
            'one word + LT + DEFAULT_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::LT,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:<product'
            ),
            'one word + LT + PREFIX_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::LT,
                SearchQueryMatch::PREFIX_MATCH,
                'subject:<product'
            ),
            'one word + LT + PREFIX_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::LT,
                SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH,
                'subject:<"product"*'
            ),
            'one word + LT + EXACT_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::LT,
                SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH,
                'subject:<"product"'
            ),
            'one word + LE + DEFAULT_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::LE,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:<=product'
            ),
            'one word + LE + PREFIX_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::LE,
                SearchQueryMatch::PREFIX_MATCH,
                'subject:<=product'
            ),
            'one word + LE + PREFIX_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::LE,
                SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH,
                'subject:<="product"*'
            ),
            'one word + LE + EXACT_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::LE,
                SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH,
                'subject:<="product"'
            ),
            'one word + GT + DEFAULT_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::GT,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:>product'
            ),
            'one word + GT + PREFIX_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::GT,
                SearchQueryMatch::PREFIX_MATCH,
                'subject:>product'
            ),
            'one word + GT + PREFIX_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::GT,
                SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH,
                'subject:>"product"*'
            ),
            'one word + GT + EXACT_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::GT,
                SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH,
                'subject:>"product"'
            ),
            'one word + GE + DEFAULT_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::GE,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:>=product'
            ),
            'one word + GE + PREFIX_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::GE,
                SearchQueryMatch::PREFIX_MATCH,
                'subject:>=product'
            ),
            'one word + GE + PREFIX_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::GE,
                SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH,
                'subject:>="product"*'
            ),
            'one word + GE + EXACT_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'product',
                SearchQueryOperator::GE,
                SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH,
                'subject:>="product"'
            ),
            'two words + EQ + DEFAULT_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::EQ,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:my product'
            ),
            'two words + EQ + PREFIX_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::EQ,
                SearchQueryMatch::PREFIX_MATCH,
                'subject:my product'
            ),
            'two words + EQ + PREFIX_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::EQ,
                SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH,
                'subject:"my product"*'
            ),
            'two words + EQ + EXACT_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::EQ,
                SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH,
                'subject:"my product"'
            ),
            'two words + NEQ + DEFAULT_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::NEQ,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:- my product'
            ),
            'two words + NEQ + PREFIX_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::NEQ,
                SearchQueryMatch::PREFIX_MATCH,
                'subject:- my product'
            ),
            'two words + NEQ + PREFIX_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::NEQ,
                SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH,
                'subject:- "my product"*'
            ),
            'two words + NEQ + EXACT_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::NEQ,
                SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH,
                'subject:- "my product"'
            ),
            'two words + LT + DEFAULT_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::LT,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:<my product'
            ),
            'two words + LT + PREFIX_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::LT,
                SearchQueryMatch::PREFIX_MATCH,
                'subject:<my product'
            ),
            'two words + LT + PREFIX_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::LT,
                SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH,
                'subject:<"my product"*'
            ),
            'two words + LT + EXACT_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::LT,
                SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH,
                'subject:<"my product"'
            ),
            'two words + LE + DEFAULT_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::LE,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:<=my product'
            ),
            'two words + LE + PREFIX_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::LE,
                SearchQueryMatch::PREFIX_MATCH,
                'subject:<=my product'
            ),
            'two words + LE + PREFIX_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::LE,
                SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH,
                'subject:<="my product"*'
            ),
            'two words + LE + EXACT_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::LE,
                SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH,
                'subject:<="my product"'
            ),
            'two words + GT + DEFAULT_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::GT,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:>my product'
            ),
            'two words + GT + PREFIX_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::GT,
                SearchQueryMatch::PREFIX_MATCH,
                'subject:>my product'
            ),
            'two words + GT + PREFIX_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::GT,
                SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH,
                'subject:>"my product"*'
            ),
            'two words + GT + EXACT_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::GT,
                SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH,
                'subject:>"my product"'
            ),
            'two words + GE + DEFAULT_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::GE,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:>=my product'
            ),
            'two words + GE + PREFIX_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::GE,
                SearchQueryMatch::PREFIX_MATCH,
                'subject:>=my product'
            ),
            'two words + GE + PREFIX_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::GE,
                SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH,
                'subject:>="my product"*'
            ),
            'two words + GE + EXACT_WITH_ORDER_RESTRICTED_MATCH' => array(
                'subject',
                'my product',
                SearchQueryOperator::GE,
                SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH,
                'subject:>="my product"'
            ),
            'SearchQuery as value + EQ + DEFAULT_MATCH' => array(
                'subject',
                $sampleQuery,
                SearchQueryOperator::EQ,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:product'
            ),
            'SearchQuery as value + NEQ + DEFAULT_MATCH' => array(
                'subject',
                $sampleQuery,
                SearchQueryOperator::NEQ,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:- product'
            ),
            'SearchQuery as value + LT + DEFAULT_MATCH' => array(
                'subject',
                $sampleQuery,
                SearchQueryOperator::LT,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:<product'
            ),
            'SearchQuery as value + LE + DEFAULT_MATCH' => array(
                'subject',
                $sampleQuery,
                SearchQueryOperator::LE,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:<=product'
            ),
            'SearchQuery as value + GT + DEFAULT_MATCH' => array(
                'subject',
                $sampleQuery,
                SearchQueryOperator::GT,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:>product'
            ),
            'SearchQuery as value + GE + DEFAULT_MATCH' => array(
                'subject',
                $sampleQuery,
                SearchQueryOperator::GE,
                SearchQueryMatch::DEFAULT_MATCH,
                'subject:>=product'
            ),
        );
    }

    public static function itemProviderForInvalidArguments()
    {
        $sampleQuery = self::createSearchQuery();
        $sampleQuery->value('product1');
        $sampleQuery->value('product2');

        return array(
            'SearchQuery as value + NEQ + DEFAULT_MATCH' => array(
                'subject',
                $sampleQuery,
                SearchQueryOperator::NEQ,
                SearchQueryMatch::DEFAULT_MATCH
            ),
            'SearchQuery as value + LT + DEFAULT_MATCH' => array(
                'subject',
                $sampleQuery,
                SearchQueryOperator::LT,
                SearchQueryMatch::DEFAULT_MATCH
            ),
            'SearchQuery as value + LE + DEFAULT_MATCH' => array(
                'subject',
                $sampleQuery,
                SearchQueryOperator::LE,
                SearchQueryMatch::DEFAULT_MATCH
            ),
            'SearchQuery as value + GT + DEFAULT_MATCH' => array(
                'subject',
                $sampleQuery,
                SearchQueryOperator::GT,
                SearchQueryMatch::DEFAULT_MATCH
            ),
            'SearchQuery as value + GE + DEFAULT_MATCH' => array(
                'subject',
                $sampleQuery,
                SearchQueryOperator::GE,
                SearchQueryMatch::DEFAULT_MATCH
            ),
        );
    }

    public static function itemRangeProvider()
    {
        return array(
            'valid range' => array('subject', '1', '2', 'subject:1..2'),
        );
    }

    public static function itemRangeProviderForInvalidArguments()
    {
        return array(
            'both from and to are null' => array('subject', null, null),
            'both from and to are empty string' => array('subject', '', ''),
            'from is null' => array('subject', null, '2'),
            'from is empty string' => array('subject', '', '2'),
            'to is null' => array('subject', '1', null),
            'to is empty string' => array('subject', '1', ''),
        );
    }

    public static function isEmptyProvider()
    {
        $empty = self::createSearchQuery();
        $emptyWithEmptySubQuery = self::createSearchQuery();
        $emptyWithEmptySubQuery->value(self::createSearchQuery());
        $nonEmpty = self::createSearchQuery();
        $nonEmpty->value('val');
        $nonEmptyWithNonEmptySubQuery = self::createSearchQuery();
        $nonEmptySubQuery = self::createSearchQuery();
        $nonEmptySubQuery->value('val');
        $nonEmptyWithNonEmptySubQuery->value($nonEmptySubQuery);

        return array(
            "empty" => array ($empty, true),
            "emptyWithEmptySubQuery" => array ($emptyWithEmptySubQuery, true),
            "nonEmpty" => array ($nonEmpty, false),
            "nonEmptyWithNonEmptySubQuery" => array ($nonEmptyWithNonEmptySubQuery, false),
        );
    }

    public static function isComplexProvider()
    {
        $empty = self::createSearchQuery();
        $emptyWithEmptySubQuery = self::createSearchQuery();
        $emptyWithEmptySubQuery->value(self::createSearchQuery());

        $simple = self::createSearchQuery();
        $simple->value('val');

        $complex = self::createSearchQuery();
        $complex->value('val1');
        $complex->value('val2');

        return array(
            "empty" => array ($empty, false),
            "emptyWithEmptySubQuery" => array ($emptyWithEmptySubQuery, false),
            "simple" => array ($simple, false),
            "complex" => array ($complex, true),
        );
    }

    private static function createSearchQuery()
    {
        return new SearchQuery(
            new QueryStringBuilder(),
            new RestrictionBuilder()
        );
    }
}
