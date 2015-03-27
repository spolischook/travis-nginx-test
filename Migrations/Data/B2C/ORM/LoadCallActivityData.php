<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroCRM\Bundle\CallBundle\Entity\Call;
use OroCRM\Bundle\CallBundle\Entity\CallDirection;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\ContactBundle\Entity\ContactPhone;

class LoadCallActivityData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var  CallDirection[] */
    protected $directions;

    /**
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'user uid',
                'account uid',
                'contact uid',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadAccountData',
            __NAMESPACE__ . '\\LoadContactData',
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'account_calls' => $this->loadData('activities/account/calls.csv'),
            'contact_calls' => $this->loadData('activities/contact/calls.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        $this->directions = [
            'Incoming' => $manager->getRepository('OroCRMCallBundle:CallDirection')->findOneBy(['name' => 'incoming']),
            'Outgoing' => $manager->getRepository('OroCRMCallBundle:CallDirection')->findOneBy(['name' => 'outgoing'])
        ];

        foreach ($data['account_calls'] as $callData) {
            $account = $this->getAccountReference($callData['account uid']);
            $defaultContact = $account->getDefaultContact();
            $this->loadActivity($manager, $account, $defaultContact, $callData);
        }

        foreach ($data['contact_calls'] as $callData) {
            $contact = $this->getContactReference($callData['contact uid']);
            $this->loadActivity($manager, $contact, $contact, $callData);
        }
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param Account|Contact $entity
     * @param Contact $contact
     * @param $data
     * @throws EntityNotFoundException
     */
    protected function loadActivity(ObjectManager $manager, $entity, Contact $contact, $data)
    {
        if ($contact !== null && $contact->getPhones()->count()) {
            /** @var ContactPhone $phone */
            $phone = $contact->getPhones()->first();
            if ($phone) {
                $call = new Call();
                $call->setOrganization($contact->getOrganization());
                if ($call->supportActivityTarget(get_class($entity->getOwner()))) {
                    $call->setOwner($entity->getOwner());
                }


                $data['duration'] = new \DateTime($data['duration'], new \DateTimeZone('UTC'));
                if (isset($this->directions[$data['direction']])) {
                    $data['direction'] = $this->directions[$data['direction']];
                } else {
                    $data['direction'] = null;
                }

                $created = $this->generateCreatedDate();
                $call->setCreatedAt($created);
                $call->setUpdatedAt($created);
                $data['calldatetime'] = $created;

                $call->setDirection($this->directions['Outgoing']);
                $data['phoneNumber'] = $phone->getPhone();
                $this->setObjectValues($call, $data);

                $call->addActivityTarget($entity);
                $this->setSecurityContext($entity->getOwner());
                $manager->getClassMetadata(get_class($call))->setLifecycleCallbacks([]);
                $manager->persist($call);
            }
        }
    }
}
