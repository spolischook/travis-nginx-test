<?php

namespace OroCRM\Bundle\MagentoBundle\ImportExport\Writer;

use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;

use OroCRM\Bundle\MagentoBundle\Entity\Cart;
use OroCRM\Bundle\MagentoBundle\Entity\Order;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;

class ProxyEntityWriter implements ItemWriterInterface, StepExecutionAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ItemWriterInterface */
    protected $writer;

    /** @var DatabaseHelper */
    protected $databaseHelper;

    /**
     * @param ItemWriterInterface $writer
     * @param DatabaseHelper $databaseHelper
     */
    public function __construct(ItemWriterInterface $writer, DatabaseHelper $databaseHelper)
    {
        $this->writer = $writer;
        $this->databaseHelper = $databaseHelper;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     *
     * Prepare items for PersistentBatchWriter, filters for duplicates and takes only latest versions
     */
    public function write(array $items)
    {
        $uniqueItems = [];
        $uniqueKeys = [];
        foreach ($items as $item) {
            if ($item instanceof Customer || $item instanceof Cart) {
                $identifier = $item->getOriginId();
                if (array_key_exists($identifier, $uniqueItems)) {
                    $this->logSkipped($identifier);
                }

                $uniqueItems[$identifier] = $item;
            } elseif ($item instanceof Order) {
                $identifier = $item->getIncrementId();
                if (array_key_exists($identifier, $uniqueItems)) {
                    $this->logSkipped($item->getIncrementId());
                }

                $uniqueItems[$identifier] = $item;
            } elseif ($item instanceof NewsletterSubscriber) {
                $identifier = $item->getCustomer() ? $item->getCustomer()->getId() : 0;
                if ($identifier !== 0 && in_array($identifier, $uniqueKeys)) {
                    $this->logSkipped($item->getOriginId());
                } else {
                    $uniqueKeys[] = $identifier;
                    $uniqueItems[] = $item;
                }

            } else {
                $uniqueItems[] = $item;
            }
        }

        $this->writer->write($uniqueItems);

        // force entity cache clear if clear is skipped
        $this->databaseHelper->onClear();
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        if ($this->writer instanceof StepExecutionAwareInterface) {
            $this->writer->setStepExecution($stepExecution);
        }
    }

    /**
     * @param int|string $identifier
     */
    protected function logSkipped($identifier)
    {
        $this->logger->info(
            sprintf('[origin_id=%s] Item skipped because of newer version found', (string)$identifier)
        );
    }
}
