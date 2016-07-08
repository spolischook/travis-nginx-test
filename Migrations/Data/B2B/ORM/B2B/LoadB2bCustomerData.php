<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM\B2B;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\AddressBundle\Entity\Address;

use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\ContactBundle\Entity\ContactAddress;
use OroCRM\Bundle\SalesBundle\Entity\B2bCustomer;
use OroCRM\Bundle\SalesBundle\Entity\B2bCustomerEmail;
use OroCRM\Bundle\SalesBundle\Entity\B2bCustomerPhone;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadB2bCustomerData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'customers' => $this->loadData('b2b/customers.csv'),
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $manager->getClassMetadata('OroCRM\Bundle\SalesBundle\Entity\B2bCustomer')->setLifecycleCallbacks([]);

        $data = $this->getData();
        foreach ($data['customers'] as $customerData) {
            $customer = $this->createCustomer($customerData);
            $this->setCustomerReference($customerData['uid'], $customer);
            $manager->persist($customer);
        }
        $manager->flush();

    }

    /**
     * @param array $customerData
     * @return B2bCustomer
     */
    protected function createCustomer(array $customerData)
    {
        $organization = $this->getOrganizationReference($customerData['organization uid']);
        $created      = $this->generateCreatedDate();
        $contact      = $this->getContactReference($customerData['contact uid']);

        $customer = new B2bCustomer();
        $customer->setName($customerData['company']);
        $customer->setOwner($this->getUserReference($customerData['user uid']));
        $customer->setContact($contact);
        $customer->setAccount($contact->getAccounts()->first());
        $customer->setOrganization($organization);

        $customer->setCreatedAt($created);
        $customer->setUpdatedAt($this->generateUpdatedDate($created));

        if (!empty($customerData['channel uid'])) {
            $customer->setDataChannel($this->getChannelReference($customerData['channel uid']));
        }

        $this->addCustomerAddress($customer, $contact);

        $customerPhone = new B2bCustomerPhone($customerData['TelephoneNumber']);
        $customerPhone->setPrimary(true);
        $customer->addPhone($customerPhone);

        $email = new B2bCustomerEmail($customerData['EmailAddress']);
        $email->setPrimary(true);
        $customer->addEmail($email);

        return $customer;
    }

    /**
     * @param B2bCustomer $customer
     * @param Contact     $contact
     */
    protected function addCustomerAddress(B2bCustomer $customer, Contact $contact)
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
            $customer->setShippingAddress($address);
            $customer->setBillingAddress($address);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 52;
    }
}
