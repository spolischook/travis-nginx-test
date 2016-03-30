<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

/**
 * @dbIsolation
 */
class AccountUserFrontendActionsTest extends AbstractAccountUserActionsTest
{
    const EMAIL = 'account.user2@test.com';

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getAccountUserEnableOperationName()
    {
        return 'orob2b_account_frontend_accountuser_enable';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAccountUserDisableOperationName()
    {
        return 'orob2b_account_frontend_accountuser_disable';
    }

    /**
     * {@inheritdoc}
     */
    protected function executeOperation(AccountUser $accountUser, $operationName)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_api_frontend_action_execute_operations',
                [
                    'operationName' => $operationName,
                    'route' => 'orob2b_account_frontend_account_user_view',
                    'entityId' => $accountUser->getId(),
                    'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\AccountUser'
                ]
            )
        );
    }
}
