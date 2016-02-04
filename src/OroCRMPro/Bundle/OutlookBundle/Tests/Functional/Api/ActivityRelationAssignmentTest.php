<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Functional\Api;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ActivityRelationAssignmentTest extends WebTestCase
{
    /** @var User */
    protected $user;

    /** @var User */
    protected $adminUser;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(
            [
                'OroCRMPro\Bundle\OutlookBundle\Tests\Functional\DataFixtures\LoadOutlookUser'
            ]
        );

        $this->user = $this->getReference('outlook_user');
    }

    public function testOne()
    {
        // Create new email
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_email'),
            $this->getEmailData(),
            [],
            $this->getWsseHeader()
        );
        $email = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $this->assertArrayHasKey('id', $email);

        //Check for no activity relations
        $url = $this->getUrl('oro_api_get_activity_relations', ['activity' => 'emails', 'id' => $email['id']]);
        $this->client->request('GET', $url, [], [], $this->getWsseHeader());
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(0, $entities);

        //Add activity relation to admin user
        $url = $this->getUrl('oro_api_post_activity_relation', ['activity' => 'emails', 'id' => $email['id']]);
        $this->client->request(
            'POST',
            $url,
            ['targets' => [['id' => $this->getAdminUser()->getId(), 'entity' => 'user']]],
            [],
            $this->getWsseHeader()
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        //Check that the activity relation was added
        $url = $this->getUrl('oro_api_get_activity_relations', ['activity' => 'emails', 'id' => $email['id']]);
        $this->client->request('GET', $url, [], [], $this->getWsseHeader());
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);

        //Remove activity relation
        $url = $this->getUrl('oro_api_delete_activity_relation', [
            'activity' => 'emails',
            'id'       => $email['id'],
            'entity'   => 'users',
            'entityId' => $this->getAdminUser()->getId()
        ]);
        $this->client->request('DELETE', $url, [], [], $this->getWsseHeader());
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        //Check that the activity relation have been successfully removed
        $url = $this->getUrl('oro_api_get_activity_relations', ['activity' => 'emails', 'id' => $email['id']]);
        $this->client->request('GET', $url, [], [], $this->getWsseHeader());
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(0, $entities);
    }

    /**
     * @return array
     */
    protected function getWsseHeader()
    {
        return $this->generateWsseAuthHeader($this->user->getUsername(), $this->user->getUsername());
    }

    /**
     * @return User
     */
    protected function getAdminUser()
    {
        if (!$this->adminUser) {
            $this->adminUser = $this->getContainer()->get('oro_user.manager')->findUserByUsername('admin');
        }

        return $this->adminUser;
    }

    /**
     * @return array
     */
    protected function getEmailData()
    {
        $rand = mt_rand(0, 1000);

        return [
            'folders'    => [
                ['fullName' => 'INBOX \ Test Folder', 'name' => 'Test Folder', 'type' => 'inbox']
            ],
            'subject'    => 'New does not exists email subject',
            'messageId'  => 'new.message.' . $rand . '@outlook.func-test',
            'from'       => $this->user->getEmail(),
            'to'         => ['"Address 1" <emaildoesnotexists' . $rand . '@example.com>'],
            'importance' => 'low',
            'body'       => 'Test body',
            'bodyType'   => 'text',
            'receivedAt' => '2016-01-01 12:00:00'
        ];
    }
}
