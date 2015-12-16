<?php

namespace OroPro\Bundle\OrganizationBundle\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;

class OrganizationProHelper
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Returns true if the system have a global organization
     *
     * @return bool
     */
    public function isGlobalOrganizationExists()
    {
        $count = $this->getRepo()->createQueryBuilder('org')
            ->select('COUNT(org.id)')
            ->where('org.is_global = :isGlobal')
            ->setParameter('isGlobal', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $count ? true : false;
    }

    /**
     * Get current global organization id or null if the system has no global organization
     *
     * @return int|null
     */
    public function getGlobalOrganizationId()
    {
        $result = $this->getRepo()->createQueryBuilder('org')
            ->select('org.id as id')
            ->where('org.is_global = :isGlobal')
            ->setParameter('isGlobal', true)
            ->getQuery()
            ->getOneOrNullResult();

        return is_null($result) ? null : $result['id'];
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
