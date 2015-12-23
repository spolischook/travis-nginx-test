<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Model;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Component\Testing\WebTestCase;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData as AccountLoadAccountUserData;

/**
 * @dbIsolation
 */
class ProductVisibilityQueryBuilderModifierTest extends WebTestCase
{
    const VISIBILITY_SYSTEM_CONFIGURATION_PATH = 'oro_b2b_account.product_visibility';

    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    protected $modifier;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenStorage;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(AccountLoadAccountUserData::EMAIL, AccountLoadAccountUserData::PASSWORD)
        );

        $this->loadFixtures([
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
        ]);

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $this->modifier = new ProductVisibilityQueryBuilderModifier(
            $this->configManager,
            $this->tokenStorage,
            $this->getContainer()->get('orob2b_website.manager')
        );
    }

    /**
     * @dataProvider modifyDataProvider
     *
     * @param string $configValue
     * @param string|null $user
     * @param array $expectedData
     */
    public function testModify($configValue, $user, $expectedData)
    {
        if ($user) {
            $user = $this->getReference($user);
        }
        $this->setupTokenStorage($user);

        $queryBuilder = $this->getProductRepository()->createQueryBuilder('p')
            ->select('p.sku')->orderBy('p.sku');

        $this->configManager->expects($this->any())
            ->method('get')
            ->with(static::VISIBILITY_SYSTEM_CONFIGURATION_PATH)
            ->willReturn($configValue);

        $this->modifier->setVisibilitySystemConfigurationPath(static::VISIBILITY_SYSTEM_CONFIGURATION_PATH);

        $this->modifier->modify($queryBuilder);

        $this->assertEquals($expectedData, array_map(function ($productData) {
            return $productData['sku'];
        }, $queryBuilder->getQuery()->execute()));
    }

    /**
     * @return array
     */
    public function modifyDataProvider()
    {
        return [
            'config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => AccountLoadAccountUserData::EMAIL,
                'expectedData' => [
                    'product.1',
                    'product.5',
                    'product.6',
                ]
            ],
            'config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => AccountLoadAccountUserData::EMAIL,
                'expectedData' => [
                    'product.1',
                    'product.5',
                    'product.6',
                ]
            ],
            'anonymous config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => null,
                'expectedData' => [
                    'product.1',
                    'product.2',
                    'product.3',
                    'product.5',
                    'product.6',
                ]
            ],
            'anonymous config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => null,
                'expectedData' => [
                    'product.2',
                    'product.3',
                    'product.5',
                    'product.6',
                ]
            ],
            'group config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => AccountLoadAccountUserData::GROUP2_EMAIL,
                'expectedData' => [
                    'product.1',
                    'product.3',
                    'product.6',
                ]
            ],
            'account without group and config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => AccountLoadAccountUserData::ORPHAN_EMAIL,
                'expectedData' => [
                    'product.1',
                    'product.2',
                    'product.3',
                    'product.4',
                    'product.5',
                    'product.6',
                ]
            ],
        ];
    }

    public function testVisibilitySystemConfigurationPathNotSet()
    {
        $queryBuilder = $this->getProductRepository()->createQueryBuilder('p')
            ->select('p.sku')->orderBy('p.sku');

        $message = sprintf('%s::visibilitySystemConfigurationPath not configured', get_class($this->modifier));
        $this->setExpectedException('\LogicException', $message);
        $this->modifier->modify($queryBuilder);
    }

    /**
     * @param object|null $user
     */
    protected function setupTokenStorage($user = null)
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);
    }

    /**
     * @return \OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository
     */
    protected function getProductRepository()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BProductBundle:Product')
            ->getRepository('OroB2BProductBundle:Product');
    }
}
