<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

/**
 * @dbIsolation
 */
class AccountControllersTest extends WebTestCase
{
    /** @var $accountUser AccountUser */
    protected $accountUser;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders',
            ]
        );
        $manager = $this->client->getContainer()->get('doctrine')->getManagerForClass(
            'OroB2BAccountBundle:AccountUser'
        );
        $this->accountUser = $manager->getRepository('OroB2BAccountBundle:AccountUser')->findOneBy(
            ['username' => LoadOrders::ACCOUNT_USER]
        );
    }

    public function testAccountViewAndGrid()
    {
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_view', ['id' => $this->accountUser->getAccount()->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertContains('account-orders-grid', $content);

        $response = $this->client->requestGrid(
            [
                'gridName' => 'account-orders-grid',
                'account-orders-grid[account_id]' => $this->accountUser->getAccount()->getId(),
            ]
        );
        $this->checkDatagridResponse($response);
    }

    public function testAccountUserViewAndGrid()
    {
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_account_user_view', ['id' => $this->accountUser->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertContains('account-user-orders-grid', $content);

        $response = $this->client->requestGrid(
            [
                'gridName' => 'account-user-orders-grid',
                'account-user-orders-grid[account_user_id]' => $this->accountUser->getId(),
            ]
        );
        $this->checkDatagridResponse($response);
    }

    /**
     * @param Response $response
     */
    protected function checkDatagridResponse(Response $response)
    {
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertContains(sprintf('$%.2F', LoadOrders::SUBTOTAL), $result['data'][0]['subtotal']);
    }
}
