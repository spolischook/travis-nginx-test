<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\UserEmailOwner;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;

use OroPro\Bundle\EwsBundle\Entity\EwsEmailFolder;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;
use OroPro\Bundle\EwsBundle\Provider\EwsEmailBodyLoader;

use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;

use Oro\Bundle\UserBundle\Entity\User;

class EwsEmailBodyLoaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $connector;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EwsEmailBodyLoader */
    protected $ewsEmailBodyLoader;

    protected function setUp()
    {
        $this->connector = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Connector\EwsConnector')
            ->disableOriginalConstructor()
            ->setMethods(['getItem', 'setTargetUser'])
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSupports()
    {
        $this->ewsEmailBodyLoader = new EwsEmailBodyLoader($this->connector);

        $this->assertEquals(true, $this->ewsEmailBodyLoader->supports(new EwsEmailOrigin()));
        $this->assertEquals(false, $this->ewsEmailBodyLoader->supports(new ImapEmailOrigin()));
    }

    public function testloadEmailBody()
    {
        $ewsEmailManager = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Manager\EwsEmailManager')
            ->setMethods(['findEmail'])
            ->setConstructorArgs([$this->connector])
            ->getMock();
        $ewsEmailManager->expects($this->once())
            ->method('findEmail')
            ->will($this->returnValue($this->getTestDTOEmail($ewsEmailManager)));

        $this->ewsEmailBodyLoader = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Provider\EwsEmailBodyLoader')
            ->setConstructorArgs([$this->connector])
            ->setMethods(['getManager'])
            ->getMock();
        $this->ewsEmailBodyLoader->expects($this->once())
            ->method('getManager')
            ->withAnyParameters()
            ->will($this->returnValue($ewsEmailManager));

        $ewsFolder = new EwsEmailFolder();

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroProEwsBundle:EwsEmail')
            ->will($this->returnValue($this->getDoctrineMocks()));

        $emailUser = $this->getTestEmailUser($this->getTestEwsOrigin());
        $folder = $emailUser->getFolder();
        $ewsFolder->setFolder($folder);

        $this->ewsEmailBodyLoader->loadEmailBody($folder, $emailUser->getEmail(), $this->em);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The origin for "test subject" email must be instance of EwsEmailOrigin.
     */
    public function testloadEmailBodyException()
    {
        $this->em->expects($this->any())
            ->method('getRepository')
            ->with('OroProEwsBundle:EwsEmail')
            ->will($this->returnValue($this->getDoctrineMocks()));

        $this->ewsEmailBodyLoader = new EwsEmailBodyLoader($this->connector);

        $folder = new EmailFolder();
        $folder->setType(FolderType::SENT);
        $origin = new InternalEmailOrigin();
        $origin->addFolder($folder);
        $emailUser = $this->getTestEmailUser($origin);

        $this->ewsEmailBodyLoader->loadEmailBody($folder, $emailUser->getEmail(), $this->em);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The origin for "test subject" email must be instance of EwsEmailOrigin.
     */
    public function testGetManagerException()
    {
        $this->ewsEmailBodyLoader = new EwsEmailBodyLoader($this->connector);

        $emailUser = $this->getTestEmailUser($this->getTestInternalOrigin());
        $folder = $emailUser->getFolder();

        $this->ewsEmailBodyLoader->loadEmailBody($folder, $emailUser->getEmail(), $this->em);
    }

    protected function getDoctrineMocks()
    {
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setConstructorArgs([$this->em])
            ->setMethods(['setHydrationMode', 'getSingleResult'])
            ->getMockForAbstractClass();
        $query->expects($this->any())
            ->method('setHydrationMode')
            ->with(Query::HYDRATE_ARRAY)
            ->will($this->returnSelf());
        $query->expects($this->any())
            ->method('getSingleResult')
            ->will($this->returnValue(['ewsId' => '1e2a3791ade757a26a490f46968f9e', 'ewsChangeKey' => 'e3791a4906f']));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setConstructorArgs([$this->em, new ArrayCollection()])
            ->setMethods([])
            ->getMock();
        $queryBuilder->expects($this->any())
            ->method('select')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->any())
            ->method('innerJoin')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->any())
            ->method('where')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->any())
            ->method('setParameter')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setConstructorArgs([$this->em, new ClassMetadata('OroProEwsBundle:EwsEmail')])
            ->getMock();
        $repo->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        return $repo;
    }

    protected function getTestUser()
    {
        $user = new User();
        $user->setId(1);
        $user->setEmail('test_user@test.com');
        $user->setSalt('1fqvkjskgry8s8cs400840c0ok8ggck');

        return $user;
    }

    protected function getTestInternalOrigin()
    {
        $outboxFolder = new EmailFolder();
        $outboxFolder
            ->setType(FolderType::SENT)
            ->setName(FolderType::SENT)
            ->setFullName(FolderType::SENT);

        $origin = new InternalEmailOrigin();
        $origin->addFolder($outboxFolder);

        return $origin;
    }

    protected function getTestEwsOrigin()
    {
        $outboxFolder = new EmailFolder();
        $outboxFolder
            ->setType(FolderType::SENT)
            ->setName(FolderType::SENT)
            ->setFullName(FolderType::SENT);

        $origin = new EwsEmailOrigin();
        $origin->addFolder($outboxFolder);
        $origin->setUserEmail('test_user@test.com');

        return $origin;
    }

    /**
     * @param EwsEmailOrigin|InternalEmailOrigin $origin
     *
*@return UserEmailOwner
     */
    protected function getTestEmailUser($origin)
    {
        $user = $this->getTestUser();
        $user->addEmailOrigin($origin);

        $fromEmailAddress = new EmailAddress();
        $fromEmailAddress->setOwner($user);
        $fromEmailAddress->setEmail('test_user@test.com');

        $body = new EmailBody();
        $body
            ->setBodyContent('email body content')
            ->setBodyIsText(true);

        $email = new Email();
        $email
            ->setSubject('test subject')
            ->setFromName('from_email@test.com')
            ->setFromEmailAddress($fromEmailAddress)
            ->setEmailBody($body)
            ->setSentAt(new \DateTime('now', new \DateTimeZone('UTC')))
            ->setInternalDate(new \DateTime('now', new \DateTimeZone('UTC')))
            ->setImportance(Email::NORMAL_IMPORTANCE);

        $emailUser = new UserEmailOwner();
        $emailUser
            ->setEmail($email)
            ->setReceivedAt(new \DateTime('now', new \DateTimeZone('UTC')))
            ->setFolder($origin->getFolder(FolderType::SENT));

        return $emailUser;
    }

    protected function getTestDTOEmail(EwsEmailManager $ewsEmailManager)
    {
        $email = new \OroPro\Bundle\EwsBundle\Manager\DTO\Email($ewsEmailManager);
        $email
            ->setId(new \OroPro\Bundle\EwsBundle\Manager\DTO\ItemId('aaa', 'bbb'))
            ->setSubject('test subject');

        $body = new \OroPro\Bundle\EwsBundle\Manager\DTO\EmailBody();
        $body
            ->setContent('test content')
            ->setBodyIsText(false);

        $email->setBody($body);

        return $email;
    }
}
