<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\AccountBundle\Entity\Account;

class LoadAccountData extends AbstractFixture implements OrderedFixtureInterface
{
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
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        $names = [];
        foreach ($data['accounts'] as $accountData) {
            if (!isset($names[$accountData['name']])) {
                $account = new Account();
                $account->setOwner($this->getUserReference($accountData['user uid']));
                $account->setOrganization($this->getOrganizationReference($accountData['organization uid']));
                $account->setCreatedAt($this->generateCreatedDate());
                $account->setUpdatedAt($this->generateUpdatedDate($account->getCreatedAt()));
                $this->setObjectValues($account, $accountData);

                $names[$accountData['name']] = $accountData['name'];

                $this->setAccountReference($accountData['uid'], $account);
                $manager->getClassMetadata(get_class($account))->setLifecycleCallbacks([]);
                $manager->persist($account);
            }
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 9;
    }
}
