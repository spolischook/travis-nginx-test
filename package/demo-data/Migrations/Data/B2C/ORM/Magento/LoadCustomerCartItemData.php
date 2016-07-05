<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Magento;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\MagentoBundle\Entity\CartItem;

use OroCRMPro\Bundle\DemoDataBundle\EventListener\CartSubscriber;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadCustomerCartItemData extends AbstractFixture implements OrderedFixtureInterface
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
     * @return array
     */
    public function getData()
    {
        return [
            'carts_items' => $this->loadData('magento/carts_items.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $subscriber = new CartSubscriber($this->container->get('orocrm_magento.manager.abandoned_shopping_cart_flow'));
        $this->em->getEventManager()->addEventSubscriber($subscriber);

        $data = $this->getData();

        foreach ($data['carts_items'] as $cartItemData) {
            $cart     = $this->getCartReference($cartItemData['cart uid']);
            $cartItem = new CartItem();
            $this->setObjectValues($cartItem, $cartItemData);

            $taxAmount = $cartItemData['price'] * $cartItem->getTaxAmount();
            $total     = $cartItemData['price'] + $taxAmount;

            $cartItem->setProductId(rand(1, 100));
            $cartItem->setFreeShipping((string)0);
            $cartItem->setIsVirtual(0);

            $cartItem->setRowTotal($total);
            $cartItem->setPriceInclTax($total);
            $cartItem->setTaxAmount($taxAmount);

            $cartItem->setCreatedAt($this->generateUpdatedDate($cart->getCreatedAt()));
            $cartItem->setUpdatedAt($this->generateUpdatedDate($cartItem->getCreatedAt()));
            $cartItem->setCart($cart);
            $cartItem->setOwner($cart->getOrganization());

            $cart->getCartItems()->add($cartItem);
            $cart->setItemsQty($cart->getItemsQty() + $cartItemData['qty']);
            $cart->setItemsCount($cart->getItemsCount() + 1);

            $cart->setGrandTotal($cart->getGrandTotal() + $total);
            $cart->setSubTotal($cart->getGrandTotal());
            $cart->setTaxAmount($cart->getTaxAmount() + $taxAmount);

            $manager->persist($cart);
        }
        $manager->flush();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 31;
    }
}
