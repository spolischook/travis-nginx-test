UPGRADE FROM 1.9 to 1.10
=======================
####OroCRMProOutlookBundle
- Renamed config group from `integrations_outlook_addin` to `outlook_addin`
- Bundle has been upgraded to Symfony 2.7


####OroProPlatformBundle
- Bundle has been upgraded to Symfony 2.7


####OroProElasticSearchBundle
- The signature of `OroPro\Bundle\ElasticSearchBundle\Engine\ElasticSearch::reindex` was changed from `reindex($class = null)` to `reindex($class = null, $offset = null, $limit = null)`. This changes affected on `oro_search.search.engine` service in DIC.
- The signature of `OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where\AbstractWherePartBuilder` was changed

Before:

```
abstract public function buildPart($field, $type, $operator, $value, $keyword, array $request);
```

After:

```
abstract public function buildPart($field, $type, $operator, $value);
```
This changes affected on next child classes `OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where\ContainsWherePartBuilder`, ``OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where\EqualsWherePartBuilder`, `OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where\InWherePartBuilder`, `OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where\RangeWherePartBuilder` and `oropro_elasticsearch.request_builder.where` service in DIC.
- Bundle has been upgraded to Symfony 2.7


####OroProEwsBundle
- Bundle has been upgraded to Symfony 2.7
- The signature of `OroPro\Bundle\EwsBundle\Provider\EwsEmailBodyLoader::__construct` was changed
 
Before:

```
use OroPro\Bundle\EwsBundle\Connector\EwsConnector;

class EwsEmailBodyLoader implements EmailBodyLoaderInterface
{
    public function __construct(EwsConnector $connector)
```

After:

```
use OroPro\Bundle\EwsBundle\Connector\EwsConnector;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class EwsEmailBodyLoader implements EmailBodyLoaderInterface
{
    public function __construct(EwsConnector $connector, ConfigManager $configManager)
```
This changes affected on `oro_pro_ews.email_body_loader` service in DIC.


####OroProSecurityBundle
- Bundle has been upgraded to Symfony 2.7
