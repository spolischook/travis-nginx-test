<?php

namespace Oro\Bundle\OrderProBundle\Tests\Functional\Controller;

use OroB2B\Bundle\OrderBundle\Tests\Functional\Controller\OrderControllerTest as BaseOrderControllerTest;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class OrderControllerTest extends BaseOrderControllerTest
{
    /**
     * @var Website
     */
    protected $website;

    /**
     * {@inheritdoc}
     */
    public function getSubmittedData($form, $orderAccount, $lineItems, $discountItems)
    {
        $submittedData = parent::getSubmittedData(
            $form,
            $orderAccount,
            $lineItems,
            $discountItems
        );
        $submittedData['orob2b_order_type']['website'] = $this->getWebsite()->getId();

        return $submittedData;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedData($form, $orderAccount, $lineItems, $discountItems)
    {
        $updatedData = parent::getUpdatedData(
            $form,
            $orderAccount,
            $lineItems,
            $discountItems
        );
        $updatedData['orob2b_order_type']['website'] = $this->getWebsite()->getId();

        return $updatedData;
    }


    /**
     * @return Website
     */
    public function getWebsite()
    {
        if (!$this->website) {
            $this->website = $this->client->getContainer()->get('orob2b_website.manager')->getCurrentWebsite();
        }

        return $this->website;
    }
}
