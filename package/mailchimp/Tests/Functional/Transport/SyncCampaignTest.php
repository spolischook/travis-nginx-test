<?php

namespace OroCRM\Bundle\MailChimpBundle\Tests\Functional\Transport;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\MailChimpBundle\Provider\Transport\MailChimpTransport;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class SyncCampaignTest extends WebTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $clientFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $apiClient;

    protected function setUp()
    {
        $this->initClient();
        $this->clientFactory = $this->getMockBuilder(
            'OroCRM\Bundle\MailChimpBundle\Provider\Transport\MailChimpClientFactory'
        )
            ->disableOriginalConstructor()
            ->setMethods(
                ['create']
            )
            ->getMock();
        $this->apiClient = $this->getMockBuilder('OroCRM\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient')
            ->disableOriginalConstructor()
            ->setMethods(
                ['export', 'getLists', 'getListMergeVars', 'getCampaigns']
            )
            ->getMock();
        $this->clientFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->apiClient));

        $transport = new MailChimpTransport($this->clientFactory, $this->getContainer()->get('doctrine'));
        $this->getContainer()->set('orocrm_mailchimp.transport.integration_transport', $transport);
        $this->loadFixtures(['OroCRM\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadStaticSegmentData']);
    }

    /**
     * @dataProvider commandCampaignOptionsProvider
     * @param string $commandName
     * @param array $params
     * @param string $entity
     * @param array $data
     * @param string $assertMethod
     * @param int $assertCount
     * @param array $expectedList
     */
    public function testSyncCampaign(
        $commandName,
        array $params,
        $entity,
        $data,
        $assertMethod,
        $assertCount,
        $expectedList
    ) {
        $this->apiClient->expects($this->once())
            ->method('getCampaigns')
            ->will($this->returnValue($data));

        if (isset($params['--integration-id'])) {
            $params['--integration-id'] = (string)$this->getReference(
                'mailchimp:channel_' . $params['--integration-id']
            )->getId();
        }
        $result = $this->runCommand($commandName, $params);
        foreach ($expectedList as $expected) {
            $this->assertContains($expected, $result);
        }
        if ($assertMethod) {
            $listRepo = $this->getContainer()->get('doctrine')->getRepository('OroCRMMailChimpBundle:' . $entity);
            $list = $listRepo->findAll();
            $this->$assertMethod($assertCount, count($list));
        }
    }


    /**
     * @return array
     */
    public function commandCampaignOptionsProvider()
    {
        $results = [];
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'campaignResponses';
        $apiData = $this->getApiRequestsData($path);

        foreach ($apiData as $test => $data) {
            $results[$test] = [
                'commandName' => 'oro:cron:integration:sync',
                'params' => ['--integration-id' => '1', '--connector' => 'campaign'],
                'entity' => 'Campaign',
                'data' => $data['response'],
                'assertMethod' => 'assertEquals',
                'assertCount' => count($data['response']['data']),
                'expectedContent' => [
                    'Run sync for "mailchimp1" integration.',
                    'Start processing "campaign" connector',
                    'invalid entities: [0]',
                    'processed [' . count($data['response']['data']) . ']',
                    'deleted [0]',
                    'updated [0]',
                    'read [' . count($data['response']['data']) . ']',
                    'added [' . count($data['response']['data']) . ']',
                ]
            ];
        }

        return $results;
    }
}
