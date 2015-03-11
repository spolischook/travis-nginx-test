<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroCRM\Bundle\AccountBundle\Entity\Account;

class LoadAccountData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadBusinessUnitData',
        ];
    }

    public function getData()
    {
        return [
            'accounts' => $this->loadData('accounts.csv')
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Organization $organization */
        $organization = $this->getMainOrganization();

        /** @var User $user */
        $user = $this->getMainUser();

        $data = $this->getData();

        $names = [];

        /**
         * TODO:Move to reset command
         */
        $this->removeOldData('OroCRMAccountBundle:Account');

        foreach($data['accounts'] as $accountData)
        {
            if(!isset($names[$accountData['name']]))
            {
                $account = new Account();
                $accountData['owner'] = $user;
                $accountData['organization'] = $organization;
                $uid = $accountData['uid'];
                unset($accountData['uid']);
                $this->setObjectValues($account, $accountData);
                $this->em->persist($account);

                $names[$accountData['name']] = $accountData['name'];

                $this->setReference('OroCRMLiveDemoBundle:Account:' . $uid, $account);
            }
        }
        $this->em->flush();
    }
}
