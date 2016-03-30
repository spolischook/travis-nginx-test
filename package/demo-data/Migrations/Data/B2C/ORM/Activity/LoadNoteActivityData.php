<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Activity;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\NoteBundle\Entity\Note;

use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\ContactBundle\Entity\Contact;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadNoteActivityData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->removeEventListener('Oro\Bundle\NoteBundle\EventListener\NoteLifecycleListener');
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
            'account_notes' => $this->loadData('activities/account/notes.csv'),
            'contact_notes' => $this->loadData('activities/contact/notes.csv'),
        ];
    }

    /**
     * {@inheritdoc}
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
     * @param ObjectManager   $manager
     * @param Account|Contact $entity
     * @param                 $data
     */
    protected function addActivity(ObjectManager $manager, $entity, $data)
    {
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
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 17;
    }
}
