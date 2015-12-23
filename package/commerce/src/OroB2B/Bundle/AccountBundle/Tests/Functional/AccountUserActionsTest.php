<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;

/**
 * @dbIsolation
 */
class AccountUserActionsTest extends AbstractAccountUserActionsTest
{
    const EMAIL = LoadAccountUserData::EMAIL;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getAccountUserEnableActionName()
    {
        return 'orob2b_account_action_accountuser_enable';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAccountUserDisableActionName()
    {
        return 'orob2b_account_action_accountuser_disable';
    }

    /**
     * @param AccountUser $accountUser
     * @param string $actionName
     */
    protected function executeAction(AccountUser $accountUser, $actionName)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_action_execute_actions',
                [
                    'actionName' => $actionName,
                    'route' => 'orob2b_account_account_user_view',
                    'entityId' => $accountUser->getId(),
                    'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\AccountUser'
                ]
            )
        );
    }
}
