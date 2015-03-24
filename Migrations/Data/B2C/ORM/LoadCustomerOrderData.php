<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroCRM\Bundle\MagentoBundle\Entity\Cart;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;
use OroCRM\Bundle\MagentoBundle\Entity\Order;
use OroCRM\Bundle\MagentoBundle\Entity\OrderItem;

class LoadCustomerOrderData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadCustomerData'
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'orders' => $this->loadData('orders.csv')
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['orders'] as $orderData) {
            $cart = $this->getCartReference($orderData['cart uid']);
            $order = new Order();

            /**
             * Store/Integration/Cart(Customer,Store)
             */
            $order->setOrganization($cart->getOrganization());
            $order->setChannel($cart->getChannel());
            $order->setCustomer($cart->getCustomer());
            $order->setOwner($cart->getCustomer()->getOwner());
            $order->setStatus($orderData['status']);
            $order->setStore($cart->getStore());
            $order->setStoreName($cart->getStore()->getName());
            $order->setIsGuest(0);
            $order->setIncrementId((string)$orderData['uid']);
            $order->setCreatedAt($this->generateUpdatedDate($cart->getCreatedAt()));
            $order->setUpdatedAt($this->generateUpdatedDate($order->getCreatedAt()));
            $order->setCart($cart);
            $order->setCurrency($cart->getBaseCurrencyCode());
            $order->setTotalAmount($cart->getGrandTotal());
            $order->setTotalInvoicedAmount($cart->getGrandTotal());
            $order->setDataChannel($cart->getDataChannel());

            if ($orderData['status'] == 'Completed') {
                $order->setTotalPaidAmount($cart->getGrandTotal());
            }
            $order->setSubtotalAmount($cart->getSubTotal());
            $order->setShippingAmount(rand(5, 10));
            $order->setPaymentMethod($orderData['paymentmethod']);
            $order->setPaymentDetails($orderData['paymentmethoddetails']);
            $order->setShippingMethod('flatrate_flatrate');

            $cartItems = $cart->getCartItems();
            $orderItems = [];
            foreach ($cartItems as $cartItem) {
                $orderItem = new OrderItem();
                $orderItem->setOriginId($cartItem->getOriginId());
                $orderItem->setOrder($order);
                $orderItem->setTaxAmount($cartItem->getTaxAmount());
                $orderItem->setTaxPercent($cartItem->getTaxPercent());
                $orderItem->setRowTotal($cartItem->getRowTotal());
                $orderItem->setProductType($cartItem->getProductType());
                $orderItem->setIsVirtual((bool)$cartItem->getIsVirtual());
                $orderItem->setQty($cartItem->getQty());
                $orderItem->setSku($cartItem->getSku());
                $orderItem->setPrice($cartItem->getPrice());
                $orderItem->setOriginalPrice($cartItem->getPrice());
                $orderItem->setName($cartItem->getName());
                $orderItems[] = $orderItem;

                $manager->persist($orderItem);
            }
            $order->setItems($orderItems);

            $manager->persist($order);
        }
        $manager->flush();
    }

    /**
     * @param $uid
     * @return Customer
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getCustomerReference($uid)
    {
        $reference = 'Customer:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @return Cart
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getCartReference($uid)
    {
        $reference = 'Cart:' . $uid;
        return $this->getReferenceByName($reference);
    }
}
