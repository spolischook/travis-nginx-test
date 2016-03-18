<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AttachmentBundle\Entity\File;

use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\ContactBundle\Entity\ContactAddress;
use OroCRM\Bundle\ContactBundle\Entity\ContactEmail;
use OroCRM\Bundle\ContactBundle\Entity\ContactPhone;

class LoadContactData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @var Country[]
     */
    protected $countries;

    /** @var  EntityRepository */
    protected $contactSources;

    /**
     * {@inheritdoc}
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
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'contact uid',
                'account uid',
                'photo',
                'source',
            ]
        );
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'addresses' => $this->loadData('contacts' . DIRECTORY_SEPARATOR . 'addresses.csv'),
            'contacts'  => $this->loadData('contacts' . DIRECTORY_SEPARATOR . 'contacts.csv'),
            'emails'    => $this->loadData('contacts' . DIRECTORY_SEPARATOR . 'emails.csv'),
            'phones'    => $this->loadData('contacts' . DIRECTORY_SEPARATOR . 'phones.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->countries = $manager->getRepository('OroAddressBundle:Country')->findAll();
        $data            = $this->getData();

        foreach ($data['contacts'] as $contactData) {
            $account = $this->getAccountReference($contactData['account uid']);

            $contact = new Contact();

            $contact->setCreatedAt($this->generateCreatedDate());
            $contact->setUpdatedAt($this->generateUpdatedDate($contact->getCreatedAt()));

            $contactData['assignedTo']   = $account->getOwner();
            $contactData['reportsTo']    = $contact;
            $contactData['owner']        = $account->getOwner();
            $contactData['updatedBy']    = $account->getOwner();
            $contactData['createdBy']    = $account->getOwner();
            $contactData['birthday']     = new \DateTime($contactData['birthday']);
            $contactData['organization'] = $account->getOrganization();

            if (!empty($contactData['photo'])) {
                $file = new File();
                $path = dirname(__FILE__) . '/data/contacts/photos/' . $contactData['photo'];
                if (!file_exists($path)) {
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
            $this->setObjectValues($contact, $contactData);
            if($account->getDefaultContact() === null) {
                $account->setDefaultContact($contact);
            }
            $account->addContact($contact);
            $this->loadPhones($contact, $uid);
            $this->loadEmails($contact, $uid);
            $this->loadAddresses($contact, $uid);

            $this->setContactReference($uid, $contact);

            $manager->persist($contact);
            $manager->persist($account);
        }
        $manager->flush();
    }

    /**
     * Load Contact phones
     *
     * @param Contact $contact
     * @param         $uid
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
            if (!$contact->getPhones()->count()) {
                $phone->setPrimary(true);
            }
            $contact->addPhone($phone);
        }
    }

    /**
     * Load Contact emails
     *
     * @param Contact $contact
     * @param         $uid
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
            if (!$contact->getEmails()->count()) {
                $email->setPrimary(true);
            }
            $contact->addEmail($email);
        }
    }

    /**
     * Load Contact addresses
     *
     * @param Contact $contact
     * @param         $uid
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
            $isoCode   = $addressData['country'];
            $countries = array_filter(
                $this->countries,
                function (Country $country) use ($isoCode) {
                    return $country->getIso2Code() == $isoCode;
                }
            );

            /** @var Country $country */
            $country = array_values($countries)[0];

            $regions = $country->getRegions();
            $region  = $regions->filter(
                function (Region $region) use ($addressData) {
                    return $region->getCode() == $addressData['region'];
                }
            );

            /** @var ContactAddress $address */
            $address = new ContactAddress();
            $address->setOwner($contact);
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
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }
}
