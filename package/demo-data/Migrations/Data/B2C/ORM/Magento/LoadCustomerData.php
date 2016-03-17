<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Magento;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\ContactBundle\Entity\ContactAddress;
use OroCRM\Bundle\MagentoBundle\Entity\Address;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadCustomerData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @return array
     */
    public function getData()
    {
        return [
            'customers' => $this->loadData('magento/customers.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        foreach ($data['customers'] as $customerData) {
            $website       = $this->getWebsiteReference($customerData['website uid']);
            $store         = $this->getStoreReference($customerData['store uid']);
            $customerGroup = $this->getCustomerGroupReference($customerData['customer group uid']);
            $integration   = $this->getIntegrationReference($customerData['integration uid']);
            $contact       = $this->getContactReference($customerData['contact uid']);
            $dataChannel   = $this->getChannelReference($customerData['integration uid']);

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
            $this->addCustomerAddress($customer, $contact);

            $this->setCustomerReference($customerData['uid'], $customer);
            $manager->persist($customer);
        }
        $manager->flush();
    }

    /**
     * @param Customer $customer
     * @param Contact  $contact
     */
    protected function addCustomerAddress(Customer $customer, Contact $contact)
    {
        if ($contact->getAddresses()->count()) {
            /** @var ContactAddress $contactAddress */
            $contactAddress = $contact->getAddresses()->first();
            $address        = new Address();
            $address->setLabel($contactAddress->getLabel());
            $address->setCountry($contactAddress->getCountry());
            $address->setRegion($contactAddress->getRegion());
            $address->setStreet($contactAddress->getStreet());
            $address->setPostalCode($contactAddress->getPostalCode());
            $address->setCity($contactAddress->getCity());
            $address->setPrimary(true);
            $customer->addAddress($address);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 29;
    }
}
