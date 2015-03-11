<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\ContactBundle\Entity\Contact;

class LoadContactData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadAccountData',
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadTagsData',
        ];
    }

    public function getData()
    {
        return [
            'contacts' => $this->loadData('contacts' . DIRECTORY_SEPARATOR . 'contacts.csv'),
            'phones' => $this->loadData('contacts' . DIRECTORY_SEPARATOR . 'phones.csv'),
            'addresses' => $this->loadData('contacts' . DIRECTORY_SEPARATOR . 'addresses.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->removeOldData('OroCRMContactBundle:Contact');

        $data = $this->getData();

        foreach ($data['contacts'] as $contactData) {
            $contact = new Contact();
            $contactData['assignedTo'] = $this->getMainUser();
            $contactData['reportsTo'] = $contact;
            $contactData['owner'] = $this->getMainUser();
            $contactData['birthday'] = new \DateTime($contactData['birthday']);
            $contactData['organization'] = $this->getMainOrganization();

            $account = $this->getAccountReference($contactData['account uid']);
            unset($contactData['uid'], $contactData['account uid']);
            $this->setObjectValues($contact, $contactData);
            $contact->addAccount($account);
            $manager->persist($contact);
        }
        $manager->flush();
    }

    /**
     * @param $uid
     * @return Account
     * @throws EntityNotFoundException
     */
    public function getAccountReference($uid)
    {
        $reference = 'OroCRMLiveDemoBundle:Account:' . $uid;
        if ($this->hasReference($reference)) {
            return $this->getReference($reference);
        } else {
            echo 'Reference to account' . $uid . 'not found.';
            /**
             * TODO:refactoring
             */
            throw new EntityNotFoundException('Reference to account ' . $uid . ' not found.');
        }
    }

    /*
       private function createContact(array $data)
       {
        /*
           $contact = new Contact();


           //Phone
           $phone = new ContactPhone($data['TelephoneNumber']);
           $phone->setPrimary(true);
           $contact->addPhone($phone);

           //Email
           $email = new ContactEmail($data['EmailAddress']);
           $email->setPrimary(true);
           $contact->addEmail($email);

           $date = \DateTime::createFromFormat('m/d/Y', $data['Birthday']);
           $contact->setBirthday($date);

           //Address
           $address = new ContactAddress();
           $address->setLabel('Primary Address');
           $address->setCity($data['City']);
           $address->setStreet($data['StreetAddress']);
           $address->setPostalCode($data['ZipCode']);
           $address->setFirstName($data['GivenName']);
           $address->setLastName($data['Surname']);
           $address->setPrimary(true);
           $address->setOwner($contact);

           //Address country + region
            $contact->addAddress($address);

           return $contact;

    }
    */
}
