<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Zendesk;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\ORM\EntityManager;

use OroCRM\Bundle\ContactBundle\Entity\Contact;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UserBundle\Entity\User as OroUser;
use OroCRM\Bundle\CaseBundle\Entity\CaseComment;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole;
use OroCRM\Bundle\ZendeskBundle\Model\EntityMapper;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;
use OroCRM\Bundle\ZendeskBundle\Entity\User;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadTicketEntityData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $entitiesCount;

    /**
     * @var int
     */
    protected $ticketOriginId = 1000000;


    /**
     * @var int
     */
    protected $ticketCommentOriginId = 1000000;

    /**
     * @var int
     */
    protected $userOriginId = 1000000;

    protected $zendeskUsers = array();

    /**
     * @var int
     */
    protected $organizationId;

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'tickets' => $this->loadData('zendesk/tickets.csv')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->entityManager = $manager;
        $data = $this->getData();

        foreach ($data['tickets'] as $dataTicket) {
            $ticket = $this->createTicket($dataTicket);
            $this->entityManager->persist($ticket);
            $this->createTicketComments($ticket);
        }
        $manager->flush();
    }

    /**
     * @param array $data
     * @return Ticket|null
     */
    protected function createTicket($data)
    {
        $this->ticketOriginId++;
        /** @var CaseEntity $case */
        $case = $this->getCaseReference($data['case uid']);
        $requester = $this->getZendeskUserByUser($case->getOwner());
        $assignee = $this->getZendeskUserByUser($case->getAssignedTo());
        $ticket = array(
            'originId' => $this->ticketOriginId,
            'url' => "https://company.zendesk.com/api/v2/tickets/{$this->ticketOriginId}.json",
            'recipient' => "{$this->ticketOriginId}_support@company.com",
            'requester' => $requester,
            'assignee'  => $assignee,
            'hasIncidents' => rand(0, 1),
            'dueAt' => $this->getRandomDate(),
            'createdAt' => $this->getRandomDate(),
            'updatedAt' => $this->getRandomDate(),
            'relatedCase' => $case,
            'externalId' => uniqid(),
            'subject' => $case->getSubject(),
            'description' => $case->getDescription(),
            'collaborators' => new ArrayCollection(array($requester))
        );

        if ($ticket['hasIncidents']) {
            $type = $this->entityManager->getRepository('OroCRMZendeskBundle:TicketType')
                ->findOneBy(array('name' => TicketType::TYPE_PROBLEM));
        } else {
            $type = $this->getRandomEntity('OroCRMZendeskBundle:TicketType');
        }

        /**
         * @var EntityMapper $entityMapper
         */
        $entityMapper = $this->container->get('orocrm_zendesk.entity_mapper');

        $status = $entityMapper->getTicketStatus($case->getStatus()->getName());

        $priority = $entityMapper->getTicketPriority($case->getPriority()->getName());

        if (!$type || !$status || !$priority) {
            return null;
        }

        $ticket['type'] = $type;
        $ticket['status'] = $status;
        $ticket['priority'] = $priority;

        $ticket['channel'] = $this->getZendeskIntegrationReference($data['zendesk uid']);

        $contact = $case->getRelatedContact();
        if ($contact) {
            $ticket['submitter'] = $this->getZendeskUserByContact($contact);
        }

        $ticketEntity = new Ticket();
        $this->setObjectValues($ticketEntity, $ticket);

        return $ticketEntity;
    }

    /**
     * @return \DateTime
     */
    protected function getRandomDate()
    {
        $result = new \DateTime();
        $result->sub(new \DateInterval(sprintf('P%dDT%dM', rand(0, 30), rand(0, 1440))));

        return $result;
    }

    protected function createTicketComments(Ticket $ticket)
    {
        $comments = $ticket->getRelatedCase()->getComments();

        /**
         * @var CaseComment $comment
         */
        foreach ($comments as $comment) {
            $ticketComment = new TicketComment();
            $author = $this->getZendeskUserByUser($comment->getOwner());
            $ticketComment->setOriginId($this->ticketCommentOriginId++)
                ->setAuthor($author)
                ->setBody($comment->getMessage())
                ->setHtmlBody($comment->getMessage())
                ->setCreatedAt($comment->getCreatedAt())
                ->setPublic($comment->isPublic())
                ->setTicket($ticket)
                ->setRelatedComment($comment);

            $this->entityManager->persist($ticketComment);
        }
    }

    /**
     * @param OroUser $user
     * @return User
     */
    protected function getZendeskUserByUser(OroUser $user)
    {
        $email = $user->getEmail();
        if (array_key_exists($email, $this->zendeskUsers)) {
            return $this->zendeskUsers[$email];
        }

        $zendeskUser = new User();
        $name = $user->getFirstName().' '.$user->getLastName();
        $roleName = rand(0, 1) ? UserRole::ROLE_AGENT : UserRole::ROLE_ADMIN;
        $role = $this->getRoleByName($roleName);
        $zendeskUser->setOriginId($this->userOriginId++)
            ->setRelatedUser($user)
            ->setEmail($user->getEmail())
            ->setRole($role)
            ->setName($name);

        $this->entityManager->persist($zendeskUser);
        $this->zendeskUsers[$email] = $zendeskUser;

        return $zendeskUser;
    }

    /**
     * @param Contact $contact
     * @return User
     */
    protected function getZendeskUserByContact(Contact $contact)
    {
        $email = $contact->getEmail();
        if (array_key_exists($email, $this->zendeskUsers)) {
            return $this->zendeskUsers[$email];
        }

        $zendeskUser = new User();
        $name = $contact->getFirstName() . ' ' . $contact->getLastName();
        $role = $this->getRoleByName(UserRole::ROLE_END_USER);

        $zendeskUser->setOriginId($this->userOriginId++)
            ->setRelatedContact($contact)
            ->setEmail($email)
            ->setName($name)
            ->setRole($role);

        $this->entityManager->persist($zendeskUser);
        $this->zendeskUsers[$email] = $zendeskUser;

        return $zendeskUser;
    }

    /**
     * @param string $entityName
     * @return object|null
     */
    protected function getRandomEntity($entityName)
    {
        $count = $this->getEntityCount($entityName);

        if ($count) {
            return $this->entityManager->createQueryBuilder()
                ->select('e')
                ->from($entityName, 'e')
                ->setFirstResult(rand(0, $count - 1))
                ->setMaxResults(1)
                ->orderBy('e.' . $this->entityManager->getClassMetadata($entityName)->getSingleIdentifierFieldName())
                ->getQuery()
                ->getSingleResult();
        }

        return null;
    }

    /**
     * @param string $entityName
     * @return int
     */
    protected function getEntityCount($entityName)
    {
        if (!isset($this->entitiesCount[$entityName])) {
            $this->entitiesCount[$entityName] = (int)$this->entityManager->createQueryBuilder()
                ->select('COUNT(e)')
                ->from($entityName, 'e')
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $this->entitiesCount[$entityName];
    }

    /**
     * @param $roleName
     * @return null|object
     */
    protected function getRoleByName($roleName)
    {
        $role = $this->entityManager->getRepository('OroCRMZendeskBundle:UserRole')
            ->findOneBy(array('name' => $roleName));

        return $role;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 36;
    }
}
