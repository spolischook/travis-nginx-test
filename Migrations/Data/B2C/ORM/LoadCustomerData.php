<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\MagentoBundle\Entity\Store;
use OroCRM\Bundle\MagentoBundle\Entity\Website;
use OroCRM\Bundle\MagentoBundle\Entity\CustomerGroup;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;

class LoadCustomerData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadStoreData',
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadMagentoIntegrationData'
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'customers' => $this->loadData('customers.csv')
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        foreach ($data['customers'] as $customerData) {
            $website = $this->getWebsiteReference($customerData['website uid']);
            $store = $this->getStoreReference($customerData['store uid']);
            $customerGroup = $this->getCustomerGroupReference($customerData['customer group uid']);
            $integration = $this->getIntegrationReference($customerData['integration uid']);
            $contact = $this->getContactReference($customerData['contact uid']);
            $dataChannel = $this->getIntegrationDataChannelReference($customerData['integration uid']);

            $customer = new Customer();
            $customer->setWebsite($website)
                ->setChannel($integration)
                ->setStore($store)
                ->setFirstName($contact->getFirstName())
                ->setLastName($contact->getLastName())
                ->setEmail($contact->getPrimaryEmail())
                ->setBirthday($contact->getBirthday())
                ->setVat(mt_rand(10000000, 99999999))
                ->setGroup($customerGroup)
                ->setCreatedAt($this->generateUpdatedDate($contact->getCreatedAt()))
                ->setUpdatedAt($this->generateUpdatedDate($customer->getCreatedAt()))
                ->setAccount($contact->getAccounts()->first())
                ->setContact($contact)
                ->setOrganization($contact->getOrganization())
                ->setOwner($contact->getOwner());
            $customer->setDataChannel($dataChannel);

            $this->setReference('Customer:' . $customerData['uid'], $customer);

            $manager->persist($customer);
        }
        $manager->flush();
    }

    /**
     * @param $uid
     * @return Website
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getWebsiteReference($uid)
    {
        $reference = 'Website:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @return Contact
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getContactReference($uid)
    {
        $reference = 'Contact:' . $uid;
        return $this->getReferenceByName($reference);
    }


    /**
     * @param $uid
     * @return Store
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getStoreReference($uid)
    {
        $reference = 'Store:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @return CustomerGroup
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getCustomerGroupReference($uid)
    {
        $reference = 'CustomerGroup:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @return Integration
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getIntegrationReference($uid)
    {
        $reference = 'Integration:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @return Channel
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getIntegrationDataChannelReference($uid)
    {
        $reference = 'IntegrationDataChannel:' . $uid;
        return $this->getReferenceByName($reference);
    }
}
