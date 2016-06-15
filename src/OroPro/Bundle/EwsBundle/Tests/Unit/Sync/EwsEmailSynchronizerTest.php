<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Sync;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerFactory;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\OrganizationBundle\OroOrganizationBundle;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\UserBundle\OroUserBundle;

use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;
use OroPro\Bundle\EwsBundle\OroProEwsBundle;
use OroPro\Bundle\EwsBundle\Sync\EwsEmailSynchronizationProcessorFactory;
use OroPro\Bundle\EwsBundle\Tests\Unit\Sync\Fixtures\TestEwsEmailSynchronizer;

class EwsEmailSynchronizerTest extends OrmTestCase
{
    /** @var TestEwsEmailSynchronizer */
    private $sync;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var EntityManagerMock */
    private $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailEntityBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailAddressManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $ewsConfigurator;

    protected function setUp()
    {
        $this->initializeEntityManager();

        $this->logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->emailEntityBuilder = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailAddressManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailAddressManager->expects($this->any())
            ->method('getEmailAddressProxyClass')
            ->will($this->returnValue('OroPro\Bundle\EwsBundle\Tests\Unit\Sync\Fixtures\Entity\TestEmailAddress'));
        $emailOwnerProviderStorage = new EmailOwnerProviderStorage();
        $userEmailOwnerProvider = $this->getMock('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface');
        $userEmailOwnerProvider->expects($this->any())
            ->method('getEmailOwnerClass')
            ->will($this->returnValue('Oro\Bundle\UserBundle\Entity\User'));
        $emailOwnerProviderStorage->addProvider($userEmailOwnerProvider);
        $ewsConnector = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Connector\EwsConnector')
            ->disableOriginalConstructor()
            ->getMock();
        $this->ewsConfigurator = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Provider\EwsServiceConfigurator')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->any())
            ->method('getManager')
            ->with(null)
            ->will($this->returnValue($this->em));

        $knownEmailAddressCheckerFactory = new KnownEmailAddressCheckerFactory(
            $doctrine,
            $this->emailAddressManager,
            new EmailAddressHelper(),
            new EmailOwnerProviderStorage(),
            []
        );
        $syncProcessorFactory = new EwsEmailSynchronizationProcessorFactory($doctrine, $this->emailEntityBuilder);

        $this->sync = new TestEwsEmailSynchronizer(
            $doctrine,
            $knownEmailAddressCheckerFactory,
            $syncProcessorFactory,
            $this->emailAddressManager,
            $emailOwnerProviderStorage,
            $ewsConnector,
            $this->ewsConfigurator,
            'Oro\Bundle\UserBundle\Entity\User'
        );

        $this->sync->setLogger($this->logger);
    }

