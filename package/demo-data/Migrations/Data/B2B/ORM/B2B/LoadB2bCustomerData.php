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
            'phones'    => $this->loadData('b2b/customer_phones.csv'),
            'emails'    => $this->loadData('b2b/customer_emails.csv'),
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
        $this->loadPhones($customer, $customerData['uid']);
        $this->loadEmails($customer, $customerData['uid']);

        return $customer;
    }

    /**
     * Load B2bCustomer phones
     *
     * @param B2bCustomer $customer
     * @param         $uid
     */
    public function loadPhones(B2bCustomer $customer, $uid)
    {
        $data = $this->getData();

        $phones = array_filter(
            $data['phones'],
            function ($phoneData) use ($uid) {
                return $phoneData['customer uid'] == $uid;
            }
        );

        foreach ($phones as $phoneData) {
            $phone = new B2bCustomerPhone($phoneData['phone']);
            if (!$customer->getPhones()->count()) {
                $phone->setPrimary(true);
            }
            $customer->addPhone($phone);
        }
    }

    /**
     * Load B2bCustomer emails
     *
     * @param B2bCustomer $customer
     * @param         $uid
     */
    public function loadEmails(B2bCustomer $customer, $uid)
    {
        $data = $this->getData();

        $emails = array_filter(
            $data['emails'],
            function ($emailData) use ($uid) {
                return $emailData['customer uid'] == $uid;
            }
        );

        foreach ($emails as $emailData) {
            $email = new B2bCustomerEmail($emailData['email']);
            if (!$customer->getEmails()->count()) {
                $email->setPrimary(true);
            }
            $customer->addEmail($email);
        }
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
