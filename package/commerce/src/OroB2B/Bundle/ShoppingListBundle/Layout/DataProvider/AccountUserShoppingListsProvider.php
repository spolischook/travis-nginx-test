<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\Common\Collections\Criteria;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

class AccountUserShoppingListsProvider extends AbstractServerRenderDataProvider
{
    const DATA_SORT_BY_UPDATED = 'updated';

    /**
     * @var FormAccessor
     */
    protected $data;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var string
     */
    protected $shoppingListClass;

    /**
     * @var ShoppingListTotalManager
     */
    protected $totalManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param SecurityFacade $securityFacade
     * @param RequestStack $requestStack
     * @param ShoppingListTotalManager $totalManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade,
        RequestStack $requestStack,
        ShoppingListTotalManager $totalManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->securityFacade = $securityFacade;
        $this->requestStack = $requestStack;
        $this->totalManager = $totalManager;
    }

    /**
     * @param string $shoppingListClass
     */
    public function setShoppingListClass($shoppingListClass)
    {
        $this->shoppingListClass = $shoppingListClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if (!$this->data) {
            $this->data = $this->getAccountUserShoppingLists();
        }

        return $this->data;
    }

    /**
     * @return array|null
     * @throws \InvalidArgumentException
     */
    protected function getAccountUserShoppingLists()
    {
        $accountUser = $this->securityFacade->getLoggedUser();
        $shoppingLists = [];
        if ($accountUser) {
            /** @var ShoppingListRepository $shoppingListRepository */
            $shoppingListRepository = $this->doctrineHelper->getEntityRepositoryForClass($this->shoppingListClass);

            /** @var ShoppingList[] $shoppingLists */
            $shoppingLists = $shoppingListRepository->findByUser($accountUser, $this->getSortOrder());
            $this->totalManager->setSubtotals($shoppingLists, false);
        }

        return ['shoppingLists' => $shoppingLists];
    }

    /**
     * @return string
     */
    protected function getSortOrder()
    {
        $sortOrder = [];
        $request = $this->requestStack->getCurrentRequest();
        $sort = $request ? $request->get('shopping_list_sort') : self::DATA_SORT_BY_UPDATED;

        switch ($sort) {
            case self::DATA_SORT_BY_UPDATED:
                $sortOrder['list.updatedAt'] = Criteria::DESC;
                break;
            default:
                $sortOrder['list.id'] = Criteria::ASC;
        }

        return $sortOrder;
    }
}
