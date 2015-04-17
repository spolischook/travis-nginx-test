<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Magento;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\MagentoBundle\Entity\Store;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadStoreData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'website uid',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadWebsiteData',
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'stores' => $this->loadData('magento/stores.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['stores'] as $storeData) {
            $storeData['website'] = $this->getWebsiteReference($storeData['website uid']);
            $store = new Store();
            $this->setObjectValues($store, $storeData);
            $manager->persist($store);

            $this->setStoreReference($storeData['uid'], $store);
        }
        $manager->flush();
    }
}
