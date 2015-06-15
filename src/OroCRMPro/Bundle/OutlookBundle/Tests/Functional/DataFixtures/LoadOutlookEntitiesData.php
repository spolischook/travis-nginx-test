<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\ChannelBundle\Builder\BuilderFactory;
use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\SalesBundle\Entity\B2bCustomer;
use OroCRM\Bundle\SalesBundle\Entity\Lead;
use OroCRM\Bundle\SalesBundle\Entity\Opportunity;

class LoadOutlookEntitiesData extends AbstractFixture implements ContainerAwareInterface
{
    const CHANNEL_TYPE = 'b2b';
    const CHANNEL_NAME = 'b2b Channel';
    const FIRST_CONTACT_NAME = 'Richard';

    /** @var ObjectManager */
    protected $em;

    /** @var BuilderFactory */
    protected $factory;

    /** @var Channel */
    protected $channel;

    /** @var Organization */
    protected $organization;

    /**
     * @var array
     */
    protected $contactsData = [
        [
            'firstName' => self::FIRST_CONTACT_NAME,
            'lastName'  => 'Bradley',
        ],
        [
            'firstName' => 'Brenda',
            'lastName'  => 'Brock',
        ],
        [
            'firstName' => 'Shawn',
            'lastName'  => 'Bryson',
        ],
        [
            'firstName' => 'Faye',
            'lastName'  => 'Church',
        ],

    ];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->factory = $container->get('orocrm_channel.builder.factory');
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->em           = $manager;
        $this->organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        $this->createChannel();
        $this->createAccount();
        $this->createContacts();
        $this->createB2bCustomer();
        $this->createLead();
        $this->createOpportunity();

        $this->em->flush();
    }

    protected function createAccount()
    {
        $account = new Account();
        $account->setName(implode(' ', $this->contactsData[0]));
        $account->setOrganization($this->organization);

        $this->em->persist($account);
        $this->setReference('default_account', $account);

        return $this;
    }

    protected function createContacts()
    {
        $firstCustomer = true;
        $count         = 0;
        foreach ($this->contactsData as $contactData) {
            $count++;
            $contact = new Contact();
            $contact->setOrganization($this->organization);
            $contact->setFirstName($contactData['firstName']);
            $contact->setLastName($contactData['lastName']);
            if ($firstCustomer) {
                $contact->addAccount($this->getReference('default_account'));
                $this->setReference('default_contact', $contact);
            }
            $this->setReference(sprintf('test_contact%d', $count), $contact);
            $firstCustomer = false;

            $this->em->persist($contact);
        }

        return $this;
    }

    protected function createB2bCustomer()
    {
        $customer = new B2bCustomer();
        $customer->setAccount($this->getReference('default_account'));
        $customer->setName(implode(' ', $this->contactsData[0]));
        $customer->setDataChannel($this->getReference('default_channel'));
        $customer->setOrganization($this->organization);

        $this->em->persist($customer);
        $this->setReference('default_b2bcustomer', $customer);

        return $this;
    }

    protected function createLead()
    {
        $lead = new Lead();
        $lead->setDataChannel($this->getReference('default_channel'));
        $lead->setName('Test lead');
        $lead->setFirstName($this->contactsData[0]['firstName']);
        $lead->setLastName($this->contactsData[0]['lastName']);
        $lead->setCustomer($this->getReference('default_b2bcustomer'));
        $lead->setContact($this->getReference('default_contact'));
        $lead->setEmail('email@email.com');
        $lead->setOrganization($this->organization);

        $this->em->persist($lead);
        $this->setReference('default_lead', $lead);

        return $this;
    }

    protected function createOpportunity()
    {
        $opportunity = new Opportunity();
        $opportunity->setName('Test opportunity');
        $opportunity->setCustomer($this->getReference('default_b2bcustomer'));
        $opportunity->setContact($this->getReference('default_contact'));
        $opportunity->setDataChannel($this->getReference('default_channel'));
        $opportunity->setBudgetAmount(50.00);
        $opportunity->setProbability(10);
        $opportunity->setOrganization($this->organization);

        $this->em->persist($opportunity);
        $this->setReference('default_opportunity', $opportunity);

        return $this;
    }

    /**
     * @return Channel
     */
    protected function createChannel()
    {
        $channel = $this
            ->factory
            ->createBuilder()
            ->setName(self::CHANNEL_NAME)
            ->setChannelType(self::CHANNEL_TYPE)
            ->setStatus(Channel::STATUS_ACTIVE)
            ->setOwner($this->organization)
            ->setEntities()
            ->getChannel();

        $this->em->persist($channel);
        $this->setReference('default_channel', $channel);

        return $this;
    }
}
