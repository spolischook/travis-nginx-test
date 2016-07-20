<?php

namespace Oro\Bundle\WarehouseProBundle\Tests\Unit\ImportExport\Reader;

use Monolog\Logger;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Security\Core\SecurityContext;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\WarehouseProBundle\ImportExport\Reader\ProInventoryLevelReader;

use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class ProInventoryLevelReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testAddOrganizationLimitsCalled()
    {
        $name = WarehouseInventoryLevel::class;

        $reader = $this->getReader($name, true);

        $securityFacade = $this->getSecurityFacade();
        $securityFacade->expects($this->exactly(1))
            ->method('getOrganization')
            ->will($this->returnValue(false));

        $reader->setSecurityFacade($securityFacade);

        $reader->setSourceEntityName($name, new Organization());
    }

    public function testAddOrganizationLimitsNotCalled()
    {
        $name = WarehouseInventoryLevel::class;

        $reader = $this->getReader($name);

        $organization = $this->getMockBuilder(Organization::class)
            ->setMethods(['getIsGlobal'])
            ->getMock();
        $organization->expects($this->exactly(1))
            ->method('getIsGlobal')
            ->will($this->returnValue(true));

        $securityFacade = $this->getSecurityFacade();
        $securityFacade->expects($this->exactly(2))
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $reader->setSecurityFacade($securityFacade);

        $reader->setSourceEntityName($name, $organization);
    }

    /**
     * @param string $name
     * @param bool $full
     * @return ProInventoryLevelReader
     */
    protected function getReader($name = '', $full = false)
    {
        $contextRegistry = $this->getMockBuilder(ContextRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $managerRegistry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ownershipMetadataProvider = $this->getMockBuilder(OwnershipMetadataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($full) {
            $ownershipMetadata = $this->getMockBuilder(OwnershipMetadata::class)
                ->disableOriginalConstructor()
                ->getMock();
            $ownershipMetadata->expects($this->once())
                ->method('getGlobalOwnerFieldName')
                ->will(
                    $this->returnValue(false)
                );
            $ownershipMetadataProvider->expects($this->once())
                ->method('getMetadata')
                ->will(
                    $this->returnValue($ownershipMetadata)
                );
        }

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $classMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->will(
                $this->returnValue([])
            );
        $classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will(
                $this->returnValue([])
            );

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->will($this->returnValue($queryBuilder));

        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with($name)
            ->will($this->returnValue($repository));

        $entityManager->expects($this->once())->method('getClassMetadata')
            ->with($name)
            ->will($this->returnValue($classMetadata));

        $managerRegistry->expects($this->once())->method('getManagerForClass')
            ->with($name)
            ->will($this->returnValue($entityManager));

        return new ProInventoryLevelReader($contextRegistry, $managerRegistry, $ownershipMetadataProvider);
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        $securityContext = $this->getMockBuilder(SecurityContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $aclAnnotationProvider = $this->getMockBuilder(AclAnnotationProvider::class)
            ->getMock();
        $objectIdentityFactory = $this->getMockBuilder(ObjectIdentityFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityClassResolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $this->getMockBuilder(SecurityFacade::class)
            ->setConstructorArgs(
                [
                    $securityContext,
                    $aclAnnotationProvider,
                    $objectIdentityFactory,
                    $entityClassResolver,
                    $logger
                ]
            )
            ->getMock();
    }
}
