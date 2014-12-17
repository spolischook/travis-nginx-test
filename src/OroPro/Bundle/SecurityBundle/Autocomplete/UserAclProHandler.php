<?php

namespace OroPro\Bundle\SecurityBundle\Autocomplete;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Autocomplete\UserAclHandler;
use Oro\Bundle\UserBundle\Entity\User;

use OroPro\Bundle\OrganizationBundle\Provider\OrganizationIdProvider;

class UserAclProHandler extends UserAclHandler
{
    /** @var OrganizationIdProvider */
    protected $organizationIdProvider;

    /**
     * @param OrganizationIdProvider $organizationIdProvider
     */
    public function setOrganizationIdProvider(OrganizationIdProvider $organizationIdProvider)
    {
        $this->organizationIdProvider = $organizationIdProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyAcl(QueryBuilder $queryBuilder, $accessLevel, User $user, Organization $organization)
    {
        // in System mode we should limit data by selected organization
        if ($organization->getIsGlobal() && $this->organizationIdProvider->getOrganizationId()) {
            $organization = $this->organizationIdProvider->getOrganization();
            $queryBuilder->join('user.organizations', 'org')
                ->andWhere($queryBuilder->expr()->in('org.id', [$organization->getId()]));

            return $queryBuilder->getQuery();
        }

        return parent::applyAcl($queryBuilder, $accessLevel, $user, $organization);
    }
}
