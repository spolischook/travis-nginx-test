<?php
namespace OroPro\Bundle\EwsBundle\Tests\Unit\Connector\Search;

use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryOperator;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryMatch;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryExprItem;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryExprOperator;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryExprRangeItem;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryExpr;
use OroPro\Bundle\EwsBundle\Connector\Search\QueryStringBuilder;
use OroPro\Bundle\EwsBundle\Connector\Search\RestrictionBuilder;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class RestrictionBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var RestrictionBuilder */
    private $builder;

    protected function setUp()
    {
        $this->builder = new RestrictionBuilder();
    }

    /**
     * @dataProvider convertExprToRPNProvider
     */
    public function testConvertExpr($exprArray, $expectedExprArray)
    {
        $expr = new SearchQueryExpr();
        foreach ($exprArray as $item) {
            $expr->add($item);
        }

        $actualExprArray = iterator_to_array($this->callConvertExprToRPN($expr));

        $this->assertEquals($expectedExprArray, $actualExprArray);
    }
    public function testOneContainsItem()
    {
        $expr = new SearchQueryExpr();
        $expr->add(
            new SearchQueryExprItem(
                'subject',
                'val1',
                SearchQueryOperator::EQ,
                SearchQueryMatch::DEFAULT_MATCH,
                true
            )
        );

        $expected = new EwsType\RestrictionType();
        $expected->Contains = array(
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
                'val1',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            )
        );

        $actual = $this->builder->buildRestriction($expr);

        $this->assertEquals($expected, $actual);
    }

    public function testOneNotContainsItem()
    {
        $expr = new SearchQueryExpr();
        $expr->add(new SearchQueryExprOperator('NOT'));
        $expr->add(
            new SearchQueryExprItem(
                'subject',
                'val1',
                SearchQueryOperator::EQ,
                SearchQueryMatch::DEFAULT_MATCH,
                true
            )
        );

        $expected = new EwsType\RestrictionType();
        $contain = $this->buildContainsExpression(
            EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
            'val1',
            EwsType\ContainmentModeType::SUBSTRING,
            EwsType\ContainmentComparisonType::IGNORE_CASE
        );
        $expected->NotRestriction = array(new EwsType\NotType());
        $expected->NotRestriction[0]->Contains = array($contain);

        $actual = $this->builder->buildRestriction($expr);

        $this->assertEquals($expected, $actual);
    }

    public function testOneNotEqualItem()
    {
        $expr = new SearchQueryExpr();
        $expr->add(
            new SearchQueryExprItem('subject', 'val1', SearchQueryOperator::NEQ, SearchQueryMatch::DEFAULT_MATCH, true)
        );

        $expected = new EwsType\RestrictionType();
        $contain = $this->buildContainsExpression(
            EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
            'val1',
            EwsType\ContainmentModeType::SUBSTRING,
            EwsType\ContainmentComparisonType::IGNORE_CASE
        );
        $expected->NotRestriction = array(new EwsType\NotType());
        $expected->NotRestriction[0]->Contains = array($contain);

        $actual = $this->builder->buildRestriction($expr);

        $this->assertEquals($expected, $actual);
    }

    public function testOneIsLessThanItem()
    {
        $expr = new SearchQueryExpr();
        $expr->add(
            new SearchQueryExprItem(
                'sent',
                '20/10/2013',
                SearchQueryOperator::LT,
                SearchQueryMatch::DEFAULT_MATCH,
                false
            )
        );

        $expected = new EwsType\RestrictionType();
        $expected->IsLessThan = array(
            $this->buildIsLessThanExpression(
                EwsType\UnindexedFieldURIType::ITEM_DATE_TIME_SENT,
                '20/10/2013'
            )
        );

        $actual = $this->builder->buildRestriction($expr);

        $this->assertEquals($expected, $actual);
    }

    public function testOneIsGreaterThanItem()
    {
        $expr = new SearchQueryExpr();
        $expr->add(
            new SearchQueryExprItem(
                'sent',
                new \DateTime('2013-10-20 10:20:30'),
                SearchQueryOperator::GT,
                SearchQueryMatch::DEFAULT_MATCH,
                false
            )
        );

        $expected = new EwsType\RestrictionType();
        $expected->IsGreaterThan = array(
            $this->buildIsGreaterThanExpression(
                EwsType\UnindexedFieldURIType::ITEM_DATE_TIME_SENT,
                '2013-10-20T00:00:00Z'
            )
        );

        $actual = $this->builder->buildRestriction($expr);

        $this->assertEquals($expected, $actual);
    }

    public function testItemRange()
    {
        $expr = new SearchQueryExpr();
        $expr->add(new SearchQueryExprRangeItem('sent', 'val1', 'val2'));

        $expected = new EwsType\RestrictionType();
        $expected->AndRestriction = array(new EwsType\AndType());
        $expected->AndRestriction[0]->IsGreaterThan = array(
            $this->buildIsGreaterThanExpression(EwsType\UnindexedFieldURIType::ITEM_DATE_TIME_SENT, 'val1')
        );
        $expected->AndRestriction[0]->IsLessThan = array(
            $this->buildIsLessThanExpression(EwsType\UnindexedFieldURIType::ITEM_DATE_TIME_SENT, 'val2')
        );

        $actual = $this->builder->buildRestriction($expr);

        $this->assertEquals($expected, $actual);
    }

    public function testItemWithSimpleSubQuery()
    {
        $subQuery = self::createSearchQuery();
        $subQuery->value('val1');

        $expr = new SearchQueryExpr();
        $expr->add(
            new SearchQueryExprItem(
                'subject',
                $subQuery->getExpression(),
                SearchQueryOperator::EQ,
                SearchQueryMatch::DEFAULT_MATCH,
                true
            )
        );

        $expected = new EwsType\RestrictionType();
        $expected->Contains = array(
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
                'val1',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            )
        );

        $actual = $this->builder->buildRestriction($expr);

        $this->assertEquals($expected, $actual);
    }

    public function testItemWithComplexSubQuery()
    {
        $subSubSubQuery = self::createSearchQuery();
        $subSubSubQuery->value('val11');
        $subSubSubQuery->value('val12');
        $subQuery = self::createSearchQuery();
        $subQuery->value($subSubSubQuery);
        $subQuery->value('val2');

        $expr = new SearchQueryExpr();
        $expr->add(
            new SearchQueryExprItem(
                'subject',
                $subQuery->getExpression(),
                SearchQueryOperator::EQ,
                SearchQueryMatch::DEFAULT_MATCH,
                true
            )
        );

        $expected = new EwsType\RestrictionType();
        $expected->AndRestriction = array(new EwsType\AndType());
        $expected->AndRestriction[0]->AndExpr = array(new EwsType\AndType());
        $expected->AndRestriction[0]->AndExpr[0]->Contains = array(
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
                'val11',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            ),
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
                'val12',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            )
        );
        $expected->AndRestriction[0]->Contains = array(
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
                'val2',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            )
        );

        $actual = $this->builder->buildRestriction($expr);

        $this->assertEquals($expected, $actual);
    }

    public function testAndOperatorWithTwoContainsItems()
    {
        $expr = new SearchQueryExpr();
        $expr->add(
            new SearchQueryExprItem(
                'subject',
                'val1',
                SearchQueryOperator::EQ,
                SearchQueryMatch::DEFAULT_MATCH,
                true
            )
        );
        $expr->add(new SearchQueryExprOperator('AND'));
        $expr->add(
            new SearchQueryExprItem(
                'subject',
                'val2',
                SearchQueryOperator::EQ,
                SearchQueryMatch::DEFAULT_MATCH,
                true
            )
        );

        $expected = new EwsType\RestrictionType();
        $expected->AndRestriction = array(new EwsType\AndType());
        $expected->AndRestriction[0]->Contains = array(
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
                'val1',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            ),
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
                'val2',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            )
        );

        $actual = $this->builder->buildRestriction($expr);

        $this->assertEquals($expected, $actual);
    }

    public function testAndOperatorWithTwoNotContainsItems()
    {
        $expr = new SearchQueryExpr();
        $expr->add(new SearchQueryExprOperator('NOT'));
        $expr->add(
            new SearchQueryExprItem('subject', 'val1', SearchQueryOperator::EQ, SearchQueryMatch::DEFAULT_MATCH, true)
        );
        $expr->add(new SearchQueryExprOperator('AND'));
        $expr->add(new SearchQueryExprOperator('NOT'));
        $expr->add(
            new SearchQueryExprItem('subject', 'val2', SearchQueryOperator::EQ, SearchQueryMatch::DEFAULT_MATCH, true)
        );

        $expected = new EwsType\RestrictionType();
        $expected->AndRestriction = array(new EwsType\AndType());
        $expected->AndRestriction[0]->NotExpr = array(new EwsType\NotType());
        $expected->AndRestriction[0]->NotExpr[0]->Contains = array(
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
                'val1',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            )
        );
        $expected->AndRestriction[0]->NotExpr[] = new EwsType\NotType();
        $expected->AndRestriction[0]->NotExpr[1]->Contains = array(
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
                'val2',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            )
        );

        $actual = $this->builder->buildRestriction($expr);

        $this->assertEquals($expected, $actual);
    }

    public function testAndOperatorWithThreeContainsItems()
    {
        $expr = new SearchQueryExpr();
        $expr->add(
            new SearchQueryExprItem(
                'subject',
                'val1',
                SearchQueryOperator::EQ,
                SearchQueryMatch::DEFAULT_MATCH,
                true
            )
        );
        $expr->add(new SearchQueryExprOperator('AND'));
        $expr->add(
            new SearchQueryExprItem(
                'subject',
                'val2',
                SearchQueryOperator::EQ,
                SearchQueryMatch::DEFAULT_MATCH,
                true
            )
        );
        $expr->add(new SearchQueryExprOperator('AND'));
        $expr->add(
            new SearchQueryExprItem(
                'subject',
                'val3',
                SearchQueryOperator::EQ,
                SearchQueryMatch::DEFAULT_MATCH,
                true
            )
        );

        $expected = new EwsType\RestrictionType();
        $expected->AndRestriction = array();
        $andForVal1AndVal2 = new EwsType\AndType();
        $andForVal1AndVal2->Contains = array(
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
                'val1',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            ),
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
                'val2',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            )
        );

        $andForVal3 = new EwsType\AndType();
        $andForVal3->Contains = array(
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
                'val3',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            )
        );
        $andForVal3->AndExpr = array($andForVal1AndVal2);

        $expected->AndRestriction[] = $andForVal3;

        $actual = $this->builder->buildRestriction($expr);

        $this->assertEquals($expected, $actual);
    }

    public function testParticipantsItems()
    {
        $expr = new SearchQueryExpr();
        $expr->add(
            new SearchQueryExprItem(
                'participants',
                'val',
                SearchQueryOperator::EQ,
                SearchQueryMatch::DEFAULT_MATCH,
                true
            )
        );

        $expected = new EwsType\RestrictionType();
        $expected->OrRestriction = array(new EwsType\OrType());
        $expected->OrRestriction[0]->Contains = array(
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::MESSAGE_TO_RECIPIENTS,
                'val',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            ),
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::MESSAGE_CC_RECIPIENTS,
                'val',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            ),
            $this->buildContainsExpression(
                EwsType\UnindexedFieldURIType::MESSAGE_BCC_RECIPIENTS,
                'val',
                EwsType\ContainmentModeType::SUBSTRING,
                EwsType\ContainmentComparisonType::IGNORE_CASE
            )
        );

        $actual = $this->builder->buildRestriction($expr);

        $this->assertEquals($expected, $actual);
    }

    private function buildContainsExpression($fieldURI, $value, $containmentMode, $containmentComparison)
    {
        $expr = new EwsType\ContainsExpressionType();
        $expr->FieldURI = array(new EwsType\PathToUnindexedFieldType());
        $expr->FieldURI[0]->FieldURI = $fieldURI;
        $expr->Constant = new EwsType\ConstantValueType();
        $expr->Constant->Value = $value;
        $expr->ContainmentMode = $containmentMode;
        $expr->ContainmentComparison = $containmentComparison;
        return $expr;
    }

    private function buildIsLessThanExpression($fieldURI, $value)
    {
        $expr = new EwsType\IsLessThanType();
        $expr->FieldURI = array(new EwsType\PathToUnindexedFieldType());
        $expr->FieldURI[0]->FieldURI = $fieldURI;
        $expr->FieldURIOrConstant = new EwsType\FieldURIOrConstantType();
        $expr->FieldURIOrConstant->Constant = new EwsType\ConstantValueType();
        $expr->FieldURIOrConstant->Constant->Value = $value;
        return $expr;
    }

    private function buildIsGreaterThanExpression($fieldURI, $value)
    {
        $expr = new EwsType\IsGreaterThanType();
        $expr->FieldURI = array(new EwsType\PathToUnindexedFieldType());
        $expr->FieldURI[0]->FieldURI = $fieldURI;
        $expr->FieldURIOrConstant = new EwsType\FieldURIOrConstantType();
        $expr->FieldURIOrConstant->Constant = new EwsType\ConstantValueType();
        $expr->FieldURIOrConstant->Constant->Value = $value;
        return $expr;
    }

    private function callConvertExprToRPN(SearchQueryExpr $expr)
    {
        $class = new \ReflectionClass($this->builder);
        $method = $class->getMethod('convertExprToRPN');
        $method->setAccessible(true);
        return $method->invokeArgs($this->builder, array($expr));
    }

    private static function createSearchQuery()
    {
        return new SearchQuery(
            new QueryStringBuilder(),
            new RestrictionBuilder()
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function convertExprToRPNProvider()
    {
        return array(
            'val' => array(
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                ),
            ),
            'NOT val' => array(
                array(
                    new SearchQueryExprOperator('NOT'),
                    new SearchQueryExprItem(
                        'subject',
                        'val',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('NOT'),
                ),
            ),
            'val1 AND val2' => array(
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                ),
            ),
            'val1 OR val2' => array(
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                ),
            ),
            'NOT val1 AND NOT val2' => array(
                array(
                    new SearchQueryExprOperator('NOT'),
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprOperator('NOT'),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('NOT'),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('NOT'),
                    new SearchQueryExprOperator('AND'),
                ),
            ),
            'NOT (val1 OR val2)' => array(
                array(
                    new SearchQueryExprOperator('NOT'),
                    new SearchQueryExprOperator('('),
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator(')'),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprOperator('NOT'),
                ),
            ),
            'val1 AND val2 AND val3' => array(
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                ),
            ),
            'val1 OR val2 OR val2' => array(
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                ),
            ),
            'NOT (val1 OR val2 OR val2)' => array(
                array(
                    new SearchQueryExprOperator('NOT'),
                    new SearchQueryExprOperator('('),
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator(')'),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprOperator('NOT'),
                ),
            ),
            'val1 AND val2 OR val3' => array(
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                ),
            ),
            'val1 AND (val2 OR val3)' => array(
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprOperator('('),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator(')'),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprOperator('AND'),
                ),
            ),
            'val1 AND (val2 AND val3)' => array(
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprOperator('('),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator(')'),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprOperator('AND'),
                ),
            ),
            'val1 AND (val2 AND val3 AND val4)' => array(
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprOperator('('),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val4',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator(')'),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val4',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprOperator('AND'),
                ),
            ),
            'val1 OR val2 OR val3 AND val4' => array(
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val4',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val4',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                ),
            ),
            '(val1 OR val2 OR val3) AND val4' => array(
                array(
                    new SearchQueryExprOperator('('),
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator(')'),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val4',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val4',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                ),
            ),
            'val1 AND val2 AND val3 OR val4' => array(
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val4',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val4',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                ),
            ),
            '(val1 AND val2 AND val3) OR val4' => array(
                array(
                    new SearchQueryExprOperator('('),
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator(')'),
                    new SearchQueryExprOperator('OR'),
                    new SearchQueryExprItem(
                        'subject',
                        'val4',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                ),
                array(
                    new SearchQueryExprItem(
                        'subject',
                        'val1',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprItem(
                        'subject',
                        'val2',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val3',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('AND'),
                    new SearchQueryExprItem(
                        'subject',
                        'val4',
                        SearchQueryOperator::EQ,
                        SearchQueryMatch::DEFAULT_MATCH,
                        true
                    ),
                    new SearchQueryExprOperator('OR'),
                ),
            ),
        );
    }
}
