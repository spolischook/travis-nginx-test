<?php

namespace OroPro\Bundle\OrganizationBundle\Helper;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

class OrganizationProHelper
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var null|bool|Organization */
    private $systemOrganization = null;

    /**
     * @param ManagerRegistry       $doctrine
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ManagerRegistry $doctrine, TokenStorageInterface $tokenStorage)
    {
        $this->doctrine     = $doctrine;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Returns true if the system have a global organization
     *
     * @return bool
     */
    public function isGlobalOrganizationExists()
    {
        return $this->getGlobalOrganization() ? true : false;
    }

    /**
     * Get current global organization id or null if the system has no global organization
     *
     * @return int|null
     */
    public function getGlobalOrganizationId()
    {
        $organization = $this->getGlobalOrganization();

        return $organization ? $organization->getId() : null;
    }

    /**
     * Return global organization or false if global organization is not exists
     *
     * @return Organization|false
     */
    public function getGlobalOrganization()
    {
        if ($this->systemOrganization === null) {
            $organization = $this->getRepo()->createQueryBuilder('org')
                ->select('org')
                ->where('org.is_global = :isGlobal')
                ->setParameter('isGlobal', true)
                ->getQuery()
                ->getOneOrNullResult();
            $this->systemOrganization = $organization ?: false;
        }

        return $this->systemOrganization;
    }

    /**
     * Returns options to Organization filter in the grid. In this filter user can see all Organizations only if he is
     * logged in to Global Organization. Otherwise only current Organization is available.
     *
     * @return array
     */
    public function getOrganizationFilterChoices()
    {
        $currentOrganization = $this->getCurrentOrganization();

        if (!$currentOrganization) {
            return [];
        }

        if ($currentOrganization->getIsGlobal()) {
            /** @var OrganizationRepository $organizationRepository */
            $organizationRepository = $this->doctrine->getRepository('OroOrganizationBundle:Organization');
            $organizations = $organizationRepository->getEnabled(false, ['name' => 'ASC']);
        } else {
            $organizations = [$currentOrganization];
        }

        $result = [];

        foreach ($organizations as $organization) {
            $result[$organization->getId()] = $organization->getName();
        }

        return $result;
    }

    /**
     * @return Organization|null
     */
    protected function getCurrentOrganization()
    {
        $token = $this->tokenStorage->getToken();

        if (!$token instanceof OrganizationContextTokenInterface) {
            return null;
        }

        return $token->getOrganizationContext();
    }

    /**
     * Returns organization entity repository
     *
     * @return OrganizationRepository
     */
    protected function getRepo()
    {
        return $this->doctrine->getRepository('OroOrganizationBundle:Organization');
    }
}
