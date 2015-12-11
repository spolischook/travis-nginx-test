<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker;

use Doctrine\ORM\Query\AST\PathExpression;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\OwnershipMetadataProviderStub;

use OroPro\Bundle\SecurityBundle\ORM\Walker\OwnershipProConditionDataBuilder;
use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipProMetadata;
use OroPro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use OroPro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\OwnershipMetadataProProviderStub;

class OwnershipProConditionDataBuilderTest extends \PHPUnit_Framework_TestCase
{
    const BUSINESS_UNIT = 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\BusinessUnit';
    const ORGANIZATION = 'OroPro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization';
    const USER = 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User';
    const TEST_ENTITY = 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity';
    const GLOBAL_ORGANIZATION_ID = 'globOrg';

    /**
     * @var OwnershipConditionDataBuilder
     */
    private $builder;

    /** @var OwnershipMetadataProviderStub */
    private $metadataProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $securityContext;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $aclVoter;

    /** @var OwnerTree */
    private $tree;

    /** @var Organization */
    private $globalOrganization;

    protected function setUp()
    {
        $this->tree = new OwnerTree();

        $treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $treeProvider->expects(static::any())
            ->method('getTree')
            ->will(static::returnValue($this->tree));

        $entityMetadataProvider =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider')
                ->disableOriginalConstructor()
                ->getMock();
        $entityMetadataProvider->expects(static::any())
            ->method('isProtectedEntity')
            ->will(static::returnValue(true));

        $this->metadataProvider = new OwnershipMetadataProProviderStub($this);
        $this->metadataProvider->setMetadata(
            $this->metadataProvider->getOrganizationClass(),
            new OwnershipProMetadata()
        );
        $this->metadataProvider->setMetadata(
            $this->metadataProvider->getBusinessUnitClass(),
            new OwnershipProMetadata('BUSINESS_UNIT', 'owner', 'owner_id')
        );
        $this->metadataProvider->setMetadata(
            $this->metadataProvider->getUserClass(),
            new OwnershipProMetadata('BUSINESS_UNIT', 'owner', 'owner_id')
        );

        $this->securityContext = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $securityContextLink   =
            $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
                ->disableOriginalConstructor()
                ->getMock();
        $securityContextLink->expects(static::any())->method('getService')
            ->will(static::returnValue($this->securityContext));
        $this->aclVoter = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new OwnershipProConditionDataBuilder(
            $securityContextLink,
            new ObjectIdAccessor(),
            $entityMetadataProvider,
            $this->metadataProvider,
            $treeProvider,
            $this->aclVoter
        );

        $organizationProviderClass = 'OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider';
        $organizationProvider      = $this->getMockBuilder($organizationProviderClass)
            ->disableOriginalConstructor()
            ->getMock();

        $organizationProvider->expects(static::any())
            ->method('getOrganizationId')
            ->will(static::returnValue(null));

        $organizationProvider->expects(static::any())
            ->method('getOrganization')
            ->will(static::returnValue($this->globalOrganization));

        $this->builder->setOrganizationProvider($organizationProvider);

        $registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $om = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $om->expects(static::any())
            ->method('getRepository')
            ->will(static::returnValue($repository));

        $registry
            ->expects(static::any())
            ->method('getManager')
            ->will(static::returnValue($om));

        $this->builder->setRegistry($registry);
    }

