<?php

namespace OroCRMPro\Bundle\LDAPBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Component\Config\Common\ConfigObject;

use OroCRMPro\Bundle\LDAPBundle\EventListener\UserChangeListener;
use OroCRMPro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingUser;

class UserChangeListenerTest extends \PHPUnit_Framework_TestCase
{
    private $uow;
    private $em;
    private $registry;
    private $channelRepository;
    private $syncScheduler;
    private $userChangeListener;
    /** @var ConfigObject */
    private $syncSettings;
    /** @var ConfigObject */
    private $mappingSettings;

    private function setUpChannel($id, $name)
    {
        $channel = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->disableOriginalConstructor()
            ->getMock();

        $channel->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $channel->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        $this->syncSettings = ConfigObject::create(
            [
                'isTwoWaySyncEnabled' => true,
            ]
        );

        $this->mappingSettings = ConfigObject::create(
            [
                'userMapping'      => [
                    'username' => 'sn',
                    'password' => null,
                    'salt'     => null,
                ],
                'exportUserBaseDn' => 'cn=base-dn',
            ]
        );

        $channel->expects($this->any())
            ->method('getSynchronizationSettings')
            ->will($this->returnValue($this->syncSettings));

        $channel->expects($this->any())
            ->method('getMappingSettings')
            ->will($this->returnValue($this->mappingSettings));

        return $channel;
    }

    public function setUp()
    {
        $this->uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->channelRepository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with($this->equalTo('OroIntegrationBundle:Channel'))
            ->will($this->returnValue($this->channelRepository));
        $this->channelRepository->expects($this->any())
            ->method('findBy')
            ->will(
                $this->returnValue(
                    [
                        1  => $this->setUpChannel(1, 'First LDAP'),
                        40 => $this->setUpChannel(40, 'Second LDAP'),
                    ]
                )
            );
        $registryLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $registryLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($this->registry));
        $this->syncScheduler = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Manager\SyncScheduler')
            ->disableOriginalConstructor()
            ->getMock();
        $schedulerLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $schedulerLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($this->syncScheduler));

        $this->userChangeListener = new UserChangeListener($registryLink, $schedulerLink);
    }

    public function testUserShouldNotBeUpdatedIfHeIsNotMappedToAnyChannel()
    {
        $user = new TestingUser();

        $user->setLdapDistinguishedNames([]);

        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([$user]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $this->uow->expects($this->once())
            ->method('getEntityChangeSet');
        $this->syncScheduler->expects($this->never())
            ->method('schedule');

        $this->userChangeListener->onFlush(new OnFlushEventArgs($this->em));
        $this->userChangeListener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testUserShouldBeAlwaysInserted()
    {
        $user = new TestingUser();
        $user->setId(1);

        $user->setLdapDistinguishedNames(
            [
                1 => 'some_dn',
            ]
        );

        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$user]));
        $this->syncScheduler->expects($this->exactly(2))
            ->method('schedule');

        $this->userChangeListener->onFlush(new OnFlushEventArgs($this->em));
        $this->userChangeListener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testUserShouldNotBeUpdatedIfHeHasChangedOtherThanSynchronizedFields()
    {
        $changeSet = [
            'password' => ['oldPass', 'newPass'],
            'salt'     => ['oldSalt', 'newSalt'],
        ];

        $user = new TestingUser();
        $user->setId(1);

        $user->setLdapDistinguishedNames(
            [
                1 => 'some_dn',
            ]
        );

        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([$user]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $this->uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($user)
            ->will($this->returnValue($changeSet));
        $this->syncScheduler->expects($this->never())
            ->method('schedule');
        $this->userChangeListener->onFlush(new OnFlushEventArgs($this->em));
        $this->userChangeListener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testUserShouldBeUpdatedIfHeHasChangedSynchronizedFields()
    {
        $changeSet = [
            'username' => ['oldUsername', 'newUsername'],
            'salt'     => ['oldSalt', 'newSalt'],
        ];

        $user = new TestingUser();
        $user->setId(1);
        $user2 = new TestingUser();
        $user2->setId(2);

        $user->setLdapDistinguishedNames(
            [
                1 => 'some_dn',
            ]
        );
        $user2->setLdapDistinguishedNames(
            [
                1 => 'some_dn',
            ]
        );

        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([$user, $user2]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $this->uow->expects($this->any())
            ->method('getEntityChangeSet')
            ->will($this->returnValue($changeSet));
        $this->syncScheduler->expects($this->once())
            ->method('schedule')
            ->with(
                $this->anything(),
                $this->equalTo('user'),
                $this->equalTo(
                    [
                        'id' => [$user->getId(), $user2->getId()],
                    ]
                )
            );

        $this->userChangeListener->onFlush(new OnFlushEventArgs($this->em));
        $this->userChangeListener->postFlush(new PostFlushEventArgs($this->em));
    }
}
