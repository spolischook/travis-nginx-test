<?php

namespace OroPro\Bundle\EwsBundle\Connector\Search;

interface SearchQueryExprNamedItemInterface extends SearchQueryExprValueInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     */
    public function setName($name);
}
