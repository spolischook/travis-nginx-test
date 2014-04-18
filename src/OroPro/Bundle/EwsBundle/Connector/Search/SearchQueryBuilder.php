<?php

namespace OroPro\Bundle\EwsBundle\Connector\Search;

use \Closure;

class SearchQueryBuilder extends AbstractSearchQueryBuilder
{
    /**
     * Sets the search query type.
     *
     * @param int $queryType Can be one of SearchQuery::QUERY_STRING or SearchQuery::RESTRICTION
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setQueryType($queryType)
    {
        if ($queryType !== SearchQuery::QUERY_STRING && $queryType !== SearchQuery::RESTRICTION) {
            throw new \InvalidArgumentException('The query type should be QUERY_STRING or RESTRICTION.');
        }
        $this->query->setQueryType($queryType);
        return $this;
    }


    /**
     * Search in all word phase properties.
     *
     * @param string $value
     * @param int    $match
     *
     * @return $this
     */
    public function value($value, $match = SearchQueryMatch::DEFAULT_MATCH)
    {
        $this->query->value($value, $match);
        return $this;
    }

    /**
     * Search by FROM field.
     *
     * @param string|Closure $value
     * @param int $match The match type. One of SearchQueryMatch::* values
     * @return $this
     */
    public function from($value, $match = SearchQueryMatch::DEFAULT_MATCH)
    {
        $this->processField('from', $value, $match);
        return $this;
    }

    /**
     * Search by TO field.
     *
     * @param string|Closure $value
     * @param int $match The match type. One of SearchQueryMatch::* values
     * @return $this
     */
    public function to($value, $match = SearchQueryMatch::DEFAULT_MATCH)
    {
        $this->processField('to', $value, $match);
        return $this;
    }

    /**
     * Search by CC field.
     *
     * @param string|Closure $value
     * @param int $match The match type. One of SearchQueryMatch::* values
     * @return $this
     */
    public function cc($value, $match = SearchQueryMatch::DEFAULT_MATCH)
    {
        $this->processField('cc', $value, $match);
        return $this;
    }

    /**
     * Search by BCC field.
     *
     * @param string|Closure $value
     * @param int $match The match type. One of SearchQueryMatch::* values
     * @return $this
     */
    public function bcc($value, $match = SearchQueryMatch::DEFAULT_MATCH)
    {
        $this->processField('bcc', $value, $match);
        return $this;
    }

    /**
     * Search by TO, CC, or BCC fields.
     *
     * @param string|Closure $value
     * @param int $match The match type. One of SearchQueryMatch::* values
     * @return $this
     */
    public function participants($value, $match = SearchQueryMatch::DEFAULT_MATCH)
    {
        $this->processField('participants', $value, $match);
        return $this;
    }

    /**
     * Search by SUBJECT field.
     *
     * @param string|Closure $value
     * @param int $match The match type. One of SearchQueryMatch::* values
     * @return $this
     */
    public function subject($value, $match = SearchQueryMatch::DEFAULT_MATCH)
    {
        $this->processField('subject', $value, $match);
        return $this;
    }

    /**
     * Search by BODY field.
     *
     * @param string|Closure $value
     * @param int $match The match type. One of SearchQueryMatch::* values
     * @return $this
     */
    public function body($value, $match = SearchQueryMatch::DEFAULT_MATCH)
    {
        $this->processField('body', $value, $match);
        return $this;
    }

    /**
     * Search by the attachment file name.
     *
     * @param string|Closure $value
     * @param int $match The match type. One of SearchQueryMatch::* values
     * @return $this
     */
    public function attachment($value, $match = SearchQueryMatch::DEFAULT_MATCH)
    {
        $this->processField('attachment', $value, $match);
        return $this;
    }

    /**
     * Search by SENT field.
     *
     * @param string $fromValue
     * @param string $toValue
     * @param bool $includeGivenValue
     *   If this flag is true the given fromValue or toValue will be included in the search condition.
     *   If this flag is null only fromValue will be used
     * @return $this
     */
    public function sent($fromValue = null, $toValue = null, $includeGivenValue = true)
    {
        $this->processDateField('sent', $fromValue, $toValue, $includeGivenValue);
        return $this;
    }

    /**
     * Search by RECEIVED field.
     *
     * @param string $fromValue
     * @param string $toValue
     * @param bool $includeGivenValue
     *   If this flag is true the given fromValue or toValue will be included in the search condition.
     *   If this flag is null only fromValue will be used
     * @return $this
     */
    public function received($fromValue = null, $toValue = null, $includeGivenValue = true)
    {
        $this->processDateField('received', $fromValue, $toValue, $includeGivenValue);
        return $this;
    }

    private function processDateField($name, $fromValue = null, $toValue = null, $includeGivenValue = null)
    {
        if ($includeGivenValue === null && $fromValue != null && $toValue == null) {
            $this->query->item($name, $fromValue);
        } else {
            if ($fromValue == null && $toValue != null) {
                $this->query->item(
                    $name,
                    $toValue,
                    $includeGivenValue ? SearchQueryOperator::LE : SearchQueryOperator::LT
                );
            } elseif ($fromValue != null && $toValue == null) {
                $this->query->item(
                    $name,
                    $fromValue,
                    $includeGivenValue ? SearchQueryOperator::GE : SearchQueryOperator::GT
                );
            } else {
                $this->query->itemRange($name, $fromValue, $toValue);
            }
        }
    }

    private function processField($name, $value, $match, $operator = SearchQueryOperator::EQ)
    {
        if ($value instanceof Closure) {
            $exprBuilder = new SearchQueryValueBuilder($this->query->newInstance());
            call_user_func($value, $exprBuilder);
            $value = $exprBuilder->get();
        }
        $this->query->item($name, $value, $operator, $match);
    }
}
