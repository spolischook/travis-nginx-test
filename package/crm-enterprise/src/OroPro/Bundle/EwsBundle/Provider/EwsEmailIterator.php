<?php

namespace OroPro\Bundle\EwsBundle\Provider;

use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;
use OroPro\Bundle\EwsBundle\Manager\DTO\Email;

class EwsEmailIterator implements \Iterator
{
    /** @var EwsEmailManager */
    private $ewsManager;

    /** @var SearchQuery */
    private $searchQuery;

    /** @var int */
    private $batchSize = 1;

    /** @var \Closure|null */
    private $onBatchLoaded;

    /** @var Email[] */
    private $batch = [];

    /** @var Email|null */
    private $current;

    /** @var bool */
    private $loaded = false;

    /** @var int */
    private $offset = 0;

    /** @var int */
    private $key = 0;

    /**
     * @param EwsEmailManager $ewsEmailManager
     * @param SearchQuery     $searchQuery
     */
    public function __construct(EwsEmailManager $ewsEmailManager, SearchQuery $searchQuery)
    {
        $this->ewsManager  = $ewsEmailManager;
        $this->searchQuery = clone $searchQuery;
    }

    /**
     * Sets batch size
     *
     * @param int $batchSize Determines how many messages can be loaded at once
     */
    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;
    }

    /**
     * Sets a callback function is called when a batch is loaded
     *
     * @param \Closure|null $callback The callback function is called when a batch is loaded
     *                                function (Email[] $batch)
     */
    public function setBatchCallback(\Closure $callback = null)
    {
        $this->onBatchLoaded = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        do {
            if (!empty($this->batch)) {
                $result = array_shift($this->batch);
                $this->key++;
            } else {
                $result = $this->findEntities();
                if ($result && $this->onBatchLoaded !== null) {
                    call_user_func($this->onBatchLoaded, $this->batch);
                }
            }

            // no more data to look for
            if (is_null($result)) {
                break;
            }

            // loop again if result is true
            // true means that there are entities to process
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

        $this->batch   = [];
        $this->current = null;
        $this->key     = 0;

        $this->next();
    }

    /**
     * Load entities
     *
     * @return true|null true when there are entities retrieved
     */
    protected function findEntities()
    {
        $offset = $this->offset * $this->batchSize;
        $this->offset++;

        $prepareRequestClosure = function (EwsType\FindItemType $request) use ($offset) {
            $request->IndexedPageItemView                     = new EwsType\IndexedPageViewType();
            $request->IndexedPageItemView->BasePoint          = EwsType\IndexBasePointType::BEGINNING;
            $request->IndexedPageItemView->MaxEntriesReturned = $this->batchSize;
            $request->IndexedPageItemView->Offset             = $offset;
        };


        $this->batch = $this->ewsManager->getEmails($this->searchQuery, $prepareRequestClosure);

        return empty($this->batch) ? null : true;
    }
}
