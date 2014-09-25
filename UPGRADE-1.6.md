UPGRADE FROM 1.5.1 to 1.6
=======================

##OroCRMPro:

####FusionCharts:
- `Model\Data\Transformer\CampaignMultiLineDataTransformer` has been added
- `Model\Data\Transformer\MultiSetDataTransformer` has been added
- `Model\Data\Transformer\MultiLineDataTransformer` has been removed
- `multiline_chart` support has been added

####OutlookBundle has been added

##OroPro:

#### ElasticSearchBundle has been added

####EwsBundle:
- `Manager\EwsEmailManager\Email` now extends from `Oro\Bundle\EmailBundle\Model\EmailHeader`
- `Provider\EwsEmailIterator` no longer extends from `AbstractBatchIterator`, currently implements `\Iterator`
- `Provider\Iterator\AbstractBatchIterator` has been removed

####OrganizationBundle has been added:
- User is able to create new or edit all existing organizations (System > User Management > Organizations)
