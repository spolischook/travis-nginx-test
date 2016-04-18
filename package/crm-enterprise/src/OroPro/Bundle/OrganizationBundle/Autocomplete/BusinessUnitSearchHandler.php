<?php

namespace OroPro\Bundle\OrganizationBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * Class BusinessUnitSearchHandler
 * @package OroPro\Bundle\OrganizationBundle\Autocomplete
 */
class BusinessUnitSearchHandler extends SearchHandler
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param string         $entityName
     * @param array          $properties
     * @param SecurityFacade $securityFacade
     */
    public function __construct($entityName, array $properties, SecurityFacade $securityFacade)
    {
        parent::__construct($entityName, $properties);

        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        $result = [];

        if ($this->idFieldName) {
            $result[$this->idFieldName] = $this->getPropertyValue($this->idFieldName, $item);
        }

        foreach ($this->properties as $property) {
            $result[$property] = $this->getPropertyValue($property, $item);

            $globalOrganization = $this->securityFacade->getOrganization();

            if ($globalOrganization->getIsGlobal() && $organization = $this->getPropertyValue('organization', $item)) {
                $result[$property] .= ' (' . $this->getPropertyValue('name', $organization) . ')';
            }
        }

        return $result;
    }
}
