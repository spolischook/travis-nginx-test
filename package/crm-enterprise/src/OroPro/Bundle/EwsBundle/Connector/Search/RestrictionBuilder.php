<?php

namespace OroPro\Bundle\EwsBundle\Connector\Search;

use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;

/**
 * Provides functionality to build EWS Restriction based on given SearchQuery expression.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class RestrictionBuilder
{
    /**
     * Matches the name of the search query items to the correspond EWS unindexed fields
     *
     * @var array
     */
    private static $unindexedFields = array(
        'from' => EwsType\UnindexedFieldURIType::MESSAGE_FROM,
        'to' => EwsType\UnindexedFieldURIType::MESSAGE_TO_RECIPIENTS,
        'cc' => EwsType\UnindexedFieldURIType::MESSAGE_CC_RECIPIENTS,
        'bcc' => EwsType\UnindexedFieldURIType::MESSAGE_BCC_RECIPIENTS,
        'participants' => array(
            EwsType\UnindexedFieldURIType::MESSAGE_TO_RECIPIENTS,
            EwsType\UnindexedFieldURIType::MESSAGE_CC_RECIPIENTS,
            EwsType\UnindexedFieldURIType::MESSAGE_BCC_RECIPIENTS
        ),
        'subject' => EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
        'body' => EwsType\UnindexedFieldURIType::ITEM_BODY,
        'attachment' => EwsType\UnindexedFieldURIType::ITEM_ATTACHMENTS,
        'sent' => EwsType\UnindexedFieldURIType::ITEM_DATE_TIME_SENT,
        'received' => EwsType\UnindexedFieldURIType::ITEM_DATE_TIME_RECEIVED
    );

    /**
     * Builds the Exchange Web Services (EWS) RestrictionType object represents the search query.
     *
     * @param SearchQueryExpr $searchQueryExpr
     *
     * @throws \LogicException
     * @return EwsType\RestrictionType
     */
    public function buildRestriction(SearchQueryExpr $searchQueryExpr)
    {
        $result = new EwsType\RestrictionType();

        $operands = array();
        foreach ($this->convertExprToRPN($searchQueryExpr) as $item) {
            if ($item instanceof SearchQueryExprOperator) {
                $operands[] = $this->createExpr($item, $operands);
            } elseif ($item instanceof SearchQueryExprValueInterface) {
                $operands[] = $this->createOperand($item);
            } else {
                throw new \LogicException('Incorrect item type.');
            }
        }

        $this->addOperands($result, $operands);

        return $result;
    }

    /**
     * @param SearchQueryExprOperator $operator
     * @param RestrictionBuilderOperand[] $operands
     * @return RestrictionBuilderOperand
     * @throws \InvalidArgumentException
     */
    protected function createExpr(SearchQueryExprOperator $operator, &$operands)
    {
        switch ($operator->getName()) {
            case 'AND':
                $result = new RestrictionBuilderOperand('And', new EwsType\AndType());
                $numberOfOperands = 2;
                break;
            case 'OR':
                $result = new RestrictionBuilderOperand('Or', new EwsType\OrType());
                $numberOfOperands = 2;
                break;
            case 'NOT':
                $result = new RestrictionBuilderOperand('Not', new EwsType\NotType());
                $numberOfOperands = 1;
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf('Incorrect value of the operator argument: %s.', $operator->getName())
                );
        }

        $operandsForExpr = array();
        while ($numberOfOperands > 0) {
            array_push($operandsForExpr, array_pop($operands));
            $numberOfOperands--;
        }

        $this->addOperands($result->getElement(), $operandsForExpr);

        return $result;
    }

    /**
     * @param mixed $destElement The destination EWS element where operands are added
     * @param RestrictionBuilderOperand[] $operands
     */
    protected function addOperands($destElement, array $operands)
    {
        foreach ($operands as $operand) {
            $operandType = $operand->getType();
            $expr = $destElement->{$operandType};
            if (null === $expr) {
                $expr = array();
            }
            array_unshift($expr, $operand->getElement());
            $destElement->{$operandType} = $expr;
        }
    }

    /**
     * @param SearchQueryExprValueInterface $item
     * @return RestrictionBuilderOperand
     * @throws \LogicException
     */
    protected function createOperand(SearchQueryExprValueInterface $item)
    {
        if ($item instanceof SearchQueryExprItem) {
            $value = $item->getValue();
            if ($value instanceof SearchQueryExpr) {
                return $this->parseSubQueryValue($item);
            } else {
                return $this->parseSimpleValue($item);
            }
        } elseif ($item instanceof SearchQueryExprRangeItem) {
            return $this->parseRangeValue($item);
        } else {
            throw new \InvalidArgumentException('Incorrect type of the item argument.');
        }
    }

    /**
     * @param SearchQueryExprItem $item
     * @return RestrictionBuilderOperand
     * @throws \LogicException
     */
    protected function parseSubQueryValue(SearchQueryExprItem $item)
    {
        $value = $item->getValue();
        if ($value->isEmpty()) {
            throw new \LogicException('The sub query should not be empty.');
        }

        $operands = array();
        foreach ($this->convertExprToRPN($value) as $subItem) {
            if ($subItem instanceof SearchQueryExprValue) {
                $mergedSubItem = $this->mergeSubQueryItem($subItem, $item);
                $operands[] = $this->createOperand($mergedSubItem);
            } elseif ($subItem instanceof SearchQueryExprOperator) {
                $operands[] = $this->createExpr($subItem, $operands);
            } elseif ($subItem instanceof SearchQueryExpr) {
                $mergedSubItem = $this->mergeSubQueryItem(
                    new SearchQueryExprValue($subItem, SearchQueryMatch::DEFAULT_MATCH),
                    $item
                );
                $operands[] = $this->parseSubQueryValue($mergedSubItem, $operands);
            }
        }
        if (count($operands) !== 1) {
            throw new \LogicException(
                sprintf(
                    'It is expected that the sub query is parsed to one operand ' .
                    'but actually it is %d operands. Item name: %s',
                    count($operands),
                    $item['name']
                )
            );
        }

        return $operands[0];
    }

    /**
     * @param SearchQueryExprValue $item
     * @param SearchQueryExprItem $parentItem
     * @return SearchQueryExprItem
     */
    private function mergeSubQueryItem(SearchQueryExprValue $item, SearchQueryExprItem $parentItem)
    {
        return new SearchQueryExprItem(
            $parentItem->getName(),
            $item->getValue(),
            $parentItem->getOperator(),
            $parentItem->getMatch(),
            $parentItem->getIgnoreCase()
        );
    }

    /**
     * @param SearchQueryExprItem $item
     * @return RestrictionBuilderOperand
     * @throws \LogicException
     */
    protected function parseSimpleValue(SearchQueryExprItem $item)
    {
        switch ($item->getOperator()) {
            case SearchQueryOperator::EQ:
                return $this->createContainsOperand(
                    $item->getName(),
                    $item->getValue(),
                    $item->getMatch(),
                    $item->getIgnoreCase()
                );
            case SearchQueryOperator::NEQ:
                $containsOperand = $this->createContainsOperand(
                    $item->getName(),
                    $item->getValue(),
                    $item->getMatch(),
                    $item->getIgnoreCase()
                );
                $notExpr = new EwsType\NotType();
                $notExpr->Contains = array(
                    $containsOperand->getElement()
                );
                return new RestrictionBuilderOperand('Not', $notExpr);
            case SearchQueryOperator::LT:
                return $this->createIsLessThanOperand($item->getName(), $item->getValue());
            case SearchQueryOperator::LE:
                return $this->createIsLessThanOrEqualToOperand($item->getName(), $item->getValue());
            case SearchQueryOperator::GT:
                return $this->createIsGreaterThanOperand($item->getName(), $item->getValue());
            case SearchQueryOperator::GE:
                return $this->createIsGreaterThanOrEqualToOperand($item->getName(), $item->getValue());
            default:
                throw new \LogicException(sprintf('Incorrect operator: %s.', $item->getOperator()));
        }
    }

    /**
     * @param SearchQueryExprRangeItem $item
     * @return RestrictionBuilderOperand
     */
    protected function parseRangeValue(SearchQueryExprRangeItem $item)
    {
        $isGreaterThanElement = $this->createIsGreaterThanOperand($item->getName(), $item->getFromValue());
        $isLessThanElement = $this->createIsLessThanOperand($item->getName(), $item->getToValue());

        $andExpr = new EwsType\AndType();
        $andExpr->IsGreaterThan = array(
            $isGreaterThanElement->getElement()
        );
        $andExpr->IsLessThan = array(
            $isLessThanElement->getElement()
        );

        return new RestrictionBuilderOperand('And', $andExpr);
    }

    /**
     * @param string $name The name of EWS field
     * @param string $value The value the given EWS field is compared
     * @param int $match One of SearchQueryMatch::* values
     * @see SearchQueryMatch
     * @param bool $ignoreCase
     * @return RestrictionBuilderOperand
     */
    protected function createContainsOperand($name, $value, $match, $ignoreCase)
    {
        if (array_key_exists($name, self::$unindexedFields) && is_array(self::$unindexedFields[$name])) {
            $orExpr = new EwsType\OrType();
            $orExpr->Contains = array();
            foreach (self::$unindexedFields[$name] as $fieldURI) {
                $element = new EwsType\ContainsExpressionType();
                $element->FieldURI = array(
                    new EwsType\PathToUnindexedFieldType()
                );
                $element->FieldURI[0]->FieldURI = $fieldURI;
                $element->Constant = new EwsType\ConstantValueType();
                $element->Constant->Value = $this->normalizeValue($value);
                $element->ContainmentMode = $this->getContainmentMode($match);
                $element->ContainmentComparison = $this->getContainmentComparison($ignoreCase);
                $orExpr->Contains[] = $element;
            }

            return new RestrictionBuilderOperand('Or', $orExpr);
        }

        $result = new EwsType\ContainsExpressionType();
        $this->setFieldURI($result, $name);
        $result->Constant = new EwsType\ConstantValueType();
        $result->Constant->Value = $this->normalizeValue($value);
        $result->ContainmentMode = $this->getContainmentMode($match);
        $result->ContainmentComparison = $this->getContainmentComparison($ignoreCase);

        return new RestrictionBuilderOperand('Contains', $result);
    }

    /**
     * @param string $name The name of EWS field
     * @param string $value The value the given EWS field is compared
     * @return RestrictionBuilderOperand
     */
    protected function createIsLessThanOperand($name, $value)
    {
        return $this->createComparisonOperand(
            'IsLessThan',
            new EwsType\IsLessThanType(),
            $name,
            $value
        );
    }

    /**
     * @param string $name The name of EWS field
     * @param string $value The value the given EWS field is compared
     * @return RestrictionBuilderOperand
     */
    protected function createIsLessThanOrEqualToOperand($name, $value)
    {
        return $this->createComparisonOperand(
            'IsLessThanOrEqualTo',
            new EwsType\IsLessThanOrEqualToType(),
            $name,
            $value
        );
    }

    /**
     * @param string $name The name of EWS field
     * @param string $value The value the given EWS field is compared
     * @return RestrictionBuilderOperand
     */
    protected function createIsGreaterThanOperand($name, $value)
    {
        return $this->createComparisonOperand(
            'IsGreaterThan',
            new EwsType\IsGreaterThanType(),
            $name,
            $value
        );
    }

    /**
     * @param string $name The name of EWS field
     * @param string $value The value the given EWS field is compared
     * @return RestrictionBuilderOperand
     */
    protected function createIsGreaterThanOrEqualToOperand($name, $value)
    {
        return $this->createComparisonOperand(
            'IsGreaterThanOrEqualTo',
            new EwsType\IsGreaterThanOrEqualToType(),
            $name,
            $value
        );
    }

    /**
     * @param string $operandType The type of the operand. It is used to determine a property name in parent element.
     * @param EwsType\TwoOperandExpressionType $operandObj
     * @param string $name The name of EWS field
     * @param string $value The value the given EWS field is compared
     * @return RestrictionBuilderOperand
     */
    protected function createComparisonOperand(
        $operandType,
        EwsType\TwoOperandExpressionType $operandObj,
        $name,
        $value
    ) {
        $this->setFieldURI($operandObj, $name);
        $operandObj->FieldURIOrConstant = new EwsType\FieldURIOrConstantType();
        $operandObj->FieldURIOrConstant->Constant = new EwsType\ConstantValueType();
        $operandObj->FieldURIOrConstant->Constant->Value = $this->normalizeValue($value);

        return new RestrictionBuilderOperand($operandType, $operandObj);
    }

    /**
     * @param mixed $destElement The destination EWS element where the FieldURI is set
     * @param string $name
     */
    protected function setFieldURI($destElement, $name)
    {
        if (array_key_exists($name, self::$unindexedFields)) {
            $destElement->FieldURI = array(
                new EwsType\PathToUnindexedFieldType()
            );
            $destElement->FieldURI[0]->FieldURI = self::$unindexedFields[$name];
        }
    }

    /**
     * @param int $match One of SearchQueryMatch::* values
     * @see SearchQueryMatch
     * @return EwsType\ContainmentModeType
     * @throws \InvalidArgumentException
     */
    protected function getContainmentMode($match)
    {
        switch ($match) {
            case SearchQueryMatch::DEFAULT_MATCH:
            case SearchQueryMatch::SUBSTRING_MATCH:
                return EwsType\ContainmentModeType::SUBSTRING;
            case SearchQueryMatch::FULL_STRING_MATCH:
                return EwsType\ContainmentModeType::FULL_STRING;
            default:
                throw new \InvalidArgumentException(sprintf('Incorrect value of the match argument: %d.', $match));
        }
    }

    /**
     * @param bool $ignoreCase
     * @return EwsType\ContainmentComparisonType
     */
    protected function getContainmentComparison($ignoreCase)
    {
        return $ignoreCase
            ? EwsType\ContainmentComparisonType::IGNORE_CASE
            : EwsType\ContainmentComparisonType::EXACT;
    }

    /**
     * Converts the given search query expression to the Reverse Polish Notation (RPN).
     *
     * This function uses the modified Shunting-yard algorithm
     * @link http://en.wikipedia.org/wiki/Shunting-yard_algorithm
     *
     * @param SearchQueryExpr $searchQueryExpr
     * @return SearchQueryExpr
     * @throws \LogicException
     */
    protected function convertExprToRPN(SearchQueryExpr $searchQueryExpr)
    {
        $result = new SearchQueryExpr();
        $stack = array();
        foreach ($searchQueryExpr as $item) {
            if ($item instanceof SearchQueryExprOperator) {
                if ($item->getName() === '(') {
                    $stack[] = $item;
                } elseif ($item->getName() === ')') {
                    $stackItem = array_pop($stack);
                    while ($stackItem !== null && $stackItem->getName() !== '(') {
                        $result->add($stackItem);
                        $stackItem = array_pop($stack);
                    }
                    if ($stackItem === null) {
                        throw new \LogicException('The open parenthesis is missing.');
                    }
                } else {
                    if ($item->getName() === 'AND' || $item->getName() === 'OR') {
                        $stackItem = array_pop($stack);
                        while ($stackItem !== null) {
                            if ($stackItem->getName() === '(') {
                                $stack[] = $stackItem;
                                break;
                            } else {
                                $result->add($stackItem);
                                $stackItem = array_pop($stack);
                            }
                        }
                    }
                    $stack[] = $item;
                }
            } else {
                $result->add($item);
            }
        }
        $stackItem = array_pop($stack);
        while ($stackItem !== null) {
            if ($stackItem->getName() === '(') {
                throw new \LogicException('The close parenthesis is missing.');
            }
            $result->add($stackItem);
            $stackItem = array_pop($stack);
        }

        return $result;
    }

    /**
     * @param mixed $value The value to be normalized
     * @return string
     */
    protected function normalizeValue($value)
    {
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d') . 'T00:00:00Z';
        }

        return $value;
    }
}
