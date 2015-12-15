<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

class SecurityFacadeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SecurityFacade
     */
    private $facade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $securityContext;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $annotationProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $objectIdentityFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $classResolver;

    protected function setUp()
    {
        $this->securityContext = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->annotationProvider =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider')
                ->disableOriginalConstructor()
                ->getMock();
        $this->objectIdentityFactory =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory')
                ->disableOriginalConstructor()
                ->getMock();
        $this->logger = $this->getMock('Psr\Log\LoggerInterface');

        $this->classResolver =
            $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
                ->disableOriginalConstructor()
                ->getMock();

        $this->facade = new SecurityFacade(
            $this->securityContext,
            $this->annotationProvider,
            $this->objectIdentityFactory,
            $this->classResolver,
            $this->logger
        );
    }

    public function testIsClassMethodGrantedDenyingByMethodAcl()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Annotation\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $annotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('method_annotation'));
        $annotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION'));

        $this->annotationProvider->expects($this->once())
            ->method('findAnnotation')
            ->with('TestClass', 'TestMethod')
            ->will($this->returnValue($annotation));
        $this->logger->expects($this->once())
            ->method('debug');
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->will($this->returnValue($oid));
        $this->securityContext->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($oid))
            ->will($this->returnValue(false));

        $result = $this->facade->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertFalse($result);
    }

    public function testIsClassMethodGrantedGrantingByMethodAclNoClassAcl()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Annotation\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $annotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('method_annotation'));
        $annotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION'));
        $annotation->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->will($this->returnValue(false));

        $this->annotationProvider->expects($this->at(0))
            ->method('findAnnotation')
            ->with('TestClass', 'TestMethod')
            ->will($this->returnValue($annotation));
        $this->annotationProvider->expects($this->at(1))
            ->method('findAnnotation')
            ->with('TestClass')
            ->will($this->returnValue(null));
        $this->logger->expects($this->once())
            ->method('debug');
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->will($this->returnValue($oid));
        $this->securityContext->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($oid))
            ->will($this->returnValue(true));

        $result = $this->facade->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertTrue($result);
    }

    public function testIsClassMethodGrantedGrantingByMethodAclWithIgnoreClassAcl()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Annotation\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $annotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('method_annotation'));
        $annotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION'));
        $annotation->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->will($this->returnValue(true));

        $this->annotationProvider->expects($this->once())
            ->method('findAnnotation')
            ->with('TestClass', 'TestMethod')
            ->will($this->returnValue($annotation));
        $this->logger->expects($this->once())
            ->method('debug');
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->will($this->returnValue($oid));
        $this->securityContext->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($oid))
            ->will($this->returnValue(true));

        $result = $this->facade->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertTrue($result);
    }

    public function testIsClassMethodGrantedDenyingByClassAcl()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Annotation\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $annotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('method_annotation'));
        $annotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION'));
        $annotation->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->will($this->returnValue(false));

        $classOid = new ObjectIdentity('2', 'TestType');
        $classAnnotation = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Annotation\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $classAnnotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('class_annotation'));
        $classAnnotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION_CLASS'));

        $this->annotationProvider->expects($this->at(0))
            ->method('findAnnotation')
            ->with('TestClass', 'TestMethod')
            ->will($this->returnValue($annotation));
        $this->annotationProvider->expects($this->at(1))
            ->method('findAnnotation')
            ->with('TestClass')
            ->will($this->returnValue($classAnnotation));
        $this->logger->expects($this->exactly(2))
            ->method('debug');
        $this->objectIdentityFactory->expects($this->at(0))
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->will($this->returnValue($oid));
        $this->objectIdentityFactory->expects($this->at(1))
            ->method('get')
            ->with($this->identicalTo($classAnnotation))
            ->will($this->returnValue($classOid));
        $this->securityContext->expects($this->at(0))
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($oid))
            ->will($this->returnValue(true));
        $this->securityContext->expects($this->at(1))
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION_CLASS'), $this->identicalTo($classOid))
            ->will($this->returnValue(false));

        $result = $this->facade->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertFalse($result);
    }

    public function testIsClassMethodGrantedGrantingByMethodAndClassAcls()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Annotation\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $annotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('method_annotation'));
        $annotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION'));
        $annotation->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->will($this->returnValue(false));

        $classOid = new ObjectIdentity('2', 'TestType');
        $classAnnotation = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Annotation\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $classAnnotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('class_annotation'));
        $classAnnotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION_CLASS'));

        $this->annotationProvider->expects($this->at(0))
            ->method('findAnnotation')
            ->with('TestClass', 'TestMethod')
            ->will($this->returnValue($annotation));
        $this->annotationProvider->expects($this->at(1))
            ->method('findAnnotation')
            ->with('TestClass')
            ->will($this->returnValue($classAnnotation));
        $this->logger->expects($this->exactly(2))
            ->method('debug');
        $this->objectIdentityFactory->expects($this->at(0))
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->will($this->returnValue($oid));
        $this->objectIdentityFactory->expects($this->at(1))
            ->method('get')
            ->with($this->identicalTo($classAnnotation))
            ->will($this->returnValue($classOid));
        $this->securityContext->expects($this->at(0))
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($oid))
            ->will($this->returnValue(true));
        $this->securityContext->expects($this->at(1))
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION_CLASS'), $this->identicalTo($classOid))
            ->will($this->returnValue(true));

        $result = $this->facade->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertTrue($result);
    }

    public function testIsGrantedWithAclAnnotationIdAndNoObject()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Annotation\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $annotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('method_annotation'));
        $annotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION'));
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->will($this->returnValue($oid));
        $this->annotationProvider->expects($this->at(0))
            ->method('findAnnotationById')
            ->with('TestAnnotation')
            ->will($this->returnValue($annotation));
        $this->logger->expects($this->once())
            ->method('debug');
        $this->securityContext->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($oid))
            ->will($this->returnValue(true));

        $result = $this->facade->isGranted('TestAnnotation');
        $this->assertTrue($result);
    }

    public function testIsGrantedWithAclAnnotationIdAndWithObject()
    {
        $obj = new \stdClass();
        $annotation = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Annotation\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $annotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('method_annotation'));
        $annotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION'));
        $this->objectIdentityFactory->expects($this->never())
            ->method('get');
        $this->annotationProvider->expects($this->at(0))
            ->method('findAnnotationById')
            ->with('TestAnnotation')
            ->will($this->returnValue($annotation));
        $this->logger->expects($this->once())
            ->method('debug');
        $this->securityContext->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($obj))
            ->will($this->returnValue(true));

        $result = $this->facade->isGranted('TestAnnotation', $obj);
        $this->assertTrue($result);
    }

    public function testIsGrantedWithRoleName()
    {
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('TestRole')
            ->will($this->returnValue(null));
        $this->securityContext->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('TestRole'), $this->equalTo(null))
            ->will($this->returnValue(true));

        $result = $this->facade->isGranted('TestRole');
        $this->assertTrue($result);
    }

    public function testIsGrantedWithRoleNames()
    {
        $this->annotationProvider->expects($this->never())
            ->method('findAnnotationById');
        $this->securityContext->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo(array('TestRole1', 'TestRole2')), $this->equalTo(null))
            ->will($this->returnValue(true));

        $result = $this->facade->isGranted(array('TestRole1', 'TestRole2'));
        $this->assertTrue($result);
    }

    public function testIsGrantedWithString()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $obj = 'Entity:SomeClass';
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('PERMISSION')
            ->will($this->returnValue(false));
        $this->objectIdentityFactory->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo($obj))
            ->will($this->returnValue($oid));
        $this->securityContext->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('PERMISSION'), $oid)
            ->will($this->returnValue(true));

        $result = $this->facade->isGranted('PERMISSION', $obj);
        $this->assertTrue($result);
    }

    public function testIsGrantedWithCombinedString()
    {
        $this->securityContext->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('VIEW'), 'entity:AcmeDemoBundle:Test')
            ->will($this->returnValue(true));

        $result = $this->facade->isGranted('VIEW;entity:AcmeDemoBundle:Test');
        $this->assertTrue($result);
    }

    public function testIsGrantedWithObject()
    {
        $obj = new \stdClass();
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('PERMISSION')
            ->will($this->returnValue(false));
        $this->securityContext->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('PERMISSION'), $this->equalTo($obj))
            ->will($this->returnValue(true));

        $result = $this->facade->isGranted('PERMISSION', $obj);
        $this->assertTrue($result);
    }

    public function testGetRequestAcl()
    {
        $request = new Request();
        $request->attributes->add(['_controller' => 'OroTestBundle::testAction']);
        $acl = new Acl(['id' => 1, 'class' => 'OroTestBundle:Test', 'type' => 'entity']);
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotation')
            ->with('OroTestBundle', 'testAction')
            ->will($this->returnValue($acl));
        $this->classResolver->expects($this->once())
            ->method('isEntity')
            ->with('OroTestBundle:Test')
            ->will($this->returnValue(true));
        $this->classResolver->expects($this->once())
            ->method('getEntityClass')
            ->with('OroTestBundle:Test')
            ->will($this->returnValue('Oro\Bundle\TestBundle\Entity\Test'));

        $returnAcl = $this->facade->getRequestAcl($request, true);
        $this->assertNotNull($returnAcl);
        $this->assertEquals('Oro\Bundle\TestBundle\Entity\Test', $acl->getClass());
    }

    public function testGeWrongRequestAcl()
    {
        $request = new Request();
        $request->attributes->add(['_controller' => 'wrong controller']);
        $this->annotationProvider->expects($this->never())
            ->method('findAnnotation');
        $this->classResolver->expects($this->never())
            ->method('isEntity');
        $this->classResolver->expects($this->never())
            ->method('getEntityClass');

        $this->assertNull($this->facade->getRequestAcl($request, true));
    }

    /**
     * @dataProvider isRequestObjectIsGrantedProvider
     */
    public function testIsRequestObjectIsGranted($requestController, $isGrant, $result)
    {
        $object = new \stdClass();
        $request = new Request();
        $request->attributes->add(['_controller' => $requestController]);
        $acl = new Acl(
            ['id' => 1, 'class' => 'OroTestBundle:Test', 'type' => 'entity', 'permission' => 'TEST_PERMISSION']
        );
        $this->annotationProvider->expects($this->any())
            ->method('findAnnotation')
            ->will($this->returnValue($acl));
        $this->classResolver->expects($this->any())
            ->method('isEntity')
            ->with('OroTestBundle:Test')
            ->will($this->returnValue(true));
        $this->classResolver->expects($this->any())
            ->method('getEntityClass')
            ->with('OroTestBundle:Test')
            ->will($this->returnValue('\stdClass'));
        $this->securityContext->expects($this->any())
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($object))
            ->will($this->returnValue($isGrant));

        $this->assertEquals($result, $this->facade->isRequestObjectIsGranted($request, $object));
    }

    public function isRequestObjectIsGrantedProvider()
    {
        return [
            ['testBundle::testAction', true, 1],
            ['testBundle::testAction', false, -1],
            ['wrong_action', true, 0]
        ];
    }
}
