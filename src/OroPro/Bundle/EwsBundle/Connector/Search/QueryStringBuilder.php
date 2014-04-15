<?php

namespace OroPro\Bundle\EwsBundle\Connector\Search;

/**
 * Provides functionality to build EWS QueryString based on given SearchQuery expression.
 */
class QueryStringBuilder
{
    /**
     * Builds a string representation of the search query which can be passed to Exchange Web Services (EWS).
     *
     * @param SearchQueryExpr $searchQueryExpr
     * @see SearchQuery
     *
     * @return string
     */
    public function buildQueryString(SearchQueryExpr $searchQueryExpr)
    {
        return $this->processExpr($searchQueryExpr);
    }

    /**
     * @param SearchQueryExpr $expr The search expression
     * @return string
     */
    protected function processExpr(SearchQueryExpr $expr)
    {
        $result = '';
        $needWhitespace = false;
        foreach ($expr as $item) {
            if ($item instanceof SearchQueryExprOperator) {
                if ($needWhitespace && $item->getName() !== ')') {
                    $result .= ' ';
                }
                $operator = $item->getName();
                if ($operator !== '') {
                    $result .= $operator;
                    $needWhitespace = ($item->getName() !== '(');
                } else {
                    $needWhitespace = false;
                }
            } else {
                if ($needWhitespace) {
                    $result .= ' ';
                }
                if ($item instanceof SearchQueryExprNamedItemInterface) {
                    $result .= $item->getName() . ':';
                }
                $result .= $item instanceof SearchQueryExpr
                    ? $this->processSubQueryValue($item)
                    : $this->processValue($item);
                $needWhitespace = true;
            }
        }

        return $result;
    }

    /**
     * Builds a string representation of the value of a search query element.
     * @param SearchQueryExprValueInterface $item
     * @return string
     */
    protected function processValue(SearchQueryExprValueInterface $item)
    {
        $result = '';
        if ($item instanceof SearchQueryExprValueBase) {
            $value = $item->getValue();
            if ($value instanceof SearchQueryExpr) {
                if (!$value->isEmpty()) {
                    $result = $this->processSubQueryValue($value);
                    if ($item instanceof SearchQueryExprItem) {
                        $result = $item->getOperator() . $result;
                    }
                }
            } else {
                $result = $this->processSimpleValue($value, $item->getMatch());
                if ($item instanceof SearchQueryExprItem) {
                    $result = $item->getOperator() . $result;
                }
            }
        } elseif ($item instanceof SearchQueryExprRangeItem) {
            $result = $this->processRangeValue($item);
        }

        return $result;
    }

    /**
     * @param SearchQueryExpr $value The sub query
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function processSubQueryValue(SearchQueryExpr $value)
    {
        if ($value->isEmpty()) {
            return '';
        }

        $expr = $this->processExpr($value);
        return $value->isComplex()
            ? sprintf('(%s)', $expr)
            : $expr;
    }

    /**
     * @param string $value
     * @param int $match One of SearchQueryMatch::* values
     * @see SearchQueryMatch
     * @return string
     */
    protected function processSimpleValue($value, $match)
    {
        if ($value instanceof \DateTime) {
            $value = $value->format('c');
            $value = str_replace('+00:00', 'Z', $value);
        }

        switch ($match) {
            case SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH:
                return '"' . $value . '"';
            case SearchQueryMatch::PREFIX_WITH_ORDER_RESTRICTED_MATCH:
                return '"' . $value . '"*';
            default: // DEFAULT_MATCH and PREFIX_MATCH
                return $value;
        }
    }

    /**
     * @param SearchQueryExprRangeItem $item
     * @return string
     */
    protected function processRangeValue(SearchQueryExprRangeItem $item)
    {
        return $item->getFromValue() . '..' . $item->getToValue();
    }
}
