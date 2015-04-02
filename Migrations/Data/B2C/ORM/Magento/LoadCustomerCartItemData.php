<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Magento;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroCRM\Bundle\MagentoBundle\Entity\CartItem;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadCustomerCartItemData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'cart uid',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadCustomerCartData',
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'carts_items' => $this->loadData('magento/carts_items.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['carts_items'] as $cartItemData) {

            $cart = $this->getCartReference($cartItemData['cart uid']);
            $cartItem = new CartItem();

            $taxAmount = $cartItemData['price'] * $cartItemData['taxpercent'];
            $total = $cartItemData['price'] + $taxAmount;

            $cartItem->setProductId(rand(1, 100));
            $cartItem->setFreeShipping((string)0);
            $cartItem->setIsVirtual(0);

            $cartItem->setRowTotal($total);
            $cartItem->setPriceInclTax($total);
            $cartItem->setTaxAmount($taxAmount);

            $cartItem->setSku($cartItemData['sku']);
            $cartItem->setProductType($cartItemData['producttype']);
            $cartItem->setName($cartItemData['name']);
            $cartItem->setQty(1);
            $cartItem->setPrice($cartItemData['price']);
            $cartItem->setDiscountAmount(0);
            $cartItem->setTaxPercent($cartItemData['taxpercent']);
            $cartItem->setCreatedAt($this->generateUpdatedDate($cart->getCreatedAt()));
            $cartItem->setUpdatedAt($this->generateUpdatedDate($cartItem->getCreatedAt()));
            $cartItem->setCart($cart);

            $cart->getCartItems()->add($cartItem);
            $cart->setItemsQty($cart->getItemsQty() + $cartItemData['qty']);
            $cart->setItemsCount($cart->getItemsCount() + 1);

            $cart->setSubTotal($total);
            $cart->setGrandTotal($cart->getGrandTotal() + $total);
            $cart->setTaxAmount($cart->getTaxAmount() + $taxAmount);

            $manager->persist($cart);

        }
        $manager->flush();
    }
}
