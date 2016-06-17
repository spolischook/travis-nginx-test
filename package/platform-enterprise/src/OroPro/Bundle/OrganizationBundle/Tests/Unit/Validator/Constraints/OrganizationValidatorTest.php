<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Validator\Constrains;

use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\User;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\GlobalOrganization;
use OroPro\Bundle\OrganizationBundle\Validator\Constraints\Organization;
use OroPro\Bundle\OrganizationBundle\Validator\Constraints\OrganizationValidator;
use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipProMetadata;

class OrganizationValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrganizationValidator */
    protected $validator;

    /** @var Organization */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $ownershipMetadataProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityOwnerAccessor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var Entity */
    protected $testEntity;

    /** @var User */
    protected $currentUser;

    /** @var GlobalOrganization */
    protected $currentOrg;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->ownershipMetadataProvider = $this
            ->getMockBuilder('OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityOwnerAccessor = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new OrganizationValidator(
            $this->registry,
            $this->ownershipMetadataProvider,
            $this->entityOwnerAccessor,
            $this->securityFacade
        );

        $this->constraint = new Organization();
        $this->testEntity = new Entity();
    }

    public function testValidateForNonSupportedEntity()
    {
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(null);

        $this->ownershipMetadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->validator->validate($this->testEntity, $this->constraint);
    }

    public function testValidateForNonACLProtectedEntity()
    {
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);

        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);
        $this->entityOwnerAccessor->expects($this->never())
            ->method('getOwner');

        $this->ownershipMetadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->validator->validate($this->testEntity, $this->constraint);
    }

    /**
     * @dataProvider validationSuccessProvider
     */
    public function testValidateSuccess($owner, $organization, $ownerType, $isGlobal)
    {
        $ownershipMetadata = new OwnershipMetadata($ownerType, 'owner', 'owner', 'organization', 'organization');

        $this->currentOrg = new GlobalOrganization();
        $this->currentOrg->setId(11);
        $this->currentOrg->setEnabled(true);
        $this->currentOrg->setIsGlobal($isGlobal);

        $this->currentUser = new User();
        $this->currentUser->setId(11);
        $this->currentUser->setOrganization($this->currentOrg);
        $this->currentUser->addOrganization($organization);

        $this->testEntity->setOwner($owner);
        $this->testEntity->setOrganization($organization);

        list($context, $violation) = $this->prepareValidator($ownershipMetadata);

        $context->expects($this->never())->method('buildViolation');
        $violation->expects($this->never())->method('setParameter');

        $this->validator->initialize($context);
        $this->validator->validate($this->testEntity, $this->constraint);
    }

    public function validationSuccessProvider()
    {
        $organization1 = new \Oro\Bundle\OrganizationBundle\Entity\Organization();
        $organization1->setId(11);
        $organization1->setEnabled(true);

        $organization2 = new \Oro\Bundle\OrganizationBundle\Entity\Organization();
        $organization2->setId(22);
        $organization2->setEnabled(true);

        $userOwner = new User();
        $userOwner->setId(11);
        $userOwner->addOrganization($organization1);
        $userOwner->addOrganization($organization2);

        $businessUnit = new BusinessUnit();
        $businessUnit->setId(33);
        $businessUnit->setOrganization($organization1);

        return [
            'owner, regular org, type USER' => [$userOwner, $organization1, 'USER', false],
            'owner, regular org, type Business Unit' => [$businessUnit, $organization1, 'BUSINESS_UNIT', false],
            'owner, regular org, type Organization' => [$organization1, $organization1, 'ORGANIZATION', false],
            'owner, global org, type USER' => [$userOwner, $organization1, 'USER', true],
            'owner, global org, type Business Unit' => [$businessUnit, $organization1, 'BUSINESS_UNIT', true],
            'owner, global org, type Organization' => [$organization1, $organization1, 'ORGANIZATION', true],
        ];
    }

    /**
     * @dataProvider validationNotSuccessProvider
     */
    public function testValidateNotSuccess($owner, $organization, $ownerType, $isGlobal)
    {
        $ownershipMetadata = new OwnershipMetadata($ownerType, 'owner', 'owner', 'organization', 'organization');

        $this->currentOrg = new GlobalOrganization();
        $this->currentOrg->setId(33);
        $this->currentOrg->setEnabled(true);
        $this->currentOrg->setIsGlobal($isGlobal);

        $this->currentUser = new User();
        $this->currentUser->setId(11);
        $this->currentUser->setOrganization($this->currentOrg);

        $this->testEntity->setOwner($owner);
        $this->testEntity->setOrganization($organization);

        list($context, $violation) = $this->prepareValidator($ownershipMetadata);

        $violation->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        $context->expects($this->any())
            ->method('buildViolation')
            ->with('You have no access to set this value as {{ organization }}.')
            ->willReturn($violation);
        $violation->expects($this->any())
            ->method('atPath')
            ->with('organization')
            ->willReturnSelf();

        $this->validator->initialize($context);
        $this->validator->validate($this->testEntity, $this->constraint);
    }

    public function validationNotSuccessProvider()
    {
        $organization1 = new \Oro\Bundle\OrganizationBundle\Entity\Organization();
        $organization1->setId(11);
        $organization1->setEnabled(true);

        $organization2 = new \Oro\Bundle\OrganizationBundle\Entity\Organization();
        $organization2->setId(22);
        $organization2->setEnabled(true);

        $organization3 = new \Oro\Bundle\OrganizationBundle\Entity\Organization();
        $organization3->setId(33);
        $organization3->setEnabled(true);

        $userOwner1 = new User();
        $userOwner1->setId(11);
        $userOwner1->addOrganization($organization1);
        $userOwner1->addOrganization($organization2);

        $businessUnit = new BusinessUnit();
        $businessUnit->setId(33);
        $businessUnit->setOrganization($organization1);

        return [
            'owner, regular org, type USER' => [$userOwner1, $organization1, 'USER', false],
            'owner, global org, type USER' => [$userOwner1, $organization1, 'USER', true],
            'owner, regular org3, type USER' => [$userOwner1, $organization3, 'USER', false],
            'owner, no org, type USER' => [$userOwner1, false, 'USER', false],

            'owner, regular org, type Business Unit' => [$businessUnit, $organization1, 'BUSINESS_UNIT', false],
            'owner, global org, type Business Unit' => [$businessUnit, $organization1, 'BUSINESS_UNIT', true],
            'owner, regular org3, type Business Unit' => [$businessUnit, $organization3, 'BUSINESS_UNIT', false],
            'owner, no org, type Business Unit' => [$businessUnit, false, 'BUSINESS_UNIT', false],

            'owner, regular org, type Organization' => [$organization1, $organization1, 'ORGANIZATION', false],
            'owner, global org, type Organization' => [$organization1, $organization1, 'ORGANIZATION', true]
        ];
    }

    protected function prepareValidator($ownershipMetadata)
    {
        $om = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()->getMock();
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn($om);
        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getMetadata')
            ->with('Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn($ownershipMetadata);

        $this->entityOwnerAccessor->expects($this->any())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());
        $this->entityOwnerAccessor->expects($this->any())
            ->method('getOrganization')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOrganization());

        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($this->currentUser);
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUserId')
            ->willReturn($this->currentUser->getId());
        $this->securityFacade->expects($this->any())
            ->method('getOrganization')
            ->willReturn($this->currentOrg);

        $context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
        $violation = $this->getMockBuilder('Symfony\Component\Validator\Violation\ConstraintViolationBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        return [$context, $violation];
    }
}
