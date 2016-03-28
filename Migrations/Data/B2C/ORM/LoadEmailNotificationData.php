<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Entity\Event;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;

use OroCRMPro\Bundle\DemoDataBundle\Exception\EntityNotFoundException;

class LoadEmailNotificationData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @return array
     */
    public function getData()
    {
        return [
            'rules'  => $this->loadData('notifications/rules.csv'),
            'users'  => $this->loadData('notifications/users.csv'),
            'groups' => $this->loadData('notifications/groups.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['rules'] as $rulesData) {
            $emailNotification = new EmailNotification();
            $emailNotification->setEntityName($rulesData['entity']);
            $emailNotification->setEvent($this->getNotificationEvent($rulesData['event']));
            $emailNotification->setTemplate($this->getNotificationTemplate($rulesData['template']));

            $recipientList = new RecipientList();
            if (!empty($rulesData['email'])) {
                $recipientList->setEmail($rulesData['email']);
            }
            $this->loadRecipientGroups($recipientList, $rulesData['uid']);
            $this->loadRecipientUsers($recipientList, $rulesData['uid']);
            $emailNotification->setRecipientList($recipientList);
            $manager->persist($emailNotification);
        }
        $manager->flush();
    }

    /**
     * @param RecipientList $recipientList
     * @param               $uid
     */
    protected function loadRecipientGroups($recipientList, $uid)
    {
        $data = $this->getData();

        $groups = $this->filterRuleData($data['groups'], $uid);

        foreach ($groups as $groupData) {
            $recipientList->addGroup($this->getGroupReference($groupData['group uid']));
        }
    }

    /**
     * @param RecipientList $recipientList
     * @param               $uid
     */
    protected function loadRecipientUsers($recipientList, $uid)
    {
        $data = $this->getData();

        $users = $this->filterRuleData($data['users'], $uid);

        foreach ($users as $userData) {
            $recipientList->addUser($this->getUserReference($userData['user uid']));
        }
    }

    /**
     * Filter and return data relations for Rule
     *
     * @param array $data
     * @param       $uid
     *
     * @return array
     */
    protected function filterRuleData(array $data, $uid)
    {
        return array_filter(
            $data,
            function ($data) use ($uid) {
                return $data['rule uid'] == $uid;
            }
        );
    }

    /**
     * Get notification event
     *
     * @param $name
     *
     * @return Event
     * @throws EntityNotFoundException
     */
    protected function getNotificationEvent($name)
    {
        $name       = 'oro.notification.event.entity_post_' . $name;
        $repository = $this->em->getRepository('OroNotificationBundle:Event');

        /** @var Event $entity */
        $entity = $repository->findOneBy(['name' => $name]);
        if (!$entity) {
            throw new EntityNotFoundException('Event ' . $name . ' not found.');
        }

        return $entity;
    }

    /**
     * Get notification email template
     *
     * @param $name
     *
     * @return EmailTemplate
     * @throws EntityNotFoundException
     */
    protected function getNotificationTemplate($name)
    {
        $repository = $this->em->getRepository('OroEmailBundle:EmailTemplate');
        /** @var EmailTemplate $mainBusinessUnit */
        $entity = $repository->findOneBy(['name' => $name]);
        if (!$entity) {
            throw new EntityNotFoundException('EmailTemplate ' . $name . ' not found.');
        }

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadEmailTemplateData'
        ];
    }
}
