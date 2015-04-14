<?php

namespace OroCRM\Bundle\MailChimpBundle\ImportExport\Reader;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\MailChimpBundle\Model\ExtendedMergeVar\DecisionHandler;
use OroCRM\Bundle\MailChimpBundle\Provider\Transport\Iterator\ExtendedMergeVarExportIterator;

class ExtendedMergeVarExportReader extends AbstractIteratorBasedReader
{
    /**
     * @var string
     */
    protected $staticSegmentClassName;

    /**
     * @var string
     */
    protected $extendedMergeVarClassName;

    /**
     * @var DecisionHandler
     */
    protected $decisionHandler;

    /**
     * @param string $staticSegmentClassName
     */
    public function setStaticSegmentClassName($staticSegmentClassName)
    {
        $this->staticSegmentClassName = $staticSegmentClassName;
    }

    /**
     * @param string $extendedMergeVarClassName
     */
    public function setExtendedMergeVarClassName($extendedMergeVarClassName)
    {
        $this->extendedMergeVarClassName = $extendedMergeVarClassName;
    }

    /**
     * @param DecisionHandler $decisionHandler
     */
    public function setDecisionHandler(DecisionHandler $decisionHandler)
    {
        $this->decisionHandler = $decisionHandler;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        if (!$this->getSourceIterator()) {
            if (!$this->extendedMergeVarClassName) {
                throw new InvalidConfigurationException('ExtendedMergeVar class name must be provided');
            }

            /** @var Channel $channel */
            $channel = $this->doctrineHelper->getEntityReference(
                $this->channelClassName,
                $context->getOption('channel')
            );

            $iterator = new ExtendedMergeVarExportIterator(
                $this->getSegmentsIterator($channel),
                $this->decisionHandler,
                $this->doctrineHelper,
                $this->extendedMergeVarClassName
            );

            $this->setSourceIterator($iterator);
        }
    }

    /**
     * @param Channel $channel
     * @return BufferedQueryResultIterator
     */
    protected function getSegmentsIterator(Channel $channel)
    {
        if (!$this->staticSegmentClassName) {
            throw new InvalidConfigurationException('StaticSegment class name must be provided');
        }

        $qb = $this->doctrineHelper
            ->getEntityManager($this->staticSegmentClassName)
            ->getRepository($this->staticSegmentClassName)
            ->createQueryBuilder('staticSegment')
            ->select('staticSegment');

        $qb
            ->andWhere($qb->expr()->eq('staticSegment.channel', ':channel'))
            ->setParameter('channel', $channel);

        return new BufferedQueryResultIterator($qb);
    }
}
