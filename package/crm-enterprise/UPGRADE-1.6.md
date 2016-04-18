UPGRADE FROM 1.5.1 to 1.6
=======================

####OroCRMProFusionChartsBundle:
- `Model\Data\Transformer\MultiLineDataTransformer` has been removed


####OroProEwsBundle:
- `Manager\EwsEmailManager\Email` now extends from `Oro\Bundle\EmailBundle\Model\EmailHeader`
- `Provider\EwsEmailIterator` no longer extends from `AbstractBatchIterator`, currently implements `\Iterator`
- `Provider\Iterator\AbstractBatchIterator` has been removed

####OroProOrganizationBundle
- Added parameter `DoctrineHelper $doctrineHelper` to constructor of `OroPro\Bundle\OrganizationBundle\Form\Extension\DynamicFieldsExtension` class
