<?php

namespace OroPro\Bundle\SecurityBundle\ImportExport\Reader;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ImportExportBundle\Reader\EntityReader;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EntityProReader extends EntityReader
{
    /** @var securityFacade */
    protected $securityFacade;

    /**
     * @param securityFacade $securityFacade
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    protected function addOrganizationLimits(QueryBuilder $queryBuilder, $entityName, Organization $organization = null)
    {
        // if user works in system access organization - we should not limit data by organization
        if ($this->securityFacade->getOrganization() && $this->securityFacade->getOrganization()->getIsGlobal()) {
            return;
        }

        parent::addOrganizationLimits($queryBuilder, $entityName, $organization);
    }
}