    private function buildTestTree()
    {
        /**
         * org1  org2     org3         org4
         *                |            |
         *  bu1   bu2     +-bu3        +-bu4
         *        |       | |            |
         *        |       | +-bu31       |
         *        |       | | |          |
         *        |       | | +-user31   |
         *        |       | |            |
         *  user1 +-user2 | +-user3      +-user4
         *                |                |
         *                +-bu3a           +-bu3
         *                  |              +-bu4
         *                  +-bu3a1          |
         *                                   +-bu41
         *                                     |
         *                                     +-bu411
         *                                       |
         *                                       +-user411
         *
         * user1 user2 user3 user31 user4 user411
         *
         * org1  org2  org3  org3   org4  org4
         * org2        org2
         *
         * bu1   bu2   bu3   bu31   bu4   bu411
         * bu2         bu2
         *
         */
        $this->tree->addBusinessUnit('bu1', null);
        $this->tree->addBusinessUnit('bu2', null);
        $this->tree->addBusinessUnit('bu3', 'org3');
        $this->tree->addBusinessUnit('bu31', 'org3');
        $this->tree->addBusinessUnit('bu3a', 'org3');
        $this->tree->addBusinessUnit('bu3a1', 'org3');
        $this->tree->addBusinessUnit('bu4', 'org4');
        $this->tree->addBusinessUnit('bu41', 'org4');
        $this->tree->addBusinessUnit('bu411', 'org4');

        $this->tree->addBusinessUnitRelation('bu1', null);
        $this->tree->addBusinessUnitRelation('bu2', null);
        $this->tree->addBusinessUnitRelation('bu3', null);
        $this->tree->addBusinessUnitRelation('bu31', 'bu3');
        $this->tree->addBusinessUnitRelation('bu3a', null);
        $this->tree->addBusinessUnitRelation('bu3a1', 'bu3a');
        $this->tree->addBusinessUnitRelation('bu4', null);
        $this->tree->addBusinessUnitRelation('bu41', 'bu4');
        $this->tree->addBusinessUnitRelation('bu411', 'bu41');

        $this->tree->buildTree();

        $this->tree->addUser('user1', null);
        $this->tree->addUser('user2', 'bu2');
        $this->tree->addUser('user3', 'bu3');
        $this->tree->addUser('user31', 'bu31');
        $this->tree->addUser('user4', 'bu4');
        $this->tree->addUser('user41', 'bu41');
        $this->tree->addUser('user411', 'bu411');

        $this->tree->addUserOrganization('user1', 'org1');
        $this->tree->addUserOrganization('user1', 'org2');
        $this->tree->addUserOrganization('user2', 'org2');
        $this->tree->addUserOrganization('user3', 'org2');
        $this->tree->addUserOrganization('user3', 'org3');
        $this->tree->addUserOrganization('user31', 'org3');
        $this->tree->addUserOrganization('user4', 'org4');
        $this->tree->addUserOrganization('user411', 'org4');

        $this->tree->addUserBusinessUnit('user1', 'org1', 'bu1');
        $this->tree->addUserBusinessUnit('user1', 'org2', 'bu2');
        $this->tree->addUserBusinessUnit('user2', 'org2', 'bu2');
        $this->tree->addUserBusinessUnit('user3', 'org3', 'bu3');
        $this->tree->addUserBusinessUnit('user3', 'org2', 'bu2');
        $this->tree->addUserBusinessUnit('user31', 'org3', 'bu31');
        $this->tree->addUserBusinessUnit('user4', 'org4', 'bu4');
        $this->tree->addUserBusinessUnit('user411', 'org4', 'bu411');
    }

