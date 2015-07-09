<?php

namespace OroPro\Bundle\UserBundle\Autocomplete;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\OrganizationBundle\Autocomplete\OrganizationSearchHandler as BaseOrganizationSearchHandler;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class OrganizationSearchHandler implements SearchHandlerInterface
{
    /**
     * @var BaseOrganizationSearchHandler
     */
    protected $baseOrganizationSearchHandler;

    /**
     * @var ServiceLink
     */
    protected $securityContextLink;

    /**
     * @param BaseOrganizationSearchHandler $baseOrganizationSearchHandler
     * @param ServiceLink $securityContextLink
     */
    public function __construct(
        BaseOrganizationSearchHandler $baseOrganizationSearchHandler,
        ServiceLink $securityContextLink
    ) {
        $this->baseOrganizationSearchHandler = $baseOrganizationSearchHandler;
        $this->securityContextLink = $securityContextLink;
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        $result = $this->baseOrganizationSearchHandler->search($query, $page, $perPage, $searchById);
        /** @var User $user */
        $user = $this->securityContextLink->getService()->getToken()->getUser();
        $organizations = $user->getOrganizations();
        $hasGlobalOrganization = $organizations->exists(
            function ($key, Organization $organization) {
                return $organization->getIsGlobal();
            }
        );
        if (!$hasGlobalOrganization) {
            $organizationIds = $organizations
                ->map(
                    function (Organization $organization) {
                        return $organization->getId();
                    }
                )->toArray();
            $result['results'] = array_filter(
                $result['results'],
                function ($element) use ($organizationIds) {
                    return in_array($element['id'], $organizationIds, true);
                }
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return $this->baseOrganizationSearchHandler->getProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityName()
    {
        return $this->baseOrganizationSearchHandler->getEntityName();
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        return $this->baseOrganizationSearchHandler->convertItem($item);
    }
}
