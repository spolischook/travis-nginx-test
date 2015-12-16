<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

class LoadAccounts extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    const DEFAULT_ACCOUNT_NAME = 'account.orphan';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [__NAMESPACE__ . '\LoadGroups'];
    }

    /**
     * {@inheritdoc}
     *
     * account.orphan
     * account.level_1
     *     account.level_1.1
     *         account.level_1.1.1
     *     account.level_1.2
     *         account.level_1.2.1
     *             account.level_1.2.1.1
     *     account.level_1.3
     *         account.level_1.3.1
     *             account.level_1.3.1.1
     *     account.level_1.4
     * account.level_1_1
     */
    public function load(ObjectManager $manager)
    {
        $owner = $this->getFirstUser($manager);
        $this->createAccount($manager, self::DEFAULT_ACCOUNT_NAME, $owner);
        $levelOne = $this->createAccount(
            $manager,
            'account.level_1',
            $owner,
            null,
            $this->getAccountGroup('account_group.group1')
        );

        $levelTwoFirst = $this->createAccount($manager, 'account.level_1.1', $owner, $levelOne);
        $this->createAccount($manager, 'account.level_1.1.1', $owner, $levelTwoFirst);

        $levelTwoSecond = $this->createAccount($manager, 'account.level_1.2', $owner, $levelOne);
        $levelTreeFirst = $this->createAccount($manager, 'account.level_1.2.1', $owner, $levelTwoSecond);
        $this->createAccount($manager, 'account.level_1.2.1.1', $owner, $levelTreeFirst);

        $levelTwoThird = $this->createAccount(
            $manager,
            'account.level_1.3',
            $owner,
            $levelOne,
            $this->getAccountGroup('account_group.group1')
        );
        $levelTreeFirst = $this->createAccount($manager, 'account.level_1.3.1', $owner, $levelTwoThird);
        $this->createAccount($manager, 'account.level_1.3.1.1', $owner, $levelTreeFirst);

        $this->createAccount($manager, 'account.level_1.4', $owner, $levelOne);

        $this->createAccount($manager, 'account.level_1_1', $owner);

        $manager->flush();
    }

    /**
     * @param string $reference
     * @return AccountGroup
     */
    protected function getAccountGroup($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param User $owner
     * @param Account $parent
     * @param AccountGroup $group
     * @return Account
     */
    protected function createAccount(
        ObjectManager $manager,
        $name,
        User $owner,
        Account $parent = null,
        AccountGroup $group = null
    ) {
        $account = new Account();
        $account->setName($name);
        $account->setOwner($owner);
        $organization = $manager
            ->getRepository('OroOrganizationBundle:Organization')
            ->getFirst();
        $account->setOrganization($organization);
        if ($parent) {
            $account->setParent($parent);
        }
        if ($group) {
            $account->setGroup($group);
        }
        $manager->persist($account);
        $this->addReference($name, $account);

        return $account;
    }
}
