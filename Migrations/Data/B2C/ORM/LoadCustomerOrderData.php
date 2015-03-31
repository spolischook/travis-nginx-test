<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

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
            __NAMESPACE__ . '\\LoadCustomerData',
            __NAMESPACE__ . '\\LoadCustomerCartData',
            __NAMESPACE__ . '\\LoadCustomerCartItemData',
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'orders' => $this->loadData('magento/orders.csv'),
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
            $customer = $cart->getCustomer();
            $order = new Order();

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

            $manager->persist($customer);
            $manager->persist($order);
        }
        $manager->flush();
    }
}
