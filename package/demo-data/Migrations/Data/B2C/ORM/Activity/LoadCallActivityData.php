<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Activity;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\DurationToStringTransformer;

use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\CallBundle\Entity\Call;
use OroCRM\Bundle\CallBundle\Entity\CallDirection;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\ContactBundle\Entity\ContactPhone;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadCallActivityData extends AbstractFixture implements OrderedFixtureInterface
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
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadAccountData',
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadContactData',
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
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // we need reverseTransform, create single instance to optimize memory usage
        $durationTransformer = new DurationToStringTransformer();

        $data = $this->getData();

        $this->directions = [
            'Incoming' => $manager->getRepository('OroCRMCallBundle:CallDirection')->findOneBy(['name' => 'incoming']),
            'Outgoing' => $manager->getRepository('OroCRMCallBundle:CallDirection')->findOneBy(['name' => 'outgoing'])
        ];

        foreach ($data['account_calls'] as $callData) {
            $account        = $this->getAccountReference($callData['account uid']);
            $defaultContact = $account->getDefaultContact();
            $this->loadActivity($manager, $account, $defaultContact, $callData, $durationTransformer);
        }

        foreach ($data['contact_calls'] as $callData) {
            $contact = $this->getContactReference($callData['contact uid']);
            $this->loadActivity($manager, $contact, $contact, $callData, $durationTransformer);
        }
        $manager->flush();
    }

    /**
     * @param ObjectManager                 $manager
     * @param Account|Contact               $entity
     * @param Contact|null                  $contact
     * @param array                         $data
     * @param DurationToStringTransformer   $durationTransformer
     */
    protected function loadActivity(
        ObjectManager $manager,
        $entity,
        $contact,
        array $data,
        DurationToStringTransformer $durationTransformer
    ) {
        if ($contact !== null && $contact->getPhones()->count()) {
            /** @var ContactPhone $phone */
            $phone = $contact->getPhones()->first();
            if ($phone) {
                $call = new Call();
                $call->setOrganization($contact->getOrganization());

                if ($call->supportActivityTarget(get_class($entity->getOwner()))) {
                    $call->setOwner($entity->getOwner());
                }

                if (isset($this->directions[$data['direction']])) {
                    $data['direction'] = $this->directions[$data['direction']];
                } else {
                    $data['direction'] = null;
                }

                $data['duration'] = $durationTransformer->reverseTransform($data['duration']);

                $created = $this->generateCreatedDate();
                $call->setCreatedAt($created);
                $call->setUpdatedAt($created);
                $call->setCallDateTime($created);

                $call->setDirection($this->directions['Outgoing']);
                $call->setPhoneNumber($phone->getPhone());
                $this->setObjectValues($call, $data);

                $call->addActivityTarget($entity);
                $this->setSecurityContext($entity->getOwner());
                $manager->getClassMetadata(get_class($call))->setLifecycleCallbacks([]);
                $manager->persist($call);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 18;
    }
}
