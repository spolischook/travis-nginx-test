<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Twig;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;

use OroPro\Bundle\SecurityBundle\Twig\OroProSecurityShareExtension;

class OroProSecurityShareExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroProSecurityShareExtension
     */
    protected $twigExtension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $aclCache;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $nameFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclCache = $this->getMockBuilder('Symfony\Component\Security\Acl\Model\AclCacheInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twigExtension = new OroProSecurityShareExtension(
            $this->manager,
            $this->aclCache,
            $this->securityFacade,
            $this->nameFormatter,
            $this->translator,
            $this->configProvider
        );
    }

    protected function tearDown()
    {
        unset($this->securityFacade);
        unset($this->twigExtension);
    }

    public function testGetName()
    {
        $this->assertEquals('oropro_security_share_extension', $this->twigExtension->getName());
    }

    public function testGetFunctions()
    {
        $expectedFunctions = array(
            'format_share_scopes' => 'formatShareScopes',
            'oropro_share_count' => 'getShareCount',
            'oropro_shared_with_name' => 'getSharedWithName',
        );

        $actualFunctions = $this->twigExtension->getFunctions();
        $this->assertSameSize($expectedFunctions, $actualFunctions);

        foreach ($expectedFunctions as $twigFunction => $internalMethod) {
            $this->assertArrayHasKey($twigFunction, $actualFunctions);
            $this->assertInstanceOf('\Twig_Function_Method', $actualFunctions[$twigFunction]);
            $this->assertAttributeEquals($internalMethod, 'method', $actualFunctions[$twigFunction]);
        }
    }

    public function testFormatShareScopesWithEmptyValue()
    {
        $this->translator->expects($this->at(0))
            ->method('trans')
            ->with('oro.security.share_scopes.not_available')
            ->willReturn('N/A');
        $this->assertEquals('N/A', $this->twigExtension->formatShareScopes(null));
    }

    /**
     * @expectedException \LogicException
     */
    public function testFormatShareScopesWithLoginException()
    {
        $this->twigExtension->formatShareScopes(new \stdClass());
    }

    public function testFormatShareScopesWithString()
    {
        $this->translator->expects($this->at(0))
            ->method('trans')
            ->with('oro.security.share_scopes.business_unit.short_label')
            ->willReturn('business_unit_short_label_translated');

        $this->translator->expects($this->at(1))
            ->method('trans')
            ->with('oro.security.share_scopes.user.short_label')
            ->willReturn('user_short_label_translated');

        $this->assertEquals(
            'business_unit_short_label_translated, user_short_label_translated',
            $this->twigExtension->formatShareScopes(json_encode(['business_unit', 'user']), 'short_label')
        );
    }

    public function testFormatShareScopesWithArray()
    {
        $this->translator->expects($this->at(0))
            ->method('trans')
            ->with('oro.security.share_scopes.business_unit.label')
            ->willReturn('business_unit_label_translated');

        $this->translator->expects($this->at(1))
            ->method('trans')
            ->with('oro.security.share_scopes.user.label')
            ->willReturn('user_label_translated');

        $this->assertEquals(
            'business_unit_label_translated, user_label_translated',
            $this->twigExtension->formatShareScopes(['business_unit', 'user'])
        );
    }

    public function testFormatShareScopesWithEntityConfigModel()
    {
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('share_scopes')
            ->willReturn(['business_unit', 'user']);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->translator->expects($this->at(0))
            ->method('trans')
            ->with('oro.security.share_scopes.business_unit.label')
            ->willReturn('business_unit_label_translated');

        $this->translator->expects($this->at(1))
            ->method('trans')
            ->with('oro.security.share_scopes.user.label')
            ->willReturn('user_label_translated');

        $this->assertEquals(
            'business_unit_label_translated, user_label_translated',
            $this->twigExtension->formatShareScopes(new EntityConfigModel())
        );
    }

    public function testGetShareCountNotZero()
    {
        $object = $this->getMockBuilder('Symfony\Component\Security\Acl\Model\DomainObjectInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $object->expects($this->any())
            ->method('getObjectIdentifier')
            ->will($this->returnValue(1));

        $aces = [1];
        $acl = $this->getMockBuilder('Symfony\Component\Security\Acl\Domain\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $acl->expects($this->any())
            ->method('getObjectAces')
            ->will($this->returnValue($aces));

        $this->aclCache->expects($this->once())
            ->method('getFromCacheByIdentity')
            ->will($this->returnValue($acl));


        $this->assertEquals(1, $this->twigExtension->getShareCount($object));
    }

    public function testGetShareCountZero()
    {
        $object = $this->getMockBuilder('Symfony\Component\Security\Acl\Model\DomainObjectInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $object->expects($this->any())
            ->method('getObjectIdentifier')
            ->will($this->returnValue(1));

        $aces = [];
        $acl = $this->getMockBuilder('Symfony\Component\Security\Acl\Domain\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $acl->expects($this->any())
            ->method('getObjectAces')
            ->will($this->returnValue($aces));

        $this->aclCache->expects($this->once())
            ->method('getFromCacheByIdentity')
            ->will($this->returnValue($acl));


        $this->assertEquals(0, $this->twigExtension->getShareCount($object));
    }

    public function testGetSharedWithName()
    {
        $object = $this->getMockBuilder('Symfony\Component\Security\Acl\Model\DomainObjectInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $object->expects($this->any())
            ->method('getObjectIdentifier')
            ->will($this->returnValue(1));

        $user = new User(1);
        $user->setUsername('TestUser');
        $sid = new UserSecurityIdentity('TestUser', get_class($user));

        $ace = $this->getMockBuilder('Symfony\Component\Security\Acl\Domain\Entry')
            ->disableOriginalConstructor()
            ->getMock();
        $ace->expects($this->any())
            ->method('getSecurityIdentity')
            ->will($this->returnValue($sid));

        $aces = [$ace];
        $acl = $this->getMockBuilder('Symfony\Component\Security\Acl\Domain\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $acl->expects($this->any())
            ->method('getObjectAces')
            ->will($this->returnValue($aces));

        $this->aclCache->expects($this->once())
            ->method('getFromCacheByIdentity')
            ->will($this->returnValue($acl));

        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())
            ->method('findOneBy')
            ->will($this->returnValue($user));

        $this->manager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));
        $this->nameFormatter->expects($this->any())
            ->method('format')
            ->will($this->returnValue($user->getUsername()));

        $this->assertEquals($user->getUsername(), $this->twigExtension->getSharedWithName($object));
    }
}
