<?php

namespace OroProfessional\Bundle\EwsBundle\Connector\Search;

class SearchQueryValueBuilder extends AbstractSearchQueryBuilder
{
    public function value($value)
    {
        $this->query->value($value);
        return $this;
    }
}
