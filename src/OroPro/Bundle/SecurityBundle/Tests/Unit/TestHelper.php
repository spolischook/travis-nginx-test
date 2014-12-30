<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnershipDecisionMaker;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\OwnershipMetadataProviderStub;

use OroPro\Bundle\SecurityBundle\Acl\Extension\EntityAclProExtension;

class TestHelper
{
    public static function get(\PHPUnit_Framework_TestCase $testCase)
    {
        return new TestHelper($testCase);
    }

    /** @var \PHPUnit_Framework_TestCase */
    private $testCase;

    public function __construct(\PHPUnit_Framework_TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    /**
     * @param  OwnershipMetadataProvider $metadataProvider
     * @param  OwnerTree                 $ownerTree
     * @param  ObjectIdAccessor          $idAccessor
     *
     * @return EntityAclProExtension
     */
    public function createEntityAclExtension(
        OwnershipMetadataProvider $metadataProvider = null,
        OwnerTree $ownerTree = null,
        ObjectIdAccessor $idAccessor = null
    ) {
        if ($idAccessor === null) {
            $idAccessor = new ObjectIdAccessor();
        }
        if ($metadataProvider === null) {
            $metadataProvider = new OwnershipMetadataProviderStub($this->testCase);
        }
        if ($ownerTree === null) {
            $ownerTree = new OwnerTree();
        }

        $treeProviderMock = $this->testCase->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $treeProviderMock->expects($this->testCase->any())
            ->method('getTree')
            ->will($this->testCase->returnValue($ownerTree));

        $decisionMaker = new EntityOwnershipDecisionMaker(
            $treeProviderMock,
            $idAccessor,
            new EntityOwnerAccessor($metadataProvider),
            $metadataProvider
        );

        $config = $this->testCase->getMockBuilder('\Doctrine\ORM\Configuration')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->testCase->any())
            ->method('getEntityNamespaces')
            ->will(
                $this->testCase->returnValue(
                    array(
                        'Test' => 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity'
                    )
                )
            );

        $em = $this->testCase->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->testCase->any())
            ->method('getConfiguration')
            ->will($this->testCase->returnValue($config));

        $doctrine = $this->testCase->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->testCase->any())
            ->method('getManagers')
            ->will($this->testCase->returnValue(array('default' => $em)));
        $doctrine->expects($this->testCase->any())
            ->method('getManagerForClass')
            ->will($this->testCase->returnValue(new \stdClass()));
        $doctrine->expects($this->testCase->any())
            ->method('getManager')
            ->with($this->testCase->equalTo('default'))
            ->will($this->testCase->returnValue($em));
        $doctrine->expects($this->testCase->any())
            ->method('getAliasNamespace')
            ->will(
                $this->testCase->returnValueMap(
                    array(
                        array('Test', 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity'),
                    )
                )
            );

        $entityMetadataProvider =
            $this->testCase->getMockBuilder('Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider')
                ->disableOriginalConstructor()
                ->getMock();
        $entityMetadataProvider->expects($this->testCase->any())
            ->method('isProtectedEntity')
            ->will($this->testCase->returnValue(true));

        return new EntityAclProExtension(
            $idAccessor,
            new EntityClassResolver($doctrine),
            $entityMetadataProvider,
            $metadataProvider,
            $decisionMaker
        );
    }
}
