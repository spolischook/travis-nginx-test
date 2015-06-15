<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;

class LoadOutlookEmailEntitiesData extends AbstractFixture implements ContainerAwareInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var EmailEntityBuilder */
    protected $emailEntityBuilder;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->emailEntityBuilder = $container->get('oro_email.email.entity.builder');
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->em = $manager;

        $email1 = $this->createEmail(
            'Test Email 1',
            'email1@orocrm-pro.func-test',
            'test1@example.com',
            'test2@example.com'
        );
        $email1->addActivityTarget($this->getReference('test_contact1'));
        $email1->addActivityTarget($this->getReference('test_contact2'));
        $email1->addActivityTarget($this->getReference('default_account'));

        $email2 = $this->createEmail(
            'Test Email 1',
            'email2@orocrm-pro.func-test',
            'test1@example.com',
            'test2@example.com',
            'test3@example.com',
            'test4@example.com'
        );
        $email2->addActivityTarget($this->getReference('default_lead'));

        $this->emailEntityBuilder->getBatch()->persist($this->em);
        $this->em->flush();
    }

    /**
     * @param string               $subject
     * @param string               $messageId
     * @param string               $from
     * @param string|string[]      $to
     * @param string|string[]|null $cc
     * @param string|string[]|null $bcc
     *
     * @return Email
     */
    protected function createEmail($subject, $messageId, $from, $to, $cc = null, $bcc = null)
    {
        $origin = $this->em
            ->getRepository('OroEmailBundle:InternalEmailOrigin')
            ->findOneBy(['internalName' => InternalEmailOrigin::BAP]);
        $folder = $origin->getFolder(FolderType::SENT);
        $date   = new \DateTime('now', new \DateTimeZone('UTC'));

        $email = $this->emailEntityBuilder->email(
            $subject,
            $from,
            $to,
            $date,
            $date,
            $date,
            Email::NORMAL_IMPORTANCE,
            $cc,
            $bcc
        );
        $email->addFolder($folder);
        $email->setMessageId($messageId);

        return $email;
    }
}
