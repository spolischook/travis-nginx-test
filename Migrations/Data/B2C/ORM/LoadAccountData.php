<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroCRM\Bundle\AccountBundle\Entity\Account;

class LoadAccountData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadBusinessUnitData',
            __NAMESPACE__ . '\\LoadTagData',
            __NAMESPACE__ . '\\LoadDefaultUserData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'user uid',
                'organization uid',
            ]
        );
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
        $data = $this->getData();

        $names = [];
        foreach ($data['accounts'] as $accountData) {
            if (!isset($names[$accountData['name']])) {
                $account = new Account();
                $accountData['owner'] = $this->getReferenceByName('User:' . $accountData['user uid']);
                $accountData['organization'] = $this->getReferenceByName('Organization:' . $accountData['organization uid']);
                $accountData['CreatedAt'] = $this->generateCreatedDate();
                $accountData['UpdatedAt'] = $this->generateUpdatedDate($accountData['CreatedAt']);

                $this->setObjectValues($account, $accountData);

                $manager->getClassMetadata(get_class($account))->setLifecycleCallbacks([]);
                $names[$accountData['name']] = $accountData['name'];

                $this->setAccountReference($accountData['uid'], $account);
                $manager->persist($account);
            }
        }
        $manager->flush();
    }
}