    protected function initializeEntityManager()
    {
        $oroBasePath = dirname((new \ReflectionClass(new OroUserBundle()))->getFileName()) . '/..';
        $oroProBasePath = dirname((new \ReflectionClass(new OroProEwsBundle()))->getFileName()) . '/..';
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            [
                $oroBasePath . '/EmailBundle/Entity',
                $oroBasePath . '/UserBundle/Entity',
                $oroProBasePath . '/EwsBundle/Entity',
                $oroProBasePath . '/EwsBundle/Tests/Unit/Sync/Fixtures/Entity',
            ]
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'OroEmailBundle' => 'Oro\Bundle\EmailBundle\Entity',
                'OroUserBundle' => 'Oro\Bundle\UserBundle\Entity',
                'OroProEwsBundle' => 'OroPro\Bundle\EwsBundle\Entity',
            ]
        );
    }

    public function testSupports()
    {
        $this->assertTrue($this->sync->supports(new EwsEmailOrigin()));
        $this->assertFalse($this->sync->supports(new InternalEmailOrigin()));
    }

    public function testCheckConfiguration()
    {
        $this->ewsConfigurator->expects($this->exactly(3))
            ->method('getServer')
            ->will($this->onConsecutiveCalls(null, '', 'test'));

        $this->ewsConfigurator->expects($this->exactly(3))
            ->method('isEnabled')
            ->will($this->onConsecutiveCalls(false, false, true));

        $this->assertFalse($this->sync->callCheckConfiguration());
        $this->assertFalse($this->sync->callCheckConfiguration());
        $this->assertTrue($this->sync->callCheckConfiguration());
    }

    public function testCreateSynchronizationProcessor()
    {
        $processor = $this->sync->callCreateSynchronizationProcessor(new EwsEmailOrigin());
        $this->assertInstanceOf(
            'OroPro\Bundle\EwsBundle\Sync\EwsEmailSynchronizationProcessor',
            $processor
        );
    }

    public function testInitializeOrigins()
    {
        $this->ewsConfigurator->expects($this->once())
            ->method('getServer')
            ->will($this->returnValue('test_server'));
        $this->ewsConfigurator->expects($this->once())
            ->method('getDomains')
            ->will($this->returnValue(['domain1.com', 'domain2.com']));

        $records = [
            ['id_0' => 1, 'email_1' => 'test@example.com']
        ];

        $selectStmt = $this->createFetchStatementMock($records);
        $selectStmt->expects($this->at(0))
            ->method('bindValue')
            ->with(1, true);
        $selectStmt->expects($this->at(1))
            ->method('bindValue')
            ->with(2, 'test_server');
        $insertOriginStmt = $this->getMock('Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\StatementMock');
        $insertOriginStmt->expects($this->at(7))
            ->method('bindValue')
            ->with(7, 1);
        $insertOriginStmt->expects($this->at(8))
            ->method('bindValue')
            ->with(8, null);
        $insertOriginStmt->expects($this->at(9))
            ->method('bindValue')
            ->with(9, 'test_server');
        $insertOriginStmt->expects($this->at(10))
            ->method('bindValue')
            ->with(10, 'test@example.com');
        $insertOriginStmt->expects($this->at(11))
            ->method('bindValue')
            ->with(11, 'ewsemailorigin');

        $statementCount = -1;
        $actualSqls = [];
        $statements = [
            $selectStmt,
            $insertOriginStmt
        ];
        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('prepare')
            ->will(
                $this->returnCallback(
                    function ($prepareString) use (&$statements, &$actualSqls, &$statementCount) {
                        $statementCount++;
                        $actualSqls[$statementCount] = $prepareString;

                        return $statements[$statementCount];
                    }
                )
            );

        $this->sync->callInitializeOrigins();

        // select users without origins
        $selectClause = 'SELECT o0_.id AS id_0, o1_.email AS email_1';
        $actualSql = $actualSqls[0];
        $actualSql = $selectClause
            . substr($actualSql, strpos($actualSql, ' FROM oro_user o0_'));
        $allOriginsInStart = ' AND o3_.name IN (';
        $actualSql = substr($actualSql, 0, strpos($actualSql, $allOriginsInStart))
            . $allOriginsInStart
            . 'ALL_ORIGINS'
            . substr(
                $actualSql,
                strpos($actualSql, ')', strpos($actualSql, $allOriginsInStart) + strlen($allOriginsInStart))
            );
        $expectedSql = $selectClause
            . ' FROM oro_user o0_'
            . ' INNER JOIN oro_email_address o1_ ON (o1_.owner_user_id = o0_.id)'
            . ' WHERE (NOT (EXISTS ('
            . 'SELECT o2_.id'
            . ' FROM oro_user o2_'
            . ' INNER JOIN oro_email_origin o3_ ON o2_.id = o3_.owner_id'
            . ' AND o3_.name IN (ALL_ORIGINS)'
            . ' INNER JOIN oro_email_address o4_ ON (o4_.owner_user_id = o2_.id)'
            . ' INNER JOIN oro_email_origin o5_ ON (o5_.id = o3_.id) AND o5_.name IN (\'ewsemailorigin\')'
            . ' WHERE o2_.id = o0_.id AND o3_.isActive = ? AND o5_.ews_server = ?'
            . ' AND o5_.ews_user_email = o4_.email)))'
            . ' AND (o1_.email LIKE \'%@domain1.com\' OR o1_.email LIKE \'%@domain2.com\')'
            . ' ORDER BY o0_.id ASC';
        $this->assertEquals($expectedSql, $actualSql);

        // insert origin
        $actualSql = $actualSqls[1];
        $expectedSql = 'INSERT INTO oro_email_origin'
            . ' (mailbox_name, isActive, sync_code_updated, synchronized, '
            . 'sync_code, sync_count, owner_id, organization_id, ews_server, ews_user_email, name)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testSyncNoOrigin()
    {
        $maxConcurrentTasks = 3;
        $minExecPeriodInMin = 1;

        $this->ewsConfigurator
            ->expects(self::exactly(2))
            ->method('getServer')
            ->will(self::returnValue('test'));
        $this->ewsConfigurator
            ->expects(self::exactly(2))
            ->method('isEnabled')
            ->will(self::returnValue(false));

        $this->logger->expects(self::once())->method('info');

        self::assertFalse($this->sync->callCheckConfiguration());

        $this->sync->sync($maxConcurrentTasks, $minExecPeriodInMin);
    }
}
