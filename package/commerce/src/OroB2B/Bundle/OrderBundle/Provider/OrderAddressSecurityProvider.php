<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;

class OrderAddressSecurityProvider
{
    const MANUAL_EDIT_ACTION = 'orob2b_order_address_%s_allow_manual';

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var OrderAddressProvider */
    protected $orderAddressProvider;

    /** @var string */
    protected $accountAddressClass;

    /** @var string */
    protected $accountUserAddressClass;

    /**
     * @param SecurityFacade $securityFacade
     * @param OrderAddressProvider $orderAddressProvider
     * @param string $accountAddressClass
     * @param string $accountUserAddressClass
     */
    public function __construct(
        SecurityFacade $securityFacade,
        OrderAddressProvider $orderAddressProvider,
        $accountAddressClass,
        $accountUserAddressClass
    ) {
        $this->securityFacade = $securityFacade;
        $this->orderAddressProvider = $orderAddressProvider;
        $this->accountAddressClass = $accountAddressClass;
        $this->accountUserAddressClass = $accountUserAddressClass;
    }

    /**
     * @param Order $order
     * @param string $type
     *
     * @return bool
     */
    public function isAddressGranted(Order $order, $type)
    {
        return $this->isAccountAddressGranted($type, $order->getAccount()) ||
            $this->isAccountUserAddressGranted($type, $order->getAccountUser());
    }

    /**
     * @param string $type
     * @param Account $account
     *
     * @return bool
     */
    public function isAccountAddressGranted($type, Account $account = null)
    {
        if ($this->isManualEditGranted($type)) {
            return true;
        }

        $hasPermissions = $this->securityFacade->isGranted(
            $this->getClassPermission('VIEW', $this->accountAddressClass)
        );

        if (!$hasPermissions) {
            return false;
        }

        if (!$account) {
            return false;
        }

        return (bool)$this->orderAddressProvider->getAccountAddresses($account, $type);
    }

    /**
     * @param string $type
     * @param AccountUser $accountUser
     *
     * @return bool
     */
    public function isAccountUserAddressGranted($type, AccountUser $accountUser = null)
    {
        if ($this->isManualEditGranted($type)) {
            return true;
        }

        $hasPermissions = $this->securityFacade
                ->isGranted($this->getClassPermission('VIEW', $this->accountUserAddressClass))
            && $this->securityFacade->isGranted($this->getTypedPermission($type));

        if (!$hasPermissions) {
            return false;
        }

        if (!$accountUser) {
            return false;
        }

        return (bool)$this->orderAddressProvider->getAccountUserAddresses($accountUser, $type);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function getTypedPermission($type)
    {
        OrderAddressProvider::assertType($type);

        if ($type === AddressType::TYPE_SHIPPING) {
            return $this->getPermission(OrderAddressProvider::ADDRESS_SHIPPING_ACCOUNT_USER_USE_ANY);
        }

        return $this->getPermission(OrderAddressProvider::ADDRESS_BILLING_ACCOUNT_USER_USE_ANY);
    }

    /**
     * @param string $permission
     * @param string $className
     * @return string
     */
    protected function getClassPermission($permission, $className)
    {
        return sprintf('%s;entity:%s', $permission, $className);
    }

    /**
     * @param string $permission
     * @return string
     */
    protected function getPermission($permission)
    {
        if (!$this->securityFacade->getLoggedUser() instanceof AccountUser) {
            $permission .= OrderAddressProvider::ADMIN_ACL_POSTFIX;
        }

        return $permission;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function isManualEditGranted($type)
    {
        OrderAddressProvider::assertType($type);

        $permission = sprintf(self::MANUAL_EDIT_ACTION, $type);

        return $this->securityFacade->isGranted($this->getPermission($permission));
    }
}
