<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\ContactBundle\Entity\ContactPhone;
use OroCRM\Bundle\CallBundle\Entity\Call;

class LoadCallActivityData extends AbstractFixture implements DependentFixtureInterface
{
    protected $directions;

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'account_calls' => $this->loadData('activities/account/calls.csv'),
            'contact_calls' => $this->loadData('activities/contact/calls.csv')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadAccountData',
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadContactData',
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
            unset($callData['uid'], $callData['account uid']);
            $defaultContact = $account->getDefaultContact();
            $this->loadActivity($manager, $account, $defaultContact, $callData);
        }

        foreach ($data['contact_calls'] as $callData) {
            $contact = $this->getContactReference($callData['contact uid']);
            unset($callData['uid'], $callData['contact uid']);
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
                    unset($data['direction']);
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

    /**
     * @param $uid
     * @return Contact
     * @throws EntityNotFoundException
     */
    public function getContactReference($uid)
    {
        $reference = 'Contact:' . $uid;
        return $this->getReferenceByName($reference);
    }

}
