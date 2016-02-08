<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\JsTree;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Component\Tree\Handler\AbstractTreeHandler;
use OroB2B\Component\Tree\Test\AbstractTreeHandlerTestCase;

/**
 * @dbIsolation
 */
class AccountTreeHandlerTest extends AbstractTreeHandlerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getFixtures()
    {
        return 'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts';
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandlerId()
    {
        return 'orob2b_account.account_tree_handler';
    }

    /**
     * @dataProvider createDataProvider
     * @param string|null $entityReference
     * @param bool $includeRoot
     * @param array $expectedData
     */
    public function testCreateTree($entityReference, $includeRoot, array $expectedData)
    {
        $entity = null;
        if ($entityReference !== null) {
            /** @var Account $entity */
            $entity = $this->getReference($entityReference);
        }

        $expectedData = array_reduce($expectedData, function ($result, $data) {
            /** @var Account $entity */
            $entity = $this->getReference($data['entity']);
            $data['id'] = $entity->getId();
            $data['text'] = $entity->getName();
            if ($data['parent'] !== AbstractTreeHandler::ROOT_PARENT_VALUE) {
                $data['parent'] = $this->getReference($data['parent'])->getId();
            }
            unset($data['entity']);
            $result[$data['id']] = $data;
            return $result;
        }, []);

        $this->assertTreeCreated($expectedData, $entity, $includeRoot);
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            [
                'root' => 'account.level_1.2',
                'includeRoot' => false,
                'expectedData' => [
                    [
                        'entity' => 'account.level_1.2.1',
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => true
                        ],
                    ],
                    [
                        'entity' => 'account.level_1.2.1.1',
                        'parent' => 'account.level_1.2.1',
                        'state' => [
                            'opened' => false
                        ],
                    ],
                ]
            ],
            [
                'root' => 'account.level_1.2',
                'includeRoot' => true,
                'expectedData' => [
                    [
                        'entity' => 'account.level_1.2',
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => true
                        ],
                    ],
                    [
                        'entity' => 'account.level_1.2.1',
                        'parent' => 'account.level_1.2',
                        'state' => [
                            'opened' => true
                        ],
                    ],
                    [
                        'entity' => 'account.level_1.2.1.1',
                        'parent' => 'account.level_1.2.1',
                        'state' => [
                            'opened' => false
                        ],
                    ],
                ]
            ],
        ];
    }
}
