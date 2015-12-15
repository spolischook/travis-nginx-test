<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Manager\ActivityListManager;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager\Fixture\TestActivityList;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager\Fixture\TestOrganization;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager\Fixture\TestUser;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Provider\Fixture\TestActivityProvider;

class ActivityListManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityListManager */
    protected $activityListManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityNameResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $pager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $activityListFilterHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $commentManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $inheritanceHelper;

    public function setUp()
    {
        $this->securityFacade     = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()->getMock();
        $this->entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()->getMock();
        $this->pager              = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager')
            ->disableOriginalConstructor()->getMock();
        $this->config             = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();
        $this->doctrineHelper     = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()->getMock();
        $this->provider = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
            ->disableOriginalConstructor()->getMock();
        $this->activityListFilterHelper = $this
            ->getMockBuilder('Oro\Bundle\ActivityListBundle\Filter\ActivityListFilterHelper')
            ->disableOriginalConstructor()->getMock();
        $this->em             = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->commentManager = $this->getMockBuilder('Oro\Bundle\CommentBundle\Entity\Manager\CommentApiManager')
            ->disableOriginalConstructor()->getMock();

        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Helper\ActivityListAclCriteriaHelper')
            ->disableOriginalConstructor()->getMock();
        $this->inheritanceHelper = $this
            ->getMockBuilder('Oro\Bundle\ActivityListBundle\Helper\ActivityInheritanceTargetsHelper')
            ->disableOriginalConstructor()->getMock();

        $this->activityListManager = new ActivityListManager(
            $this->securityFacade,
            $this->entityNameResolver,
            $this->pager,
            $this->config,
            $this->provider,
            $this->activityListFilterHelper,
            $this->commentManager,
            $this->doctrineHelper,
            $this->aclHelper,
            $this->inheritanceHelper
        );
    }

    public function testGetRepository()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')->with('OroActivityListBundle:ActivityList');
        $this->activityListManager->getRepository();
    }

    public function testGetList()
    {
        $classMeta     = new ClassMetadata('Oro\Bundle\ActivityListBundle\Entity\ActivityList');
        $repo          = new ActivityListRepository($this->em, $classMeta);
        $testClass     = 'Acme\TestBundle\Entity\TestEntity';
        $testId        = 12;
        $page          = 2;
        $filter        = [];
        $configPerPare = 10;

        $this->config->expects($this->any())->method('get')
            ->willReturnCallback(
                function ($configKey) {
                    if ($configKey === 'oro_activity_list.per_page') {
                        return 10;
                    }
                    if ($configKey === 'oro_activity_list.sorting_field') {
                        return 'createdBy';
                    }
                    if ($configKey === 'oro_activity_list.grouping') {
                        return false;
                    }
                    return 'ASC';
                }
            );

        $this->aclHelper->expects($this->any())->method('applyAclCriteria')->willReturn('');

        $qb = new QueryBuilder($this->em);
        $this->em->expects($this->any())->method('createQueryBuilder')->willReturn($qb);
        $this->doctrineHelper->expects($this->any())->method('getEntityRepository')->willReturn($repo);
        $this->activityListFilterHelper->expects($this->once())->method('addFiltersToQuery')->with($qb, $filter);
        $this->pager->expects($this->once())->method('setQueryBuilder')->with($qb);
        $this->pager->expects($this->once())->method('setPage')->with($page);

        $this->pager->expects($this->once())->method('setMaxPerPage')->with($configPerPare);
        $this->pager->expects($this->once())->method('init');
        $this->pager->expects($this->once())->method('getResults')->willReturn([]);

        $this->provider->expects($this->once())->method('getProviders')->willReturn([]);

        $this->activityListManager->getList($testClass, $testId, $filter, $page);

        $expectedDQL = 'SELECT activity FROM Oro\Bundle\ActivityListBundle\Entity\ActivityList activity '
            . 'LEFT JOIN activity.test_entity_9d8125dd r LEFT JOIN activity.activityOwners ao '
            . 'WHERE r.id = :entityId GROUP BY activity.id ORDER BY activity.createdBy ASC';

        $this->assertEquals($expectedDQL, $qb->getDQL());
        $this->assertEquals($testId, $qb->getParameters()->first()->getValue());
    }

    public function testGetNonExistItem()
    {
        $repo = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository')
            ->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);
        $repo->expects($this->once())->method('find')->with(12)->willReturn(null);
        $this->assertNull($this->activityListManager->getItem(12));
    }

    public function testGetItem()
    {
        $testItem = new TestActivityList();
        $testItem->setId(105);
        $owner = new TestUser();
        $owner->setId(15);
        $editor = new TestUser();
        $editor->setId(142);
        $organization = new TestOrganization();
        $organization->setId(584);
        $testItem->setOwner($owner);
        $testItem->setEditor($editor);
        $testItem->setOrganization($organization);
        $testItem->setCreatedAt(new \DateTime('2012-01-01', new \DateTimeZone('UTC')));
        $testItem->setUpdatedAt(new \DateTime('2014-01-01', new \DateTimeZone('UTC')));
        $testItem->setVerb(ActivityList::VERB_UPDATE);
        $testItem->setSubject('test_subject');
        $testItem->setDescription('test_description');
        $testItem->setRelatedActivityClass('Acme\TestBundle\Entity\TestEntity');
        $testItem->setRelatedActivityId(127);

        $this->entityNameResolver->expects($this->any())->method('getName')
            ->willReturnCallback(
                function ($user) {
                    if ($user->getId() === 15) {
                        return 'Owner_String';
                    }

                    return 'Editor_String';
                }
            );

        $repo = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository')
            ->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);
        $repo->expects($this->once())->method('find')->with(105)->willReturn($testItem);

        $this->securityFacade->expects($this->any())->method('isGranted')->willReturn(true);

        $provider = new TestActivityProvider();
        $this->provider->expects($this->once())->method('getProviderForEntity')->willReturn($provider);

        $this->assertEquals(
            [
                'id'                   => 105,
                'owner'                => 'Owner_String',
                'owner_id'             => 15,
                'editor'               => 'Editor_String',
                'editor_id'            => 142,
                'verb'                 => 'update',
                'subject'              => 'test_subject',
                'description'          => 'test_description',
                'data'                 => ['test_data'],
                'relatedActivityClass' => 'Acme\TestBundle\Entity\TestEntity',
                'relatedActivityId'    => 127,
                'createdAt'            => '2012-01-01T00:00:00+00:00',
                'updatedAt'            => '2014-01-01T00:00:00+00:00',
                'editable'             => true,
                'removable'            => true,
                'commentCount'         => '',
                'commentable'          => '',
                'targetEntityData'     => [],
                'is_head'              => false,
            ],
            $this->activityListManager->getItem(105)
        );
    }

    public function testGetGroupedEntitiesEmpty()
    {
        $this->provider
            ->expects($this->once())
            ->method('getProviderForEntity')
            ->willReturn($this->returnValue(new TestActivityProvider()));
        $this->assertCount(0, $this->activityListManager->getGroupedEntities(new \stdClass(), '', '', 0, []));
    }

    protected function mockEmailActivityListProvider()
    {
        $emailActivityListProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider')
        ->disableOriginalConstructor()->getMock();

        $emailActivityListProvider->expects($this->once())->method('getActivityClass')->willReturn('ActivityClass');
        $emailActivityListProvider->expects($this->once())->method('getAclClass')->willReturn('AclClass');

        return $emailActivityListProvider;
    }
}
