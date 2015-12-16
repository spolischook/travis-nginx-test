<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Unit\Entity\AbstractUserTest;

use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Traits\AddressEntityTestTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AccountUserTest extends AbstractUserTest
{
    use AddressEntityTestTrait;

    /**
     * @return AccountUser
     */
    public function getUser()
    {
        return new AccountUser();
    }

    /**
     * @return AccountUserAddress
     */
    public function createAddressEntity()
    {
        return new AccountUserAddress();
    }

    /**
     * @return AccountUser
     */
    protected function createTestedEntity()
    {
        return $this->getUser();
    }

    public function testProperties()
    {
        $account = new Account();

        $user = $this->getUser();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('test@example.com');
        $user->setAccount($account);

        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('test@example.com', $user->getUsername());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals($account, $user->getAccount());
    }

    public function testCreateAccount()
    {
        $organization = new Organization();
        $organization->setName('test');

        $user = $this->getUser();
        $user->setOrganization($organization)
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setOwner(new User());
        $this->assertEmpty($user->getAccount());
        $address = new AccountAddress();
        $user->addAddress($address);
        $this->assertContains($address, $user->getAddresses());
        $backendUser = new User();
        $user->setOwner($backendUser);
        $this->assertEquals($user->getOwner(), $backendUser);

        // createAccount is triggered on prePersist event
        $user->createAccount();
        $account = $user->getAccount();
        $this->assertInstanceOf('OroB2B\Bundle\AccountBundle\Entity\Account', $account);
        $this->assertEquals($organization, $account->getOrganization());
        $this->assertEquals('John Doe', $account->getName());

        // new account created only if it not defined
        $user->setFirstName('Jane');
        $user->createAccount();
        $this->assertEquals('John Doe', $user->getAccount()->getName());

        //Creating an account with company name parameter instead of use first and last name
        $user->setAccount(null);
        $user->createAccount('test company');
        $this->assertEquals('test company', $user->getAccount()->getName());
    }

    public function testSerializing()
    {
        $user = $this->getUser();
        $data = $user->serialize();

        $this->assertNotEmpty($data);

        $user
            ->setPassword('new-pass')
            ->setConfirmationToken('token')
            ->setUsername('new-name');

        $user->unserialize($data);

        $this->assertEmpty($user->getPassword());
        $this->assertEmpty($user->getConfirmationToken());
        $this->assertEmpty($user->getUsername());
        $this->assertEquals('new-name', $user->getEmail());
    }

    /**
     * @return array
     */
    public function provider()
    {
        return [
            ['username', 'test'],
            ['email', 'test'],
            ['nameprefix', 'test'],
            ['firstname', 'test'],
            ['middlename', 'test'],
            ['lastname', 'test'],
            ['namesuffix', 'test'],
            ['birthday', new \DateTime()],
            ['password', 'test'],
            ['plainPassword', 'test'],
            ['confirmationToken', 'test'],
            ['passwordRequestedAt', new \DateTime()],
            ['passwordChangedAt', new \DateTime()],
            ['lastLogin', new \DateTime()],
            ['loginCount', 11],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['salt', md5('user')]
        ];
    }

    public function testPrePersist()
    {
        $user = $this->getUser();
        $user->prePersist();
        $this->assertInstanceOf('\DateTime', $user->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $user->getUpdatedAt());
        $this->assertEquals(0, $user->getLoginCount());
        $this->assertNotEmpty($user->getAccount());
    }

    public function testPreUpdateUnChanged()
    {
        $changeSet = [
            'lastLogin' => null,
            'loginCount' => null
        ];

        $user = $this->getUser();
        $updatedAt = new \DateTime('2015-01-01');
        $user->setUpdatedAt($updatedAt);

        /** @var \PHPUnit_Framework_MockObject_MockObject|PreUpdateEventArgs $event */
        $event = $this->getMockBuilder('Doctrine\ORM\Event\PreUpdateEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getEntityChangeSet')
            ->will($this->returnValue($changeSet));

        $user->preUpdate($event);
        $this->assertEquals($updatedAt, $user->getUpdatedAt());
    }

    public function testPreUpdateChanged()
    {
        $changeSet = ['lastname' => null];

        $user = $this->getUser();
        $updatedAt = new \DateTime('2015-01-01');
        $user->setUpdatedAt($updatedAt);

        /** @var \PHPUnit_Framework_MockObject_MockObject|PreUpdateEventArgs $event */
        $event = $this->getMockBuilder('Doctrine\ORM\Event\PreUpdateEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getEntityChangeSet')
            ->will($this->returnValue($changeSet));

        $user->preUpdate($event);
        $this->assertNotEquals($updatedAt, $user->getUpdatedAt());
    }

    public function testUnserialize()
    {
        $user = $this->getUser();
        $serialized = [
            'password',
            'salt',
            'username',
            true,
            false,
            'confirmation_token',
            10
        ];
        $user->unserialize(serialize($serialized));

        $this->assertEquals($serialized[0], $user->getPassword());
        $this->assertEquals($serialized[1], $user->getSalt());
        $this->assertEquals($serialized[2], $user->getUsername());
        $this->assertEquals($serialized[3], $user->isEnabled());
        $this->assertEquals($serialized[4], $user->isConfirmed());
        $this->assertEquals($serialized[5], $user->getConfirmationToken());
        $this->assertEquals($serialized[6], $user->getId());
    }

    public function testIsEnabledAndIsConfirmed()
    {
        $user = $this->getUser();

        $this->assertTrue($user->isEnabled());
        $this->assertTrue($user->isConfirmed());
        $this->assertTrue($user->isAccountNonExpired());
        $this->assertTrue($user->isAccountNonLocked());

        $user->setEnabled(false);

        $this->assertFalse($user->isEnabled());
        $this->assertFalse($user->isAccountNonLocked());

        $user->setEnabled(true);
        $user->setConfirmed(false);

        $this->assertFalse($user->isConfirmed());
        $this->assertFalse($user->isAccountNonLocked());
    }
}
