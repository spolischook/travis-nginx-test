<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;
use OroPro\Bundle\EwsBundle\Entity\Repository\EwsEmailRepository;

class EwsEmailRepositoryTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    protected function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            [
                'OroPro\Bundle\EwsBundle\Entity',
                'Oro\Bundle\EmailBundle\Entity',
            ]
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'OroProEwsBundle' => 'OroPro\Bundle\EwsBundle\Entity'
            ]
        );
    }

    public function testGetEmailsByEwsIdsQueryBuilder()
    {
        $folder = new EmailFolder();
        $ewsIds = ['id1', 'id2'];

        /** @var EwsEmailRepository $repo */
        $repo = $this->em->getRepository('OroProEwsBundle:EwsEmail');

        $qb    = $repo->getEmailsByEwsIdsQueryBuilder($folder, $ewsIds);
        $query = $qb->getQuery();

        $this->assertEquals(
            'SELECT ews_email'
            . ' FROM OroPro\Bundle\EwsBundle\Entity\EwsEmail ews_email'
            . ' INNER JOIN ews_email.email email'
            . ' INNER JOIN email.emailUsers email_users'
            . ' INNER JOIN email_users.folders folders'
            . ' WHERE folders IN(:folder) AND ews_email.ewsId IN (:ewsIds)',
            $query->getDQL()
        );

        $this->assertSame($folder, $query->getParameter('folder')->getValue());
        $this->assertEquals($ewsIds, $query->getParameter('ewsIds')->getValue());
    }

    public function testGetEmailsByMessageIdsQueryBuilder()
    {
        $origin     = new EwsEmailOrigin();
        $messageIds = ['msg1', 'msg2'];

        /** @var EwsEmailRepository $repo */
        $repo = $this->em->getRepository('OroProEwsBundle:EwsEmail');

        $qb    = $repo->getEmailsByMessageIdsQueryBuilder($origin, $messageIds);
        $query = $qb->getQuery();

        $this->assertEquals(
            'SELECT ews_email'
            . ' FROM OroPro\Bundle\EwsBundle\Entity\EwsEmail ews_email'
            . ' INNER JOIN ews_email.ewsFolder ews_folder'
            . ' INNER JOIN ews_email.email email'
            . ' INNER JOIN email.emailUsers email_users'
            . ' WHERE email_users.origin = :origin AND email.messageId IN (:messageIds)',
            $query->getDQL()
        );

        $this->assertSame($origin, $query->getParameter('origin')->getValue());
        $this->assertEquals($messageIds, $query->getParameter('messageIds')->getValue());
    }
}
