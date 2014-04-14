<?php

namespace OroPro\Bundle\EwsBundle\Provider\Iterator;

use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;

abstract class AbstractBatchIterator implements \Iterator, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const DEFAULT_SYNC_RANGE = '1 month';
    const READ_BATCH_SIZE    = 1;

    /** @var \DateTime needed to restore initial value on next rewinds */
    protected $lastSyncDateInitialValue;

    /** @var \DateTime */
    protected $lastSyncDate;

    /** @var \DateInterval */
    protected $syncRange;

    /** @var array */
    protected $buffer = [];

    /** @var null|\stdClass */
    protected $current;

    /** @var bool */
    protected $loaded = false;

    /** @var EwsEmailManager */
    protected $ewsManager;

    /** @var SearchQuery */
    protected $searchQuery;

    /** @var int */
    protected $offset = 0;

    /** @var int */
    protected $key = 0;

    public function __construct(EwsEmailManager $ewsEmailManager, SearchQuery $searchQuery, \DateTime $startSyncDate)
    {
        $this->ewsManager  = $ewsEmailManager;
        $this->searchQuery = clone $searchQuery;
        $this->syncRange   = \DateInterval::createFromDateString(self::DEFAULT_SYNC_RANGE);

        $this->setStartDate($startSyncDate);
        $this->setLogger(new NullLogger());
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $this->logger->info(sprintf('Loading entity by id: %s', $this->key()));

        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        do {
            if (!empty($this->buffer)) {
                $result = array_shift($this->buffer);
                $this->key++;
            } else {
                $result = $this->findEntities();
            }

            // no more data to look for
            if (is_null($result)) {
                break;
            }

            // loop again if result is true
            // true means that there are entities to process or
            // there are intervals to retrieve entities there
        } while ($result === true);

        $this->current = $result;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return null !== $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        if (false === $this->loaded) {
            $this->loaded = true;
        }

        $this->buffer       = [];
        $this->current      = null;
        $this->key          = 0;
        //$this->lastSyncDate = clone $this->lastSyncDateInitialValue;

        // reset filter
        // $this->filter->reset();

        $this->next();
    }

    /**
     * {@inheritdoc}
     */
    public function setStartDate(\DateTime $date)
    {
        $this->lastSyncDate             = clone $date;
        $this->lastSyncDateInitialValue = clone $date;
    }

    /**
     * Load entities
     *
     * @return true|null true when there are entities retrieved
     */
    protected function findEntities()
    {
        $this->logger->info('Looking for batch');

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        // TODO: add date filter to search query
        $offset = $this->offset * self::READ_BATCH_SIZE;
        $prepareRequestClosure = function (EwsType\FindItemType $request) use ($offset) {
            $request->IndexedPageItemView = new EwsType\IndexedPageViewType();
            $request->IndexedPageItemView->BasePoint = EwsType\IndexBasePointType::BEGINNING;
            $request->IndexedPageItemView->MaxEntriesReturned = self::READ_BATCH_SIZE;
            $request->IndexedPageItemView->Offset = $offset;
        };

        $this->buffer = $this->ewsManager->getEmails($this->searchQuery, $prepareRequestClosure);
        $this->logger->info(sprintf('found %d entities', count($this->buffer)));

        // clone date to check if there's last batch in this range
//        $lastSyncDate = clone $this->lastSyncDate;
//        $lastSyncDate->add($this->syncRange);

        if (empty($this->buffer)) {
            $result = null;
        } else {
            $result = true;
        }

        // increment date for further filtering
        //$this->lastSyncDate->add($this->syncRange);

        return $result;
    }
}
