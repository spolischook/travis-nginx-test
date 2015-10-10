<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Sync;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\UserBundle\Entity\User;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use OroPro\Bundle\EwsBundle\Entity\EwsEmail;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailFolder;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;
use OroPro\Bundle\EwsBundle\Manager\DTO\Email;
use OroPro\Bundle\EwsBundle\Manager\DTO\ItemId;
use OroPro\Bundle\EwsBundle\Sync\EwsEmailSynchronizationProcessor;
use OroPro\Bundle\EwsBundle\Sync\FolderInfo;

class EwsEmailSynchronizationProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailEntityBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $knownEmailAddressChecker;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $manager;

    protected function setUp()
    {
        $this->logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailEntityBuilder = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->knownEmailAddressChecker =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerInterface')
                ->disableOriginalConstructor()
                ->getMock();
        $this->manager = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Manager\EwsEmailManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testEnsureFolderPersistedForNewFolder()
    {
        $processor = new EwsEmailSynchronizationProcessor(
            $this->em,
            $this->emailEntityBuilder,
            $this->knownEmailAddressChecker,
            $this->manager
        );
        $processor->setLogger($this->logger);

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
                FolderType::OTHER
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
        $this->assertEquals(FolderType::OTHER, $folderInfo->ewsFolder->getFolder()->getType());
    }

    public function testEnsureFolderPersistedForExistingFolder()
    {
        $processor = new EwsEmailSynchronizationProcessor(
            $this->em,
            $this->emailEntityBuilder,
            $this->knownEmailAddressChecker,
            $this->manager
        );
        $processor->setLogger($this->logger);

        $origin = new EwsEmailOrigin();
        $folders = [];
        $folderId = new EwsType\FolderIdType();
        $folderId->Id = '123';
        $folderId->ChangeKey = 'CK';

        $existEwsFolder = $this->createEwsEmailFolder();
        $existEwsFolder->getFolder()->setFullName('Inbox/TestOld');
        $existEwsFolder->getFolder()->setName('TestOld');
        $existEwsFolder->getFolder()->setType(FolderType::DRAFTS);
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
                FolderType::OTHER
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
        $this->assertEquals(FolderType::OTHER, $folderInfo->ewsFolder->getFolder()->getType());
    }

    public function testEnsureFolderPersistedForExistingFolderWithNoChanges()
    {
        $processor = new EwsEmailSynchronizationProcessor(
            $this->em,
            $this->emailEntityBuilder,
            $this->knownEmailAddressChecker,
            $this->manager
        );
        $processor->setLogger($this->logger);

        $origin = new EwsEmailOrigin();
        $folders = [];
        $folderId = new EwsType\FolderIdType();
        $folderId->Id = '123';
        $folderId->ChangeKey = 'CK';

        $existEwsFolder = $this->createEwsEmailFolder();
        $existEwsFolder->getFolder()->setFullName('Inbox/TestOld');
        $existEwsFolder->getFolder()->setName('TestOld');
        $existEwsFolder->getFolder()->setType(FolderType::DRAFTS);
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
                FolderType::OTHER
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
        $this->assertEquals(FolderType::OTHER, $folderInfo->ewsFolder->getFolder()->getType());
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
            ->with(FolderType::SENT)
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
                FolderType::SENT
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
                FolderType::SENT
            )
            ->will($this->returnValue($childFolderInfo));

        $result = ReflectionUtil::callProtectedMethod(
            $processor,
            'ensureDistinguishedFolderInitialized',
            [
                &$folders,
                $origin,
                FolderType::SENT
            ]
        );

        $this->assertEquals(2, $result);

        $this->assertEquals(FolderType::SENT, $distinguishedFolderInfo->folderType);
        $this->assertEquals(FolderType::SENT, $childFolderInfo->folderType);
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
                FolderType::SENT
            )
            ->will($this->returnValue(2));
        $processor->expects($this->at(1))
            ->method('ensureDistinguishedFolderInitialized')
            ->with(
                $folders,
                $origin,
                FolderType::INBOX
            )
            ->will($this->returnValue(3));

        $this->logger->expects($this->at(1))
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

    public function testSyncFolders()
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

        $repo = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Entity\Repository\EwsEmailFolderRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getFoldersByOrigin')
            ->with($this->identicalTo($origin))
            ->will(
                $this->returnValue(
                    [
                        $ewsFolder1,
                        $ewsFolder2,
                        $ewsFolder3,
                    ]
                )
            );
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroProEwsBundle:EwsEmailFolder')
            ->will($this->returnValue($repo));

        $processor->expects($this->once())
            ->method('ensureFoldersInitialized')
            ->with(
                $folders,
                $origin
            )
            ->will($this->returnValue(3));

        $result = ReflectionUtil::callProtectedMethod(
            $processor,
            'syncFolders',
            [
                $origin
            ]
        );

        $this->assertEquals($folders, $result);
    }

    public function testProcess()
    {
        $processor = $this->createProcessor(['syncFolders', 'syncEmails']);

        $origin = new EwsEmailOrigin();
        $origin->setUserEmail('test@example.com');
        $origin->setSynchronizedAt(new \DateTime('2014-04-15 9:30:00'));
        $origin->setOwner(new User());
        $origin->setOrganization(new Organization());

        $ewsFolder1 = $this->createEwsEmailFolder();
        $ewsFolder1->setEwsId('1');
        $ewsFolder1->getFolder()->setFullName('Folder1');
        $ewsFolder2 = $this->createEwsEmailFolder();
        $ewsFolder2->setEwsId('2');
        $ewsFolder2->getFolder()->setFullName('Folder2');
        $ewsFolder2->getFolder()->setType(FolderType::OTHER);
        $ewsFolder2->getFolder()->setSynchronizedAt(new \DateTime('2014-04-15 11:30:00'));

        $folders = [];
        $folders[$ewsFolder1->getEwsId()] = new FolderInfo($ewsFolder1, false);
        $folders[$ewsFolder2->getEwsId()] = new FolderInfo($ewsFolder2, true);

        $this->em->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValue(
                    $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository')
                        ->disableOriginalConstructor()
                        ->getMock()
                )
            );

        $sqb = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $sq = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery')
            ->disableOriginalConstructor()
            ->getMock();
        $sqb->expects($this->once())
            ->method('received')
            ->with($ewsFolder2->getFolder()->getSynchronizedAt());
        $sqb->expects($this->once())
            ->method('get')
            ->will($this->returnValue($sq));

        $mailboxRepository = $this->getMockbuilder('Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:Mailbox')
            ->will($this->returnValue($mailboxRepository));
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
            ->method('syncFolders')
            ->will($this->returnValue($folders));
        $processor->expects($this->once())
            ->method('syncEmails')
            ->with($folders[$ewsFolder2->getEwsId()], $sq)
            ->will($this->returnValue(new \DateTime('2014-04-15 12:30:00')));

        $syncStartTime = new \DateTime('2014-04-15 10:30:00');
        $processor->process($origin, $syncStartTime);

        $this->assertEquals(
            new \DateTime('2014-04-15 12:30:00'),
            $ewsFolder2->getFolder()->getSynchronizedAt()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSaveEmails()
    {
        $processor = new EwsEmailSynchronizationProcessor(
            $this->em,
            $this->emailEntityBuilder,
            $this->knownEmailAddressChecker,
            $this->manager
        );
        $processor->setLogger($this->logger);

        $owner = new User();
        $owner->setUsername('owner');

        $origin = new EwsEmailOrigin();

        $folder = new EmailFolder();
        $folder->setOrigin($origin);
        ReflectionUtil::setId($folder, 123);

        $ewsFolder = new EwsEmailFolder();
        $ewsFolder->setFolder($folder);
        $folderInfo = new FolderInfo($ewsFolder, true);

        $email1 = new Email($this->manager);
        $email1Id = new ItemId('test1', 'ck1');
        $email1->setId($email1Id);
        $email1->setSeen(true);
        $email1->setMessageId('message_id');

        $email2 = new Email($this->manager);
        $email2Id = new ItemId('test2', 'ck2');
        $email2
            ->setId($email2Id)
            ->setSeen(false)
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
            ->setRefs("<testRef@test.tst>")
            ->setXMessageId('x_message_id')
            ->setXThreadId('x_thread_id');

        $repo = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Entity\Repository\EwsEmailRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $emailUserRepo = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    ['OroProEwsBundle:EwsEmail', $repo],
                    ['OroEmailBundle:EmailUser', $emailUserRepo],
                ]
            );
        $repo->expects($this->once())
            ->method('getExistingEwsIds')
            ->with($this->identicalTo($folder), [$email1Id->getId(), $email2Id->getId()])
            ->will($this->returnValue([$email1Id->getId()]));
        $repo->expects($this->once())
            ->method('getEmailsByMessageIds')
            ->with($this->identicalTo($origin))
            ->will($this->returnValue([]));
        $emailUserRepo->expects($this->once())
            ->method('getEmailUsersByFolderAndMessageIds')
            ->willReturn([]);

        $newEmailUserEntity = new EmailUser();
        $newEmailEntity = new EmailEntity();
        $newEmailUserEntity = new EmailUser();
        $newEmailUserEntity->setEmail($newEmailEntity);
        $newEwsEmailEntity = new EwsEmail();
        $newEwsEmailEntity
            ->setEmail($newEmailEntity)
            ->setEwsId($email2->getId()->getId())
            ->setEwsChangeKey($email2->getId()->getChangeKey())
            ->setEwsFolder($ewsFolder);

        $this->emailEntityBuilder->expects($this->once())
            ->method('removeEmails');
        $this->emailEntityBuilder->expects($this->once())
            ->method('emailUser')
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
            ->will($this->returnValue($newEmailUserEntity));
        $this->em->expects($this->once())
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
        $batch->expects($this->once())
            ->method('getChanges')
            ->will($this->returnValue([]));

        $newEmailEntity->setMessageId('message_id');
        $new2EmailEntity = new EmailEntity();
        ReflectionUtil::setId($new2EmailEntity, '123');
        
        $emailUser = new EmailUser();
        $emailUser->addFolder($folder);
        $newEmailEntity->addEmailUser($emailUser);

        $this->em->expects($this->once())
            ->method('flush');

        ReflectionUtil::callProtectedMethod(
            $processor,
            'saveEmails',
            [
                [$email1, $email2],
                $folderInfo,
                $owner
            ]
        );

        $this->assertEquals($email2->getMessageId(), $newEmailEntity->getMessageId());
        $this->assertEquals($email2->getXMessageId(), $newEmailEntity->getXMessageId());
        $this->assertEquals($email2->getXThreadId(), $newEmailEntity->getXThreadId());
        $this->assertEquals($email2->isSeen(), $newEmailUserEntity->isSeen());
        $this->assertEquals($email2->getRefs(), implode('', $newEmailEntity->getRefs()));
        $this->assertEquals($folder, $newEmailUserEntity->getFolders()->first());
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
        $processor = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Sync\EwsEmailSynchronizationProcessor')
            ->setConstructorArgs(
                [
                    $this->em,
                    $this->emailEntityBuilder,
                    $this->knownEmailAddressChecker,
                    $this->manager
                ]
            )
            ->setMethods($methods)
            ->getMock();
        $processor->setLogger($this->logger);

        return $processor;
    }
}
