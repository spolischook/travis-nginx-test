<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;
use OroPro\Bundle\EwsBundle\Entity\Repository\EwsEmailFolderRepository;

class EwsEmailFolderRepositoryTest extends OrmTestCase
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

    public function testGetFoldersByOriginQueryBuilder()
    {
        $origin = new EwsEmailOrigin();

        /** @var EwsEmailFolderRepository $repo */
        $repo = $this->em->getRepository('OroProEwsBundle:EwsEmailFolder');

        $qb    = $repo->getFoldersByOriginQueryBuilder($origin);
        $query = $qb->getQuery();

        $this->assertEquals(
            'SELECT ews_folder'
            . ' FROM OroPro\Bundle\EwsBundle\Entity\EwsEmailFolder ews_folder'
            . ' INNER JOIN ews_folder.folder folder'
            . ' WHERE folder.origin = :origin AND folder.outdatedAt IS NULL'
            . ' ORDER BY folder.synchronizedAt ASC',
            $query->getDQL()
        );

        $this->assertSame($origin, $query->getParameter('origin')->getValue());
    }

    public function testGetFoldersByOriginQueryBuilderWithOutdated()
    {
        $origin = new EwsEmailOrigin();

        /** @var EwsEmailFolderRepository $repo */
        $repo = $this->em->getRepository('OroProEwsBundle:EwsEmailFolder');

        $qb    = $repo->getFoldersByOriginQueryBuilder($origin, true);
        $query = $qb->getQuery();

        $this->assertEquals(
            'SELECT ews_folder'
            . ' FROM OroPro\Bundle\EwsBundle\Entity\EwsEmailFolder ews_folder'
            . ' INNER JOIN ews_folder.folder folder'
            . ' WHERE folder.origin = :origin'
            . ' ORDER BY folder.synchronizedAt ASC',
            $query->getDQL()
        );

        $this->assertSame($origin, $query->getParameter('origin')->getValue());
    }
}
