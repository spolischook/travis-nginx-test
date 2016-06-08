<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Activity;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Model\FolderType;

use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\ContactBundle\Entity\Contact;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;
use OroCRMPro\Bundle\DemoDataBundle\Exception\EntityNotFoundException;

class LoadEmailActivityData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @var EmailEntityBuilder
     */
    protected $emailEntityBuilder;

    /**
     * @var EmailOriginHelper
     */
    protected $emailOriginHelper;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

        /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $this->emailEntityBuilder = $container->get('oro_email.email.entity.builder');
        $this->emailOriginHelper  = $container->get('oro_email.tools.email_origin_helper');
        $this->entityNameResolver = $container->get('oro_entity.entity_name_resolver');
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
            'contact_emails'  => $this->loadData('activities/contact/emails.csv'),
            'incoming_emails' => $this->loadData('activities/contact/incoming_emails.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['contact_emails'] as $emailData) {
            $contact = $this->getContactReference($emailData['contact uid']);
            $this->addEmailUser($contact, $emailData);
        }

        foreach ($data['incoming_emails'] as $emailData) {
            $contact = $this->getContactReference($emailData['contact uid']);
            $this->addEmailUser($contact, $emailData, FolderType::INBOX);
        }
        $manager->flush();
    }

    /**
     * Create Email activity for $entity
     *
     * @param Account|Contact $entity
     * @param                 $data
     * @param string          $type
     */
    protected function addEmailUser($entity, $data, $type = FolderType::SENT)
    {
        if ($entity->getEmail() !== null) {
            $user   = $entity->getOwner();
            $origin = $this->emailOriginHelper->getEmailOrigin($user->getEmail());

            $emailUser = $this->createEmailUser($entity, $data['subject'], $type);
            $emailUser->setOrganization($entity->getOrganization());
            $emailUser->getEmail()->setEmailBody($this->createEmailBody($data['body']));
            $emailUser->addFolder($this->getFolder($origin, $type));
            $emailUser->getEmail()->addActivityTarget($user);
            $emailUser->getEmail()->addActivityTarget($entity);
            $emailUser->getEmail()->setMessageId(sprintf('id.%s@%s', uniqid(), '@orocrm-pro.demo-data.generated'));

            $this->em->getClassMetadata(get_class($emailUser))->setLifecycleCallbacks([]);
            $this->emailEntityBuilder->getBatch()->persist($this->em);
        }
    }

    /**
     * Create Email
     *
     * @param Account|Contact $entity
     * @param                 $subject
     * @param string          $type
     *
     * @return EmailUser
     */
    protected function createEmailUser($entity, $subject, $type = FolderType::SENT)
    {
        $from = $entity->getOwner()->getFullName() . ' <' . $entity->getOwner()->getEmail() . '>';
        $to = $this->entityNameResolver->getName($entity) . ' <' . $entity->getEmail() . '>';

        if ($type === FolderType::INBOX) {
            $from = $entity->getEmail();
            $to   = $entity->getOwner()->getEmail();
        }

        $createdAt = $this->generateUpdatedDate($entity->getCreatedAt());

        /** @var EmailUser $emailUser */
        $emailUser = $this->emailEntityBuilder->emailUser(
            $subject,
            $from,
            $to,
            $createdAt,
            $createdAt,
            $createdAt,
            Email::NORMAL_IMPORTANCE,
            null,
            null,
            $entity->getOwner()
        );
        $this->setProtectedCreatedAtDate($emailUser, $createdAt);

        return $emailUser;
    }

    /**
     * Create Email Body
     *
     * @param $body
     * @return EmailBody
     */
    protected function createEmailBody($body)
    {
        return $this->emailEntityBuilder->body(
            $body,
            false,
            true
        );
    }

    /**
     * Add incoming folder for origin
     *
     * @param EmailOrigin $origin
     * @param             $type
     * @return EmailFolder
     * @throws EntityNotFoundException
     */
    protected function getFolder(EmailOrigin $origin, $type)
    {
        if ($origin->getFolder($type)) {
            return $origin->getFolder($type);
        }

        if ($type === FolderType::INBOX) {
            $folder = new EmailFolder();
            $folder
                ->setType($type)
                ->setFullName('inbox')
                ->setOrigin($origin)
                ->setName('inbox');
            $origin->addFolder($folder);
            $this->em->persist($origin);

            return $folder;
        } else {
            throw new EntityNotFoundException('Folder ' . $type . ' not found');
        }
    }

    /**
     * @todo: should be refactored in BAP-7856
     *
     * @param EmailUser     $emailUser
     * @param \DateTime $createdAt
     */
    protected function setProtectedCreatedAtDate(EmailUser $emailUser, \DateTime $createdAt)
    {
        $class   = new \ReflectionClass($emailUser);
        $created = $class->getProperty('createdAt');
        $created->setAccessible(true);
        $created->setValue($emailUser, $createdAt);

        $email = $emailUser->getEmail();
        $class   = new \ReflectionClass($email);
        $created = $class->getProperty('created');
        $created->setAccessible(true);
        $created->setValue($email, $createdAt);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 16;
    }
}
