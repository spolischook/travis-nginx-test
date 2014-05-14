<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Sync;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use OroPro\Bundle\EwsBundle\Entity\EwsEmail;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailFolder;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;
use OroPro\Bundle\EwsBundle\Manager\DTO\Email;
use OroPro\Bundle\EwsBundle\Manager\DTO\ItemId;
use OroPro\Bundle\EwsBundle\Sync\EwsEmailSynchronizationProcessor;
use OroPro\Bundle\EwsBundle\Sync\FolderInfo;

class EwsEmailSynchronizationProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $log;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailEntityBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailAddressManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $knownEmailAddressChecker;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $manager;

    protected function setUp()
    {
        $this->log = $this->getMock('Psr\Log\LoggerInterface');
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailEntityBuilder = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailAddressManager = $this->getMockBuilder(
            'Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->knownEmailAddressChecker = $this->getMockBuilder('Oro\Bundle\EmailBundle\Sync\KnownEmailAddressChecker')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Manager\EwsEmailManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testEnsureFolderPersistedForNewFolder()
    {
        $processor = new EwsEmailSynchronizationProcessor(
            $this->log,
            $this->em,
            $this->emailEntityBuilder,
            $this->emailAddressManager,
            $this->knownEmailAddressChecker,
            $this->manager
        );

        $origin = new EwsEmailOrigin();
        $folders = [];
        $folderId = new EwsType\FolderIdType();
        $folderId->Id = '123';
        $folderId->ChangeKey = 'CK';

        ReflectionUtil::callProtectedMethod(
            $processor,
            'ensureFolderPersisted',
            [
                $origin,
                &$folders,
                $folderId,
                'Inbox/Test',
                'Test',
                EmailFolder::OTHER
            ]
        );

        $this->assertCount(1, $origin->getFolders());
        $this->assertCount(1, $folders);

        /** @var FolderInfo $folderInfo */
        $folderInfo = $folders[$folderId->Id];
        $this->assertTrue($folderInfo->needSynchronization);
        $this->assertNull($folderInfo->folderType);
        $this->assertEquals($folderId->Id, $folderInfo->ewsFolder->getEwsId());
        $this->assertEquals($folderId->ChangeKey, $folderInfo->ewsFolder->getEwsChangeKey());
        $this->assertEquals('Inbox/Test', $folderInfo->ewsFolder->getFolder()->getFullName());
        $this->assertEquals('Test', $folderInfo->ewsFolder->getFolder()->getName());
        $this->assertEquals(EmailFolder::OTHER, $folderInfo->ewsFolder->getFolder()->getType());
    }

    public function testEnsureFolderPersistedForExistingFolder()
    {
        $processor = new EwsEmailSynchronizationProcessor(
            $this->log,
            $this->em,
            $this->emailEntityBuilder,
            $this->emailAddressManager,
            $this->knownEmailAddressChecker,
            $this->manager
        );

        $origin = new EwsEmailOrigin();
        $folders = [];
        $folderId = new EwsType\FolderIdType();
        $folderId->Id = '123';
        $folderId->ChangeKey = 'CK';

        $existEwsFolder = $this->createEwsEmailFolder();
        $existEwsFolder->getFolder()->setFullName('Inbox/TestOld');
        $existEwsFolder->getFolder()->setName('TestOld');
        $existEwsFolder->getFolder()->setType(EmailFolder::DRAFTS);
        $existEwsFolder->setEwsId($folderId->Id);
        $existEwsFolder->setEwsChangeKey('old_ck');
        $existFolderInfo = new FolderInfo($existEwsFolder, false);
        $folders[$folderId->Id] = $existFolderInfo;
        $origin->addFolder($existEwsFolder->getFolder());

        $this->assertCount(1, $origin->getFolders());
        $this->assertCount(1, $folders);

        ReflectionUtil::callProtectedMethod(
            $processor,
            'ensureFolderPersisted',
            [
                $origin,
                &$folders,
                $folderId,
                'Inbox/Test',
                'Test',
                EmailFolder::OTHER
            ]
        );

        $this->assertCount(1, $origin->getFolders());
        $this->assertCount(1, $folders);

        /** @var FolderInfo $folderInfo */
        $folderInfo = $folders[$folderId->Id];
        $this->assertTrue($folderInfo->needSynchronization);
        $this->assertNull($folderInfo->folderType);
        $this->assertEquals('123', $folderInfo->ewsFolder->getEwsId());
        $this->assertEquals('CK', $folderInfo->ewsFolder->getEwsChangeKey());
        $this->assertEquals('Inbox/Test', $folderInfo->ewsFolder->getFolder()->getFullName());
        $this->assertEquals('Test', $folderInfo->ewsFolder->getFolder()->getName());
        $this->assertEquals(EmailFolder::OTHER, $folderInfo->ewsFolder->getFolder()->getType());
    }

    public function testEnsureFolderPersistedForExistingFolderWithNoChanges()
    {
        $processor = new EwsEmailSynchronizationProcessor(
            $this->log,
            $this->em,
            $this->emailEntityBuilder,
            $this->emailAddressManager,
            $this->knownEmailAddressChecker,
            $this->manager
        );

        $origin = new EwsEmailOrigin();
        $folders = [];
        $folderId = new EwsType\FolderIdType();
        $folderId->Id = '123';
        $folderId->ChangeKey = 'CK';

        $existEwsFolder = $this->createEwsEmailFolder();
        $existEwsFolder->getFolder()->setFullName('Inbox/TestOld');
        $existEwsFolder->getFolder()->setName('TestOld');
        $existEwsFolder->getFolder()->setType(EmailFolder::DRAFTS);
        $existEwsFolder->setEwsId($folderId->Id);
        $existEwsFolder->setEwsChangeKey($folderId->ChangeKey);
        $existFolderInfo = new FolderInfo($existEwsFolder, false);
        $folders[$folderId->Id] = $existFolderInfo;
        $origin->addFolder($existEwsFolder->getFolder());

        $this->assertCount(1, $origin->getFolders());
        $this->assertCount(1, $folders);

        ReflectionUtil::callProtectedMethod(
            $processor,
            'ensureFolderPersisted',
            [
                $origin,
                &$folders,
                $folderId,
                'Inbox/Test',
                'Test',
                EmailFolder::OTHER
            ]
        );

        $this->assertCount(1, $origin->getFolders());
        $this->assertCount(1, $folders);

        /** @var FolderInfo $folderInfo */
        $folderInfo = $folders[$folderId->Id];
        $this->assertTrue($folderInfo->needSynchronization);
        $this->assertNull($folderInfo->folderType);
        $this->assertEquals('123', $folderInfo->ewsFolder->getEwsId());
        $this->assertEquals('CK', $folderInfo->ewsFolder->getEwsChangeKey());
        $this->assertEquals('Inbox/Test', $folderInfo->ewsFolder->getFolder()->getFullName());
        $this->assertEquals('Test', $folderInfo->ewsFolder->getFolder()->getName());
        $this->assertEquals(EmailFolder::OTHER, $folderInfo->ewsFolder->getFolder()->getType());
    }

    public function testEnsureDistinguishedFolderInitialized()
    {
        $processor = $this->createProcessor(['ensureFolderPersisted']);

        $origin = new EwsEmailOrigin();
        $folders = [];

        $distinguishedFolder = new EwsType\FolderType();
        $distinguishedFolder->FolderId = new EwsType\FolderIdType();
        $distinguishedFolder->FolderId->Id = '123';
        $distinguishedFolder->FolderId->ChangeKey = 'CK';
        $distinguishedFolder->DisplayName = 'Test';

        $childFolder = new EwsType\FolderType();
        $childFolder->FolderId = new EwsType\FolderIdType();
        $childFolder->FolderId->Id = '123_c';
        $childFolder->FolderId->ChangeKey = 'CK_c';
        $childFolder->DisplayName = 'Test_c';
        $childFolder->ParentFolderId = $distinguishedFolder->FolderId;

        $this->manager->expects($this->once())
            ->method('getDistinguishedFolderName')
            ->with(EmailFolder::SENT)
            ->will($this->returnValue('SentItems'));
        $this->manager->expects($this->once())
            ->method('getDistinguishedFolder')
            ->with('SentItems')
            ->will($this->returnValue($distinguishedFolder));

        $distinguishedFolderInfo = new FolderInfo(new EwsEmailFolder(), false);
        $this->assertNull($distinguishedFolderInfo->folderType);
        $childFolderInfo = new FolderInfo(new EwsEmailFolder(), false);
        $this->assertNull($childFolderInfo->folderType);

        $this->manager->expects($this->once())
            ->method('getFolders')
            ->with($distinguishedFolder->FolderId, true)
            ->will($this->returnValue([$childFolder]));

        $processor->expects($this->at(0))
            ->method('ensureFolderPersisted')
            ->with(
                $origin,
                $folders,
                $distinguishedFolder->FolderId,
                $distinguishedFolder->DisplayName,
                $distinguishedFolder->DisplayName,
                EmailFolder::SENT
            )
            ->will($this->returnValue($distinguishedFolderInfo));
        $processor->expects($this->at(1))
            ->method('ensureFolderPersisted')
            ->with(
                $origin,
                $folders,
                $childFolder->FolderId,
                'Test/Test_c',
                $childFolder->DisplayName,
                EmailFolder::OTHER
            )
            ->will($this->returnValue($childFolderInfo));

        $result = ReflectionUtil::callProtectedMethod(
            $processor,
            'ensureDistinguishedFolderInitialized',
            [
                &$folders,
                $origin,
                EmailFolder::SENT
            ]
        );

        $this->assertEquals(2, $result);

        $this->assertEquals(EmailFolder::SENT, $distinguishedFolderInfo->folderType);
        $this->assertEquals(EmailFolder::SENT, $childFolderInfo->folderType);
    }

    public function testEnsureFoldersInitialized()
    {
        $processor = $this->createProcessor(['ensureDistinguishedFolderInitialized']);

        $origin = new EwsEmailOrigin();
        $folders = [];

        $processor->expects($this->at(0))
            ->method('ensureDistinguishedFolderInitialized')
            ->with(
                $folders,
                $origin,
                EmailFolder::SENT
            )
            ->will($this->returnValue(2));
        $processor->expects($this->at(1))
            ->method('ensureDistinguishedFolderInitialized')
            ->with(
                $folders,
                $origin,
                EmailFolder::INBOX
            )
            ->will($this->returnValue(3));

        $this->log->expects($this->at(1))
            ->method('notice')
            ->with('Retrieved 5 folder(s).');

        ReflectionUtil::callProtectedMethod(
            $processor,
            'ensureFoldersInitialized',
            [
                &$folders,
                $origin
            ]
        );
    }

    public function testGetFolders()
    {
        $processor = $this->createProcessor(['ensureFoldersInitialized']);

        $origin = new EwsEmailOrigin();
        $origin->setSynchronizedAt(new \DateTime('2014-04-15 10:30:00'));

        $ewsFolder1 = $this->createEwsEmailFolder();
        $ewsFolder1->setEwsId('1');

        $ewsFolder2 = $this->createEwsEmailFolder();
        $ewsFolder2->setEwsId('2');
        $ewsFolder2->getFolder()->setSynchronizedAt(new \DateTime('2014-04-15 9:30:00'));

        $ewsFolder3 = $this->createEwsEmailFolder();
        $ewsFolder3->setEwsId('3');
        $ewsFolder3->getFolder()->setSynchronizedAt(new \DateTime('2014-04-15 11:30:00'));

        $folders = [];
        $folders['1'] = new FolderInfo($ewsFolder1, true);
        $folders['2'] = new FolderInfo($ewsFolder2, true);
        $folders['3'] = new FolderInfo($ewsFolder3, false);

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getResult'))
            ->getMockForAbstractClass();
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('ews')
            ->will($this->returnValue($qb));
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroProEwsBundle:EwsEmailFolder')
            ->will($this->returnValue($repo));

        $index = 0;
        $qb->expects($this->at($index++))
            ->method('select')
            ->with('ews, f')
            ->will($this->returnSelf());
        $qb->expects($this->at($index++))
            ->method('innerJoin')
            ->with('ews.folder', 'f')
            ->will($this->returnSelf());
        $qb->expects($this->at($index++))
            ->method('where')
            ->with('f.origin = ?1')
            ->will($this->returnSelf());
        $qb->expects($this->at($index++))
            ->method('orderBy')
            ->with('f.name')
            ->will($this->returnSelf());
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with(1, $origin)
            ->will($this->returnSelf());
        $qb->expects($this->at($index++))
            ->method('getQuery')
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('getResult')
            ->will(
                $this->returnValue(
                    [
                        $ewsFolder1,
                        $ewsFolder2,
                        $ewsFolder3,
                    ]
                )
            );

        $processor->expects($this->once())
            ->method('ensureFoldersInitialized')
            ->with(
                $folders,
                $origin
            )
            ->will($this->returnValue(3));

        $result = ReflectionUtil::callProtectedMethod(
            $processor,
            'getFolders',
            [
                $origin
            ]
        );

        $this->assertEquals($folders, $result);
    }

    public function testProcess()
    {
        $processor = $this->createProcessor(['getFolders', 'loadEmails']);

        $origin = new EwsEmailOrigin();
        $origin->setUserEmail('test@example.com');
        $origin->setSynchronizedAt(new \DateTime('2014-04-15 9:30:00'));

        $ewsFolder1 = $this->createEwsEmailFolder();
        $ewsFolder1->setEwsId('1');
        $ewsFolder1->getFolder()->setFullName('Folder1');
        $ewsFolder2 = $this->createEwsEmailFolder();
        $ewsFolder2->setEwsId('2');
        $ewsFolder2->getFolder()->setFullName('Folder2');
        $ewsFolder2->getFolder()->setType(EmailFolder::OTHER);
        $ewsFolder2->getFolder()->setSynchronizedAt(new \DateTime('2014-04-15 11:30:00'));

        $folders = [];
        $folders[$ewsFolder1->getEwsId()] = new FolderInfo($ewsFolder1, false);
        $folders[$ewsFolder2->getEwsId()] = new FolderInfo($ewsFolder2, true);

        $sqb = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $sq = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery')
            ->disableOriginalConstructor()
            ->getMock();
        $sqb->expects($this->once())
            ->method('sent')
            ->with($ewsFolder2->getFolder()->getSynchronizedAt());
        $sqb->expects($this->once())
            ->method('get')
            ->will($this->returnValue($sq));

        $this->manager->expects($this->once())
            ->method('selectUser')
            ->with('test@example.com');
        $this->emailEntityBuilder->expects($this->once())
            ->method('clear');
        $this->manager->expects($this->once())
            ->method('selectFolder')
            ->with($ewsFolder2->getEwsId());
        $this->emailEntityBuilder->expects($this->once())
            ->method('setFolder')
            ->with($ewsFolder2->getFolder());
        $this->manager->expects($this->once())
            ->method('getSearchQueryBuilder')
            ->will($this->returnValue($sqb));

        $processor->expects($this->once())
            ->method('getFolders')
            ->will($this->returnValue($folders));
        $processor->expects($this->once())
            ->method('loadEmails')
            ->with($folders[$ewsFolder2->getEwsId()], $sq)
            ->will($this->returnValue(new \DateTime('2014-04-15 12:30:00')));

        $this->em->expects($this->at(0))
            ->method('flush')
            ->with($ewsFolder1->getFolder());
        $this->em->expects($this->at(1))
            ->method('flush')
            ->with($ewsFolder2->getFolder());

        $syncStartTime = new \DateTime('2014-04-15 10:30:00');
        $processor->process($origin, $syncStartTime);

        $this->assertEquals(
            new \DateTime('2014-04-15 12:30:00'),
            $ewsFolder2->getFolder()->getSynchronizedAt()
        );
    }

    public function testSaveEmails()
    {
        $processor = $this->createProcessor();

        $folder = new EmailFolder();
        ReflectionUtil::setId($folder, 123);

        $ewsFolder = new EwsEmailFolder();
        $ewsFolder->setFolder($folder);
        $folderInfo = new FolderInfo($ewsFolder, true);

        $email1 = new Email($this->manager);
        $email1Id = new ItemId('test1', 'ck1');
        $email1->setId($email1Id);
        $email1->setMessageId('message_id');

        $email2 = new Email($this->manager);
        $email2Id = new ItemId('test2', 'ck2');
        $email2
            ->setId($email2Id)
            ->setSubject('subject2')
            ->setFrom('from_email')
            ->addToRecipient('to_email')
            ->addCcRecipient('cc_email')
            ->addBccRecipient('bcc_email')
            ->setSentAt(new \DateTime('2014-04-15 10:00:00'))
            ->setReceivedAt(new \DateTime('2014-04-15 11:00:00'))
            ->setInternalDate(new \DateTime('2014-04-15 12:00:00'))
            ->setImportance(1)
            ->setMessageId('message_id')
            ->setXMessageId('x_message_id')
            ->setXThreadId('x_thread_id');

        $this->initSaveEmailQBMocks($folder, $email1Id, $email2Id);

        $newEmailEntity = new EmailEntity();
        $newEwsEmailEntity = new EwsEmail();
        $newEwsEmailEntity
            ->setEmail($newEmailEntity)
            ->setEwsId($email2->getId()->getId())
            ->setEwsChangeKey($email2->getId()->getChangeKey())
            ->setEwsFolder($ewsFolder);

        $this->emailEntityBuilder->expects($this->once())
            ->method('removeEmails');
        $this->emailEntityBuilder->expects($this->once())
            ->method('email')
            ->with(
                $email2->getSubject(),
                $email2->getFrom(),
                $email2->getToRecipients(),
                $email2->getSentAt(),
                $email2->getReceivedAt(),
                $email2->getInternalDate(),
                $email2->getImportance(),
                $email2->getCcRecipients(),
                $email2->getBccRecipients()
            )
            ->will($this->returnValue($newEmailEntity));
        $this->em->expects($this->exactly(2))
            ->method('persist')
            ->with($newEwsEmailEntity);

        $batch = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\EmailEntityBatchProcessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailEntityBuilder->expects($this->exactly(2))
            ->method('getBatch')
            ->will($this->returnValue($batch));
        $batch->expects($this->once())
            ->method('persist');

        $newEmailEntity->setMessageId('message_id');
        $new2EmailEntity = new EmailEntity();
        ReflectionUtil::setId($new2EmailEntity, '123');

        $emails = [
            $newEmailEntity,
            $new2EmailEntity
        ];

        $batch->expects($this->once())
            ->method('getEmails')
            ->with()
            ->will($this->returnValue($emails));

        $this->em->expects($this->once())
            ->method('flush');

        ReflectionUtil::callProtectedMethod(
            $processor,
            'saveEmails',
            [
                [$email1, $email2],
                $folderInfo
            ]
        );

        $this->assertEquals($email2->getMessageId(), $newEmailEntity->getMessageId());
        $this->assertEquals($email2->getXMessageId(), $newEmailEntity->getXMessageId());
        $this->assertEquals($email2->getXThreadId(), $newEmailEntity->getXThreadId());
        $this->assertEquals($folder, $newEmailEntity->getFolders()->first());
    }

    protected function initSaveEmailQBMocks(EmailFolder $folder, ItemId $email1Id, ItemId $email2Id)
    {
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getResult'))
            ->getMockForAbstractClass();
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->will($this->returnValue($qb));
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroProEwsBundle:EwsEmail')
            ->will($this->returnValue($repo));

        $index = 0;
        $qb->expects($this->at($index++))
            ->method('select')
            ->with('e.ewsId, se.id')
            ->will($this->returnSelf());
        $qb->expects($this->at($index++))
            ->method('innerJoin')
            ->with('e.email', 'se')
            ->will($this->returnSelf());
        $qb->expects($this->at($index++))
            ->method('innerJoin')
            ->with('se.folders', 'sf')
            ->will($this->returnSelf());
        $qb->expects($this->at($index++))
            ->method('where')
            ->with('sf.id = :folderId AND e.ewsId IN (:ewsIds)')
            ->will($this->returnSelf());
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('folderId', $folder->getId())
            ->will($this->returnSelf());
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('ewsIds', [$email1Id->getId(), $email2Id->getId()])
            ->will($this->returnSelf());
        $qb->expects($this->at($index++))
            ->method('getQuery')
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('getResult')
            ->will(
                $this->returnValue(
                    [
                        ['ewsId' => $email1Id->getId(), 'id' => $email1Id->getId()],
                    ]
                )
            );
    }

    /**
     * @return EwsEmailFolder
     */
    protected function createEwsEmailFolder()
    {
        $folder = new EmailFolder();
        $ewsFolder = new EwsEmailFolder();
        $ewsFolder->setFolder($folder);

        return $ewsFolder;
    }

    /**
     * @param array $methods
     * @return EwsEmailSynchronizationProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProcessor(array $methods = [])
    {
        return $this->getMockBuilder('OroPro\Bundle\EwsBundle\Sync\EwsEmailSynchronizationProcessor')
            ->setConstructorArgs(
                [
                    $this->log,
                    $this->em,
                    $this->emailEntityBuilder,
                    $this->emailAddressManager,
                    $this->knownEmailAddressChecker,
                    $this->manager
                ]
            )
            ->setMethods($methods)
            ->getMock();
    }
}