    /**
     * @dataProvider buildFilterConstraintProvider
     */
    public function testGetAclConditionData(
        $userId,
        $organizationId,
        $isGranted,
        $accessLevel,
        $ownerType,
        $targetEntityClassName,
        $expectedConstraint,
        $expectedGroup = ''
    ) {
        $this->buildTestTree();

        if ($ownerType !== null) {
            $this->metadataProvider->setMetadata(
                self::TEST_ENTITY,
                new OwnershipProMetadata($ownerType, 'owner', 'owner_id', 'organization', 'organization_id', true)
            );
        }

        /** @var OneShotIsGrantedObserver $aclObserver */
        $aclObserver = null;
        $this->aclVoter->expects(static::any())
            ->method('addOneShotIsGrantedObserver')
            ->will(
                static::returnCallback(
                    function ($observer) use (&$aclObserver, &$accessLevel) {
                        $aclObserver = $observer;
                        /** @var OneShotIsGrantedObserver $aclObserver */
                        $aclObserver->setAccessLevel($accessLevel);
                    }
                )
            );

        $user         = new User($userId);
        $organization = new Organization($organizationId);

        if ($accessLevel === AccessLevel::SYSTEM_LEVEL) {
            $organization->setIsGlobal(true);
        }

        $user->addOrganization($organization);
        $token =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken')
                ->disableOriginalConstructor()
                ->getMock();

        $token->expects(static::any())
            ->method('getUser')
            ->will(static::returnValue($user));
        $token->expects(static::any())
            ->method('getOrganizationContext')
            ->will(static::returnValue($organization));

        /** @var \PHPUnit_Framework_MockObject_MockObject|AclGroupProviderInterface $aclGroupProvider */
        $aclGroupProvider = $this->getMock('Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface');
        $aclGroupProvider->expects($this->any())->method('getGroup')->willReturn($expectedGroup);

        $this->builder->setAclGroupProvider($aclGroupProvider);

        $this->securityContext->expects($this->any())
            ->method('isGranted')
            ->with(
                $this->equalTo('VIEW'),
                $this->callback(
                    function (ObjectIdentity $identity) use ($targetEntityClassName, $expectedGroup) {
                        $this->assertEquals('entity', $identity->getIdentifier());
                        $this->assertStringEndsWith($targetEntityClassName, $identity->getType());
                        if ($expectedGroup) {
                            $this->assertStringStartsWith($expectedGroup, $identity->getType());
                        }

                        return true;
                    }
                )
            )
            ->will($this->returnValue($isGranted));
        $this->securityContext->expects(static::any())
            ->method('getToken')
            ->will(static::returnValue($userId ? $token : null));

        $result = $this->builder->getAclConditionData($targetEntityClassName);

        static::assertEquals(
            $expectedConstraint,
            $result
        );
    }

