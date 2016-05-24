<?php
/**
 * Created by PhpStorm.
 * User: vdenchyk
 * Date: 20.05.16
 * Time: 20:43
 */

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

use Symfony\Component\HttpFoundation\Session\Session;

class ProductPrimaryUnitPrecisionListener
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var Session
     */
    protected $request;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param Session $session
     */
    public function __construct(DoctrineHelper $doctrineHelper, Session $session)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->session = $session;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->updateProductUnitPrecisionRelation($args);
    }

    /**
     * Set workflow item entity ID
     *
     * @param LifecycleEventArgs $args
     */
    protected function updateProductUnitPrecisionRelation(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof ProductUnitPrecision) {
            $product = $entity->getProduct();
            if (!$product) {
                throw new \Exception('PrimaryUnitPrecision does not have Product');
            }

            $primaryUnitPrecisionId = $entity->getId();
            $actualPrimaryCode = $entity->getUnit()->getCode();
            $expectedPrimaryCode = $this->session->get('primaryUnitPrecisionCode');

            if ($actualPrimaryCode == $expectedPrimaryCode) {
                $unitOfWork = $args->getEntityManager()->getUnitOfWork();
                $unitOfWork->scheduleExtraUpdate($product, [
                    'primaryUnitPrecisionId' => [
                        null,
                        $primaryUnitPrecisionId
                    ]
                ]);
                $this->session->remove('primaryUnitPrecisionCode');
            }
        }
    }

}