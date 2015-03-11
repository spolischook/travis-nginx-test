<?php

namespace OroPro\Bundle\OrganizationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroPro\Bundle\OrganizationBundle\Entity\UserPreferredOrganization;
use OroPro\Bundle\OrganizationConfigBundle\Config\UserOrganizationScopeManager;

class UserPreferredOrganizationRepository extends EntityRepository
{
    /**
     * Returns user preferred organization
     *
     * @param User $user
     * @param Organization $organization
     * @return UserPreferredOrganization
     */
    public function getPreferredOrganization(User $user, Organization $organization)
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->select('e')
            ->where('e.user = :user')
            ->andWhere('e.organization = :organization')
            ->setParameter('user', $user)
            ->setParameter('organization', $organization);
        $queryBuilder->getMaxResults(1);
        $result = $queryBuilder->getQuery()->getResult();
        if (count($result) > 0) {
            return $result[0];
        }
    }

    /**
     * Removes existing entry and creates new one for the user
     *
     * @param User         $user
     * @param Organization $organization
     */
    public function updatePreferredOrganization(User $user, Organization $organization)
    {
        $em = $this->getEntityManager();

        $removeQB = $em->createQueryBuilder()
            ->select('e')
            ->from($this->getEntityName(), 'e')
            ->where('e.user = :user')
            ->setParameter('user', $user);
        $rows = $removeQB->getQuery()->execute();
        $preferredOrgIds = [];
        foreach ($rows as $row) {
            $preferredOrgIds[] = $row->getId();
            $em->remove($row);
        }
        $em->flush();

        $this->savePreferredOrganization($user, $organization, $preferredOrgIds);
    }

    /**
     * Creates entry for user preferred organization
     *
     * @param User $user
     * @param Organization $organization
     * @param array $preferredOrgIds
     * @return UserPreferredOrganization
     */
    public function savePreferredOrganization(User $user, Organization $organization, $preferredOrgIds = [])
    {
        $em = $this->getEntityManager();

        $entry = new UserPreferredOrganization($user, $organization);
        $em->persist($entry);
        $em->flush($entry);

        if (count($preferredOrgIds) > 0) {
            $queryBuilder = $em->getRepository('OroConfigBundle:Config')->createQueryBuilder('e');
            $queryBuilder->update()
                ->set('e.recordId', $entry->getId())
                ->where('e.scopedEntity = :scopedEntity')
                ->andWhere($queryBuilder->expr()->in('e.recordId', $preferredOrgIds))
                ->setParameter('scopedEntity', UserOrganizationScopeManager::SCOPED_ENTITY_NAME)
                ->getQuery()
                ->execute();
        }

        return $entry;
    }
}
