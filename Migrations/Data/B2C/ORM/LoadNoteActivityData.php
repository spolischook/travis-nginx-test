<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\NoteBundle\Entity\Note;
use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\ContactBundle\Entity\Contact;

class LoadNoteActivityData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->removeEventListener('Oro\Bundle\NoteBundle\EventListener\NoteLifecycleListener');
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'account_notes' => $this->loadData('activities/account/notes.csv'),
            'contact_notes' => $this->loadData('activities/contact/notes.csv')
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

        foreach ($data['account_notes'] as $noteData) {
            $account = $this->getAccountReference($noteData['account uid']);
            $this->addActivity($manager, $account, $noteData);
        }

        foreach ($data['contact_notes'] as $noteData) {
            $contact = $this->getContactReference($noteData['contact uid']);
            $this->addActivity($manager, $contact, $noteData);
        }
        $manager->flush();
    }

    /**
     * Create Note activity for $entity
     *
     * @param ObjectManager $manager
     * @param Account|Contact $entity
     * @param $data
     */
    protected function addActivity(ObjectManager $manager, $entity, $data)
    {
        unset($data['uid'], $data['account uid'], $data['contact uid']);

        $note = new Note();
        $note->setTarget($entity);
        $note->setOrganization($entity->getOrganization());
        $note->setOwner($entity->getOwner());
        $note->setCreatedAt($this->generateUpdatedDate($entity->getCreatedAt()));
        $note->setUpdatedAt($note->getCreatedAt());
        $this->setSecurityContext($entity->getOwner());
        $this->setObjectValues($note, $data);

        $manager->persist($note);
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

