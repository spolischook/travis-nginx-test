<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\ContactBundle\Entity\ContactEmail;
use OroCRM\Bundle\ContactBundle\Entity\ContactPhone;
use OroCRM\Bundle\ContactBundle\Entity\ContactAddress;

class LoadContactData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var Country[]
     */
    protected $countries;

    /** @var  EntityRepository */
    protected $contactSources;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->contactSources = $this->em->getRepository('OroCRMContactBundle:Source');
        $this->removeEventListener('OroCRM\Bundle\ContactBundle\EventListener\ContactListener');
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadAccountData',
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadTagData',
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'addresses' => $this->loadData('contacts' . DIRECTORY_SEPARATOR . 'addresses.csv'),
            'contacts' => $this->loadData('contacts' . DIRECTORY_SEPARATOR . 'contacts.csv'),
            'emails' => $this->loadData('contacts' . DIRECTORY_SEPARATOR . 'emails.csv'),
            'phones' => $this->loadData('contacts' . DIRECTORY_SEPARATOR . 'phones.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->countries = $manager->getRepository('OroAddressBundle:Country')->findAll();
        $data = $this->getData();

        foreach ($data['contacts'] as $contactData) {

            $account = $this->getAccountReference($contactData['account uid']);

            $contact = new Contact();

            $contact->setCreatedAt($this->generateCreatedDate());
            $contact->setUpdatedAt($this->generateUpdatedDate($contact->getCreatedAt()));
            $contact->setFirstName($contactData['firstname']);
            $contact->setLastName($contactData['lastname']);

            $contactData['assignedTo'] = $account->getOwner();
            $contactData['reportsTo'] = $contact;
            $contactData['owner'] = $account->getOwner();
            $contactData['updatedBy'] = $account->getOwner();
            $contactData['createdBy'] = $account->getOwner();
            $contactData['birthday'] = new \DateTime($contactData['birthday']);
            $contactData['organization'] = $account->getOrganization();

            if (!empty($contactData['photo'])) {
                $file = new File();
                $path = dirname(__FILE__) . '/data/contacts/photos/' . $contactData['photo'];
                if(!file_exists($path))
                {
                    throw new FileNotFoundException($path);
                }
                $file->setFile(new ComponentFile($path));
                $contact->setPicture($file);
            }

            if (!empty($contactData['source'])) {
                $source = $this->contactSources->findOneByName($contactData['source']);
                if ($source) {
                    $contact->setSource($source);
                }
            }

            $uid = $contactData['uid'];
            unset($contactData['account uid'], $contactData['uid'], $contactData['photo'], $contactData['source']);

            $this->setObjectValues($contact, $contactData);
            $account->setDefaultContact($contact);
            $this->loadPhones($contact, $uid);
            $this->loadEmails($contact, $uid);
            $this->loadAddresses($contact, $uid);


            $this->setReference('Contact:' . $uid, $contact);

            $manager->persist($contact);
            $manager->persist($account);

        }
        $manager->flush();
    }

    /**
     * Load Contact phones
     * @param Contact $contact
     * @param $uid
     */
    public function loadPhones(Contact $contact, $uid)
    {
        $data = $this->getData();

        $phones = array_filter(
            $data['phones'],
            function ($phoneData) use ($uid) {
                return $phoneData['contact uid'] == $uid;
            }
        );

        foreach ($phones as $phoneData) {
            $phone = new ContactPhone($phoneData['phone']);
            if(!$contact->getPhones()->count()) {
                $phone->setPrimary(true);
            }
            $contact->addPhone($phone);
        }
    }

    /**
     * Load Contact emails
     * @param Contact $contact
     * @param $uid
     */
    public function loadEmails(Contact $contact, $uid)
    {
        $data = $this->getData();

        $emails = array_filter(
            $data['emails'],
            function ($emailData) use ($uid) {
                return $emailData['contact uid'] == $uid;
            }
        );

        foreach ($emails as $emailData) {
            $email = new ContactEmail($emailData['email']);
            if(!$contact->getEmails()->count()) {
                $email->setPrimary(true);
            }
            $contact->addEmail($email);
        }
    }

    /**
     * Load Contact addresses
     * @param Contact $contact
     * @param $uid
     */
    public function loadAddresses(Contact $contact, $uid)
    {
        $data = $this->getData();

        $addresses = array_filter(
            $data['addresses'],
            function ($addressData) use ($uid) {
                return $addressData['contact uid'] == $uid;
            }
        );

        foreach ($addresses as $addressData) {
            $isoCode = $addressData['country'];
            $country = array_filter(
                $this->countries,
                function (Country $country) use ($isoCode) {
                    return $country->getIso2Code() == $isoCode;
                }
            );
            /** @var Country $country */
            $country = array_values($country)[0];

            $regions = $country->getRegions();
            $region = $regions->filter(
                function (Region $region) use ($addressData) {
                    return $region->getCode() == $addressData['region'];
                }
            );

            /** @var ContactAddress $address */
            $address = new ContactAddress();
            $address->setOwner($contact);

            unset($addressData['contact uid'], $addressData['uid']);
            $this->setObjectValues($address, $addressData);

            $address->setCountry($country);
            if (!$region->isEmpty()) {
                $address->setRegion($region->first());
            }
            if (!$contact->getAddresses()->count()) {
                $address->setPrimary(true);
            }
            $contact->addAddress($address);
        }
    }

    /**
     * @param $uid
     * @return Account
     * @throws EntityNotFoundException
     */
    public function getAccountReference($uid)
    {
        $reference = 'Account:' . $uid;
        return $this->getReferenceByName($reference);
    }
}
