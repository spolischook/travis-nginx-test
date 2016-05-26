<?php

namespace OroCRM\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Doctrine\ORM\AbstractQuery;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use OroCRM\Bundle\MailChimpBundle\Model\ExtendedMergeVar\ProviderInterface;
use OroCRM\Bundle\MarketingListBundle\Provider\MarketingListProvider;
use OroCRM\Bundle\MailChimpBundle\Entity\StaticSegment;
use OroCRM\Bundle\MailChimpBundle\Util\CallbackFilterIteratorCompatible;

class MmbrExtdMergeVarIterator extends AbstractStaticSegmentMembersIterator
{
    const STATIC_SEGMENT_MEMBER_ALIAS = 'ssm';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var array
     */
    protected $uniqueMembers = [];

    /**
     * @var ProviderInterface
     */
    protected $extendMergeVarsProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        parent::rewind();
        $this->uniqueMembers = [];
    }

    /**
     * @param ProviderInterface $extendMergeVarsProvider
     * @return MmbrExtdMergeVarIterator
     */
    public function setExtendMergeVarsProvider(ProviderInterface $extendMergeVarsProvider)
    {
        $this->extendMergeVarsProvider = $extendMergeVarsProvider;

        return $this;
    }

    /**
     * @param StaticSegment $staticSegment
     *
     * {@inheritdoc}
     */
    protected function createSubordinateIterator($staticSegment)
    {
        $this->assertRequiredDependencies();

        if (!$this->extendMergeVarsProvider->isApplicable($staticSegment->getMarketingList())) {
            return new \EmptyIterator();
        }

        if (!$staticSegment->getExtendedMergeVars()) {
            return new \EmptyIterator();
        }

        $qb = $this->getIteratorQueryBuilder($staticSegment);

        $marketingList = $staticSegment->getMarketingList();
        $memberIdentifier = self::MEMBER_ALIAS . '.id';
        $fieldExpr = $this->fieldHelper
            ->getFieldExpr(
                $marketingList->getEntity(),
                $qb,
                $this->doctrineHelper->getSingleEntityIdentifierFieldName($marketingList->getEntity())
            );
        $qb->addSelect($fieldExpr . ' AS entity_id');
        $qb->addSelect($memberIdentifier . ' AS member_id');

        $qb->andWhere(
            $qb->expr()->andX(
                $qb->expr()->isNotNull($memberIdentifier)
            )
        );

        $bufferedIterator = new BufferedQueryResultIterator($qb);
        $bufferedIterator->setHydrationMode(AbstractQuery::HYDRATE_ARRAY)->setReverse(true);

        $uniqueMembers = &$this->uniqueMembers;

        return new CallbackFilterIteratorCompatible(
            $bufferedIterator,
            function (&$current) use ($staticSegment, &$uniqueMembers) {
                if (is_array($current)) {
                    if (!empty($current['member_id']) && in_array($current['member_id'], $uniqueMembers, true)) {
                        return false;
                    }
                    $current['subscribersList_id'] = $staticSegment->getSubscribersList()->getId();
                    $current['static_segment_id']  = $staticSegment->getId();
                    $uniqueMembers[] = $current['member_id'];
                    unset($current['id']);
                }
                return true;
            }
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function assertRequiredDependencies()
    {
        if (!$this->doctrineHelper) {
            throw new \InvalidArgumentException('DoctrineHelper must be provided.');
        }

        if (!$this->fieldHelper) {
            throw new \InvalidArgumentException('FieldHelper must be provided.');
        }

        if (!$this->segmentMemberClassName) {
            throw new \InvalidArgumentException('StaticSegmentMember class name must be provided.');
        }

        if (!$this->extendMergeVarsProvider) {
            throw new \InvalidArgumentException('ExtendMergeVarsProvider must be provided.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getIteratorQueryBuilder(StaticSegment $staticSegment)
    {
        $marketingList = $staticSegment->getMarketingList();

        if ($marketingList->isManual()) {
            $mixin = MarketingListProvider::MANUAL_RESULT_ENTITIES_MIXIN;
        } else {
            $mixin = MarketingListProvider::RESULT_ENTITIES_MIXIN;
        }

        $qb = clone $this->marketingListProvider->getMarketingListQueryBuilder($marketingList, $mixin);
        $this->matchMembersByEmail($staticSegment, $qb);
        $this->applyOrganizationRestrictions($staticSegment, $qb);

        return $qb;
    }
}
