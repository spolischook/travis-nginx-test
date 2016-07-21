<?php

namespace Oro\Bundle\InvoiceProBundle\Tests\Functional\Controller;

use OroB2B\Bundle\InvoiceBundle\Tests\Functional\Controller\InvoiceControllerTest as BaseInvoiceControllerTest;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class InvoiceControllerTest extends BaseInvoiceControllerTest
{
    /**
     * {@inheritdoc}
     */
    public function getSubmittedData($form, $account, $today, $lineItems, $poNumber)
    {
        $data = parent::getSubmittedData($form, $account, $today, $lineItems, $poNumber);

        $data['website'] = $this->getReference(LoadWebsiteData::WEBSITE1)->getId();

        return $data;
    }
}
