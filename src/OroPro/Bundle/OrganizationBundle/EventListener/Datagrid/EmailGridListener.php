<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener\Datagrid;

use Symfony\Component\Security\Core\SecurityContext;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\EmailBundle\EventListener\Datagrid\EmailGridListener as BaseListener;

class EmailGridListener extends BaseListener
{
    /**
     * @var SecurityContext
     */
    protected $securityContext;

    public function __construct(AclHelper $aclHelper, SecurityContext $securityContext)
    {
        parent::__construct($aclHelper);

        $this->securityContext = $securityContext;
    }

    /**
     * @param $queryBuilder
     */
    protected function applyAcl($queryBuilder)
    {
        /** @var Organization $organization */
        $organization = $this->securityContext->getToken()->getOrganizationContext();
        if (!$organization->getIsGlobal()) {
            $this->aclHelper->apply($queryBuilder, 'VIEW');
        }
    }
}
