<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroCRM\Bundle\MagentoBundle\Entity\Store;
use OroCRM\Bundle\MagentoBundle\Entity\Website;

class LoadStoreData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadWebsiteData',
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'stores' => $this->loadData('stores.csv')
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['stores'] as $storeData) {
            $uid = $storeData['uid'];
            $storeData['website'] = $this->getWebsiteReference($storeData['website uid']);
            unset($storeData['uid'], $storeData['website uid']);
            $store = new Store();
            $this->setObjectValues($store, $storeData);
            $manager->persist($store);

            $this->setReference('Store:' . $uid, $store);
        }
        $manager->flush();
    }

    /**
     * @param $uid
     * @return Website
     * @throws EntityNotFoundException
     */
    protected function getWebsiteReference($uid)
    {
        $reference = 'Website:' . $uid;
        return $this->getReferenceByName($reference);
    }
}
