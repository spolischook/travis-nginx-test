UPGRADE FROM 1.5 to 1.6
=======================

####OroEntityBundle:

##`Oro\Bundle\EntityBundle\Provider\EntityFieldProvider`
- `setVirtualRelationProvider` was added
- `addFields` method signature changed
    `EntityManager $em` removed
    `$withVirtualFields` removed
- `addRelations` method signature changed
    `EntityManager $em` removed
- `addUnidirectionalRelations` method signature changed
    `EntityManager $em` removed
- `addVirtualFields` method signature changed
    `ClassMetadata $metadata` => `$className`
- `isIgnoredField` method signature changed
    `ClassMetadataInfo $metadata` => `ClassMetadata $metadata`
- `getUnidirectionalRelations` method signature changed
    `EntityManager $em` removed
####OroOrganizationBundle:
- Removed Twig/OrganizationExtension as organization selector has been removed from login screen

####OroSearchBundle:
 - `Oro\Bundle\SearchBundle\Query\Result\Item` entity field `recordText` marked as deprecated and will be removed in 1.7 version.
 - `Oro\Bundle\SearchBundle\Engine\Orm\BaseDriver::search` return an array filled with array representation of `Oro\Bundle\SearchBundle\Entity\Item`

####OroUIBundle:
 - "oroui/js/loading-mask" module marked as deprecated and will be removed in 1.8 version. Use "oroui/js/app/views/loading-mask-view" module instead.

####BatchBundle:
- `Oro\Bundle\BatchBundle\ORM\QueryBuilder\QueryBuilderTools` method signature changed
    `prepareFieldAliases($selects)` to `prepareFieldAliases(array $selects)`
    `__construct(array $selects = null, $joins = null)` to `__construct(array $selects = null, array $joins = null)`
    
####OroUserBundle:
- Added `oro_user_organization_acl_select` form type which selects users by assigned organizations, not by owned organization.    
- Added `oro_user_organization_acl_multiselect` multi select form type which selects users by assigned organizations, not by owned organization.   
