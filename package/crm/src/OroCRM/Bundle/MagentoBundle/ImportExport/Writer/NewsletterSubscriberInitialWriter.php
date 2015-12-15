<?php

namespace OroCRM\Bundle\MagentoBundle\ImportExport\Writer;

use OroCRM\Bundle\MagentoBundle\Entity\MagentoSoapTransport;
use OroCRM\Bundle\MagentoBundle\Entity\NewsletterSubscriber;

class NewsletterSubscriberInitialWriter extends ProxyEntityWriter
{
    /**
     * @param NewsletterSubscriber[] $items
     */
    public function write(array $items)
    {
        parent::write($items);

        // Save minimum originId received by initial sync for further filtering in case of failure
        $lastSubscriber = $items[count($items) - 1];
        $transport = $lastSubscriber->getChannel()->getTransport();
        if ($transport instanceof MagentoSoapTransport) {
            /** @var MagentoSoapTransport $transport */
            $transport = $this->databaseHelper->getEntityReference($transport);
            $syncedToId = $transport->getNewsletterSubscriberSyncedToId();
            if (!$syncedToId || $syncedToId > $lastSubscriber->getOriginId()) {
                $transport->setNewsletterSubscriberSyncedToId($lastSubscriber->getOriginId());
                $this->writer->write([$transport]);
            }
        }
    }
}
