<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;

use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\AccountBundle\Entity\Account;

class LoadEmailActivityData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var EmailEntityBuilder
     */
    protected $emailEntityBuilder;

    /**
     * @var Processor
     */
    protected $mailerProcessor;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $this->emailEntityBuilder = $container->get('oro_email.email.entity.builder');
        $this->mailerProcessor = $container->get('oro_email.mailer.processor');
    }


    /**
     * @return array
     */
    public function getData()
    {
        return [
            'contact_emails' => $this->loadData('activities/contact/emails.csv'),
            'account_emails' => $this->loadData('activities/account/emails.csv')
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

        foreach ($data['account_emails'] as $emailData) {
            $account = $this->getAccountReference($emailData['account uid']);
            $this->addActivity($manager, $account, $emailData);
        }

        foreach ($data['contact_emails'] as $emailData) {
            $contact = $this->getContactReference($emailData['contact uid']);
            $this->addActivity($manager, $contact, $emailData);
        }
        $manager->flush();
    }

    /**
     * Create Email activity for $entity
     *
     * @param ObjectManager $manager
     * @param Account|Contact $entity
     * @param $data
     */
    protected function addActivity(ObjectManager $manager, $entity, $data)
    {
        if ($entity->getEmail() !== null) {
            $user = $entity->getOwner();
            $origin = $this->mailerProcessor->getEmailOrigin($user->getEmail());
            $createdAt = $this->generateUpdatedDate($entity->getCreatedAt());

            /** @var Email $email */
            $email = $this->emailEntityBuilder->email(
                $data['subject'],
                $user->getEmail(),
                $entity->getEmail(),
                $createdAt,
                $createdAt,
                $createdAt,
                Email::NORMAL_IMPORTANCE
            );

            $email->addActivityTarget($entity);

            /**
             * @todo: should be refactored in BAP-7856
             */
            $class = new \ReflectionClass($email);
            $created = $class->getProperty('created');
            $created->setAccessible(true);
            $created->setValue($email, $createdAt);

            $emailBody = $this->emailEntityBuilder->body(
                $data['body'],
                false,
                true
            );
            $email->setEmailBody($emailBody);
            $email->addFolder($origin->getFolder(FolderType::SENT));
            $email->setMessageId(sprintf('id.%s@%s', uniqid(), '@orocrm-pro.demo-data.generated'));
            $manager->getClassMetadata(get_class($email))->setLifecycleCallbacks([]);
            $this->emailEntityBuilder->getBatch()->persist($manager);
        }
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
}