    public function testGetUserIdWithNonLoginUser()
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects(static::any())
            ->method('getUser')
            ->will(static::returnValue('anon'));
        $this->securityContext->expects(static::any())
            ->method('getToken')
            ->will(static::returnValue($token));
        static::assertNull($this->builder->getUserId());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function buildFilterConstraintProvider()
    {
        return array(
            '1. for the TEST entity without userId, grant, ownerType; with NONE ACL'             => array(
                '',
                '',
                false,
                AccessLevel::NONE_LEVEL,
                null,
                self::TEST_ENTITY,
                []
            ),
            '2. for the stdClass entity without userId, grant, ownerType; with NONE ACL'         => array(
                '',
                '',
                false,
                AccessLevel::NONE_LEVEL,
                null,
                '\stdClass',
                []
            ),
            '3. for the stdClass entity without userId, ownerType; with grant and NONE ACL'      => array(
                '',
                '',
                true,
                AccessLevel::NONE_LEVEL,
                null,
                '\stdClass',
                []
            ),
            '4. for the TEST entity without ownerType; with SYSTEM ACL, userId, grant'           => array(
                'user4',
                '',
                true,
                AccessLevel::SYSTEM_LEVEL,
                null,
                self::TEST_ENTITY,
                []
            ),
            '5. for the TEST entity with SYSTEM ACL, userId, grant and ORGANIZATION ownerType'   => array(
                'user4',
                '',
                true,
                AccessLevel::SYSTEM_LEVEL,
                'ORGANIZATION',
                self::TEST_ENTITY,
                []
            ),
            '6. for the TEST entity with SYSTEM ACL, userId, grant and BUSINESS_UNIT ownerType'  => array(
                'user4',
                '',
                true,
                AccessLevel::SYSTEM_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                []
            ),
            '7. for the TEST entity with SYSTEM ACL, userId, grant and USER ownerType'           => array(
                'user4',
                '',
                true,
                AccessLevel::SYSTEM_LEVEL,
                'USER',
                self::TEST_ENTITY,
                []
            ),
            '8. for the TEST entity without ownerType; with GLOBAL ACL, userId, grant'           => array(
                'user4',
                '',
                true,
                AccessLevel::GLOBAL_LEVEL,
                null,
                self::TEST_ENTITY,
                []
            ),
            '9. for the TEST entity with GLOBAL ACL, userId, grant and ORGANIZATION ownerType'   => array(
                'user4',
                'org4',
                true,
                AccessLevel::GLOBAL_LEVEL,
                'ORGANIZATION',
                self::TEST_ENTITY,
                array(
                    null,
                    null,
                    AccessLevel::GLOBAL_LEVEL,
                    'organization',
                    'org4',
                    true
                )
            ),
            '10. for the TEST entity with GLOBAL ACL, userId, grant and BUSINESS_UNIT ownerType' => array(
                'user4',
                'org4',
                true,
                AccessLevel::GLOBAL_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                array(null, null, AccessLevel::GLOBAL_LEVEL, 'organization', 'org4', true)
            ),
            '11. for the TEST entity with GLOBAL ACL, userId, grant and USER ownerType'          => array(
                'user4',
                'org4',
                true,
                AccessLevel::GLOBAL_LEVEL,
                'USER',
                self::TEST_ENTITY,
                array(null, null, AccessLevel::GLOBAL_LEVEL, 'organization', 'org4', true)
            ),
            '12. for the ORGANIZATION entity without ownerType; with GLOBAL ACL, userId, grant'  => array(
                'user4',
                'org4',
                true,
                AccessLevel::GLOBAL_LEVEL,
                null,
                self::ORGANIZATION,
                [

                ]
            ),
            '13. for the TEST entity without ownerType; with DEEP ACL, userId, grant'            => array(
                'user4',
                '',
                true,
                AccessLevel::DEEP_LEVEL,
                null,
                self::TEST_ENTITY,
                []
            ),
            '14. for the TEST entity with DEEP ACL, userId, grant and ORGANIZATION ownerType'    => array(
                'user4',
                '',
                true,
                AccessLevel::DEEP_LEVEL,
                'ORGANIZATION',
                self::TEST_ENTITY,
                null
            ),
            '15. for the TEST entity with DEEP ACL, userId, grant and BUSINESS_UNIT ownerType'   => array(
                'user4',
                'org4',
                true,
                AccessLevel::DEEP_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                array(
                    'owner',
                    array('bu4', 'bu41', 'bu411'),
                    PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                    'organization',
                    'org4',
                    false
                )
            ),
            '16. for the TEST entity with DEEP ACL, userId, grant and USER ownerType'            => array(
                'user4',
                'org4',
                true,
                AccessLevel::DEEP_LEVEL,
                'USER',
                self::TEST_ENTITY,
                array(
                    'owner',
                    array('user4', 'user411'),
                    PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                    'organization',
                    'org4',
                    false
                )
            ),
            '17. for the BUSINESS entity without ownerType; with DEEP ACL, userId, grant'        => array(
                'user4',
                'org4',
                true,
                AccessLevel::DEEP_LEVEL,
                null,
                self::BUSINESS_UNIT,
                array(
                    'id',
                    array('bu4', 'bu41', 'bu411'),
                    PathExpression::TYPE_STATE_FIELD,
                    null,
                    null,
                    false
                )
            ),
            '18. for the TEST entity without ownerType; with LOCAL ACL, userId, grant'           => array(
                'user4',
                '',
                true,
                AccessLevel::LOCAL_LEVEL,
                null,
                self::TEST_ENTITY,
                []
            ),
            '19. for the TEST entity with LOCAL ACL, userId, grant and ORGANIZATION ownerType'   => array(
                'user4',
                '',
                true,
                AccessLevel::LOCAL_LEVEL,
                'ORGANIZATION',
                self::TEST_ENTITY,
                null
            ),
            '20. for the TEST entity with LOCAL ACL, userId, grant and BUSINESS_UNIT ownerType'  => array(
                'user4',
                'org4',
                true,
                AccessLevel::LOCAL_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                array(
                    'owner',
                    array('bu4'),
                    PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                    'organization',
                    'org4',
                    false
                )
            ),
            '21. for the TEST entity with LOCAL ACL, userId, grant and USER ownerType'           => array(
                'user4',
                'org4',
                true,
                AccessLevel::LOCAL_LEVEL,
                'USER',
                self::TEST_ENTITY,
                array(
                    'owner',
                    array('user4'),
                    PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                    'organization',
                    'org4',
                    false
                )
            ),
            '22. for the BUSINESS entity without ownerType; with LOCAL ACL, userId, grant'       => array(
                'user4',
                'org4',
                true,
                AccessLevel::LOCAL_LEVEL,
                null,
                self::BUSINESS_UNIT,
                array(
                    'id',
                    array('bu4'),
                    PathExpression::TYPE_STATE_FIELD,
                    null,
                    null,
                    false
                )
            ),
            '23. for the TEST entity without ownerType; with BASIC ACL, userId, grant'           => array(
                'user4',
                '',
                true,
                AccessLevel::BASIC_LEVEL,
                null,
                self::TEST_ENTITY,
                []
            ),
            '24. for the TEST entity with BASIC ACL, userId, grant and ORGANIZATION ownerType'   => array(
                'user4',
                '',
                true,
                AccessLevel::BASIC_LEVEL,
                'ORGANIZATION',
                self::TEST_ENTITY,
                null
            ),
            '25. for the TEST entity with BASIC ACL, userId, grant and BUSINESS_UNIT ownerType'  => array(
                'user4',
                '',
                true,
                AccessLevel::BASIC_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                null
            ),
            '26. for the TEST entity with BASIC ACL, userId, grant and USER ownerType'           => array(
                'user4',
                'org4',
                true,
                AccessLevel::BASIC_LEVEL,
                'USER',
                self::TEST_ENTITY,
                array(
                    'owner',
                    'user4',
                    PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                    'organization',
                    'org4',
                    false
                )
            ),
            '27. for the USER entity without ownerType; with BASIC ACL, userId, grant'           => array(
                'user4',
                'org4',
                true,
                AccessLevel::BASIC_LEVEL,
                null,
                self::USER,
                array(
                    'id',
                    'user4',
                    PathExpression::TYPE_STATE_FIELD,
                    null,
                    null,
                    false
                )
            ),
            '28. TEST entity with BASIC ACL, user1, grant and BUSINESS_UNIT ownerType'           => array(
                'user1',
                '',
                true,
                AccessLevel::BASIC_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                null
            ),
            '29. TEST entity with LOCAL ACL, user1, grant and BUSINESS_UNIT ownerType'           => array(
                'user1',
                '',
                true,
                AccessLevel::LOCAL_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                array(
                    'owner',
                    array('bu1', 'bu2'),
                    PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                    null,
                    null,
                    false
                )
            ),
            '30. BUSINESS entity with LOCAL ACL, user1, grant, BUSINESS_UNIT ownerType'          => array(
                'user1',
                'org1',
                true,
                AccessLevel::LOCAL_LEVEL,
                'BUSINESS_UNIT',
                self::BUSINESS_UNIT,
                array(
                    'id',
                    array('bu1'),
                    PathExpression::TYPE_STATE_FIELD,
                    null,
                    null,
                    false
                )
            ),
            '31. USER entity with LOCAL ACL, user1, grant, BUSINESS_UNIT ownerType'              => array(
                'user1',
                '',
                true,
                AccessLevel::LOCAL_LEVEL,
                'BUSINESS_UNIT',
                self::USER,
                array(
                    'owner',
                    array('bu1', 'bu2'),
                    PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                    null,
                    null,
                    false
                )
            ),
            '32. TEST entity with DEEP ACL, user1, grant and BUSINESS_UNIT ownerType'            => array(
                'user1',
                'org2',
                true,
                AccessLevel::DEEP_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                array(
                    'owner',
                    array('bu2'),
                    PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                    'organization',
                    'org2',
                    false
                )
            ),
            '33. TEST entity with GLOBAL ACL, user1, grant and BUSINESS_UNIT ownerType'          => array(
                'user1',
                '',
                true,
                AccessLevel::GLOBAL_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                null
            ),
            '34. TEST entity with GLOBAL ACL, user1, grant and BUSINESS_UNIT ownerType WITH custom group' => array(
                'user1',
                '',
                true,
                AccessLevel::GLOBAL_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                null,
                'custom_group'
            )
        );
    }
}
