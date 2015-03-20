<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

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
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadTagData',
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadDefaultUserData',
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'accounts' => $this->loadData('accounts.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Organization $organization */
        $organization = $this->getMainOrganization();

        $data = $this->getData();

        $names = [];

        foreach ($data['accounts'] as $accountData) {
            if (!isset($names[$accountData['name']])) {
                $account = new Account();
                $accountData['owner'] = $this->getReferenceByName('User:' . $accountData['user uid']);
                $accountData['organization'] = $organization;
                $accountData['CreatedAt'] = $this->generateCreatedDate();
                $accountData['UpdatedAt'] = $this->generateUpdatedDate($accountData['CreatedAt']);
                $uid = $accountData['uid'];
                unset($accountData['uid'], $accountData['user uid']);
                $this->setObjectValues($account, $accountData);

                $manager->getClassMetadata(get_class($account))->setLifecycleCallbacks([]);

                $names[$accountData['name']] = $accountData['name'];

                $this->setReference('Account:' . $uid, $account);
                $manager->persist($account);
            }
        }
        $manager->flush();
    }
}
