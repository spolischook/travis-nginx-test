<?php

namespace Oro\Bundle\ReminderBundle\Twig;

use Symfony\Component\Security\Core\SecurityContext;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;

class ReminderExtension extends \Twig_Extension
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @var MessageParamsProvider
     */
    protected $messageParamsProvider;

    /**
     * @param EntityManager         $entityManager
     * @param SecurityContext       $securityContext
     * @param MessageParamsProvider $messageParamsProvider
     */
    public function __construct(
        EntityManager $entityManager,
        SecurityContext $securityContext,
        MessageParamsProvider $messageParamsProvider
    ) {
        $this->entityManager         = $entityManager;
        $this->securityContext       = $securityContext;
        $this->messageParamsProvider = $messageParamsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'oro_reminder_get_requested_reminders_data',
                array($this, 'getRequestedRemindersData')
            )
        );
    }

    /**
     * Get requested reminders
     *
     * @return string
     */
    public function getRequestedRemindersData()
    {
        /** @var User|null */
        $user = $this->securityContext->getToken() ? $this->securityContext->getToken()->getUser() : null;

        if (is_object($user) && $user instanceof User) {
            $reminders = $this->entityManager
                ->getRepository('OroReminderBundle:Reminder')
                ->findRequestedReminders($user);

            return $this->messageParamsProvider->getMessageParamsForReminders($reminders);
        }

        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_reminder.subscriber';
    }
}
