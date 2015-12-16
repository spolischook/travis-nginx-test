<?php

namespace OroCRM\Bundle\MailChimpBundle\ImportExport\Writer;

use Doctrine\Common\Collections\ArrayCollection;

use OroCRM\Bundle\MailChimpBundle\Entity\ExtendedMergeVar;
use OroCRM\Bundle\MailChimpBundle\Entity\SubscribersList;

class ExtendedMergeVarExportWriter extends AbstractExportWriter
{
    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        /** @var ExtendedMergeVar $item */
        $item = reset($items);
        $staticSegment = $item->getStaticSegment();
        $channel = $staticSegment->getChannel();
        $this->transport->init($channel->getTransport());

        $items = new ArrayCollection($items);

        $itemsToWrite = [];

        try {
            $addedItems = $this->add($items);
            $removedItems = $this->remove($items);

            if ($addedItems) {
                $this->logger->info(sprintf('Extended merge vars: [%s] added', count($addedItems)));
            }

            if ($removedItems) {
                $this->logger->info(sprintf('Extended merge vars: [%s] removed', count($addedItems)));
            }

            $itemsToWrite = array_merge($itemsToWrite, $addedItems, $removedItems);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->stepExecution->addFailureException($e);
        }

        parent::write($itemsToWrite);
    }

    /**
     * @param ArrayCollection $items
     * @return array
     */
    protected function add(ArrayCollection $items)
    {
        $items = $items->filter(function (ExtendedMergeVar $extendedMergeVar) {
            return $extendedMergeVar->isAddState();
        });

        if ($items->isEmpty()) {
            return [];
        }

        $mergeVars = $this->getSubscribersListMergeVars(
            $items->first()->getStaticSegment()->getSubscribersList()
        );

        $successItems = [];
        /** @var ExtendedMergeVar $each */
        foreach ($items as $each) {
            $exists = array_filter($mergeVars, function ($var) use ($each) {
                return $var['tag'] === $each->getTag();
            });
            $response = [];
            if (empty($exists)) {
                $response = $this->transport->addListMergeVar(
                    [
                        'id' => $each->getStaticSegment()->getSubscribersList()->getOriginId(),
                        'tag' => $each->getTag(),
                        'name' => $each->getLabel(),
                        'options' => [
                            'field_type' => $each->getFieldType(),
                            'require' => $each->isRequired()
                        ]
                    ]
                );
            }

            $this
                ->handleResponse(
                    $response,
                    function ($response) use (&$successItems, $each) {
                        if (empty($response['errors'])) {
                            $each->markSynced();
                            $successItems[] = $each;
                        }
                    }
                );
        }

        return $successItems;
    }

    /**
     * @param ArrayCollection $items
     * @return array
     */
    protected function remove(ArrayCollection $items)
    {
        $items = $items->filter(function (ExtendedMergeVar $extendedMergeVar) {
            return $extendedMergeVar->isRemoveState();
        });

        if ($items->isEmpty()) {
            return [];
        }
        $successItems = [];
        /** @var ExtendedMergeVar $each */
        foreach ($items as $each) {
            $each->markDropped();
            $successItems[] = $each;
        }
        return $successItems;
    }

    /**
     * @param SubscribersList $subscribersList
     * @return array
     */
    protected function getSubscribersListMergeVars(SubscribersList $subscribersList)
    {
        $response = $this->transport->getListMergeVars(
            [
                'id' => [
                    $subscribersList->getOriginId()
                ]
            ]
        );

        $this->handleResponse($response);

        if (!empty($response['errors'])) {
            throw new \RuntimeException('Can not get list of merge vars.');
        }

        return $this->extractMergeVarsFromResponse($response);
    }

    /**
     * @param array $response
     * @return array
     */
    protected function extractMergeVarsFromResponse(array $response)
    {
        if (!isset($response['data'])) {
            throw new \RuntimeException('Can not extract merge vars data from response.');
        }
        $data = reset($response['data']);
        if (!is_array($data) || !isset($data['merge_vars']) || !is_array($data['merge_vars'])) {
            return [];
        }
        return $data['merge_vars'];
    }
}
