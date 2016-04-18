<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountUserRoleRepository extends EntityRepository
{
    /**
     * @param Website $website
     * @return AccountUserRole|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDefaultAccountUserRoleByWebsite(Website $website)
    {
        $qb = $this->createQueryBuilder('accountUserRole');

        return $qb
            ->innerJoin('accountUserRole.websites', 'website')
            ->andWhere($qb->expr()->eq('website', ':website'))
            ->setParameter('website', $website)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Checks is role default for website
     *
     * @param AccountUserRole $role
     * @return bool
     */
    public function isDefaultForWebsite(AccountUserRole $role)
    {
        $qb = $this->createQueryBuilder('accountUserRole');
        $findResult = $qb
            ->select('accountUserRole.id')
            ->innerJoin('accountUserRole.websites', 'website')
            ->where($qb->expr()->eq('accountUserRole', ':accountUserRole'))
            ->setParameter('accountUserRole', $role)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        return !empty($findResult);
    }

    /**
     * Checks if there are at least one user assigned to the given role
     *
     * @param AccountUserRole $role
     * @return bool
     */
    public function hasAssignedUsers(AccountUserRole $role)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $findResult = $qb
            ->select('accountUser.id')
            ->from('OroB2BAccountBundle:AccountUser', 'accountUser')
            ->innerJoin('accountUser.roles', 'accountUserRole')
            ->where($qb->expr()->eq('accountUserRole', ':accountUserRole'))
            ->setParameter('accountUserRole', $role)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        return !empty($findResult);
    }

    /**
     * Return array of assigned users to the given role
     *
     * @param AccountUserRole $role
     * @return AccountUser[]
     */
    public function getAssignedUsers(AccountUserRole $role)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $findResult = $qb
            ->select('accountUser')
            ->from('OroB2BAccountBundle:AccountUser', 'accountUser')
            ->innerJoin('accountUser.roles', 'accountUserRole')
            ->where($qb->expr()->eq('accountUserRole', ':accountUserRole'))
            ->setParameter('accountUserRole', $role)
            ->getQuery()
            ->getResult();

        return $findResult;
    }

    /**
     * @param OrganizationInterface $organization
     * @param mixed $account
     * @return QueryBuilder
     */
    public function getAvailableRolesByAccountUserQueryBuilder(OrganizationInterface $organization, $account)
    {
        if ($account instanceof Account) {
            $account = $account->getId();
        }

        $qb = $this->createQueryBuilder('accountUserRole');

        $expr = $qb->expr()->isNull('accountUserRole.account');
        if ($account) {
            $expr = $qb->expr()->orX(
                $expr,
                $qb->expr()->eq('accountUserRole.account', ':account')
            );
            $qb->setParameter('account', (int)$account);
        }

        $qb->where(
            $qb->expr()->andX(
                $expr,
                $qb->expr()->eq('accountUserRole.organization', ':organization')
            )
        );
        $qb->setParameter('organization', $organization);

        return $qb;
    }
}
