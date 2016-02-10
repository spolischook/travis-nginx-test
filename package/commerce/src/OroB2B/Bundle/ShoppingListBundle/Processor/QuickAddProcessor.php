<?php

namespace OroB2B\Bundle\ShoppingListBundle\Processor;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Component\PhpUtils\ArrayUtil;

use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use OroB2B\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;

class QuickAddProcessor implements ComponentProcessorInterface
{
    const NAME = 'orob2b_shopping_list_quick_add_processor';

    /** @var ShoppingListLineItemHandler */
    protected $shoppingListLineItemHandler;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var MessageGenerator */
    protected $messageGenerator;

    /** @var string */
    protected $productClass;

    /**
     * @param ShoppingListLineItemHandler $shoppingListLineItemHandler
     * @param ManagerRegistry $registry
     * @param MessageGenerator $messageGenerator
     */
    public function __construct(
        ShoppingListLineItemHandler $shoppingListLineItemHandler,
        ManagerRegistry $registry,
        MessageGenerator $messageGenerator
    ) {
        $this->shoppingListLineItemHandler = $shoppingListLineItemHandler;
        $this->registry = $registry;
        $this->messageGenerator = $messageGenerator;
    }

    /** {@inheritdoc} */
    public function process(array $data, Request $request)
    {
        if (empty($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY]) ||
            !is_array($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY])
        ) {
            return;
        }

        $shoppingListId = null;
        if (!empty($data[ProductDataStorage::ADDITIONAL_DATA_KEY])) {
            $shoppingListId = (int) $data[ProductDataStorage::ADDITIONAL_DATA_KEY] ? : null;
        }

        $data = $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY];

        $shoppingList = $this->shoppingListLineItemHandler->getShoppingList($shoppingListId);

        $productSkus = ArrayUtil::arrayColumn($data, ProductDataStorage::PRODUCT_SKU_KEY);
        $productIds = $this->getProductRepository()->getProductsIdsBySku($productSkus);
        $productSkuQuantities = array_combine(
            $productSkus,
            ArrayUtil::arrayColumn($data, ProductDataStorage::PRODUCT_QUANTITY_KEY)
        );
        $productIdsQuantities = array_combine($productIds, $productSkuQuantities);

        /** @var Session $session */
        $session = $request->getSession();
        $flashBag = $session->getFlashBag();

        try {
            $entitiesCount = $this->shoppingListLineItemHandler->createForShoppingList(
                $shoppingList,
                array_values($productIds),
                $productIdsQuantities
            );

            $flashBag->add(
                'success',
                $this->messageGenerator->getSuccessMessage($shoppingList->getId(), $entitiesCount)
            );
        } catch (AccessDeniedException $e) {
            $flashBag->add('error', $this->messageGenerator->getFailedMessage());
        }
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return self::NAME;
    }

    /** {@inheritdoc} */
    public function isValidationRequired()
    {
        return true;
    }

    /** @return ProductRepository */
    protected function getProductRepository()
    {
        return $this->registry->getManagerForClass($this->productClass)->getRepository($this->productClass);
    }

    /** @param string $productClass */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
    }

    /** {@inheritdoc} */
    public function isAllowed()
    {
        return $this->shoppingListLineItemHandler->isAllowed();
    }
}
