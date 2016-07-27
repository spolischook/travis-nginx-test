UPGRADE FROM 1.7 to 1.8
=======================
####OroCRMProOutlookBundle
- Added new REST API that contains next controllers
    * `OroCRMPro\Bundle\OutlookBundle\Controller\Api\Rest\AuditController`
    * `OroCRMPro\Bundle\OutlookBundle\Controller\Api\Rest\ConfigurationController`
    * `OroCRMPro\Bundle\OutlookBundle\Controller\Api\Rest\WorkflowDefinitionController`
- Removed config field `oro_crm_pro_outlook.addin_download`


####OroProCommentBundle
- Added bundle that allow to add comment to activity in global organization (see [Readme.md](./src/OroPro/Bundle/CommentBundle/README.md))


####OroProOrganizationBundle
- The signature of `OroPro\Bundle\OrganizationBundle\Form\Extension\DynamicFieldsExtension::__construct` was changed

Before:

```
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\Translator;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class DynamicFieldsExtension extends BaseDynamicFieldsExtension
{
    public function __construct(
        ConfigManager $configManager,
        Router $router,
        Translator $translator,
        SecurityFacade $securityFacade
    ) {
```

After

```
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\Translator;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class DynamicFieldsExtension extends BaseDynamicFieldsExtension
{
    public function __construct(
        ConfigManager $configManager,
        Router $router,
        Translator $translator,
        SecurityFacade $securityFacade,
        SystemAccessModeOrganizationProvider $systemAccessModeOrganizationProvider
    ) {
```
This changes affected on `oro_entity_extend.form.extension.dynamic_fields` service in DIC.

- The signature of `OroPro\Bundle\OrganizationBundle\Provider\OrganizationExclusionProvider::__construct` was changed

Before:

```
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class OrganizationExclusionProvider implements ExclusionProviderInterface
{
    public function __construct(
        ServiceLink $securityFacadeLink,
        ConfigProvider $organizationConfigProvider
    ) {
```

After:

```
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class OrganizationExclusionProvider implements ExclusionProviderInterface
{
    public function __construct(
        ServiceLink $securityFacadeLink,
        ConfigProvider $organizationConfigProvider,
        SystemAccessModeOrganizationProvider $organizationProvider
    ) {
```
This changes affected on `oropro_organization.exclusion_provider` service in DIC.

- The signature of `OroPro\Bundle\OrganizationBundle\Twig\OrganizationExtension::__construct` was changed

Before:

```
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class OrganizationExtension extends \Twig_Extension
{
    public function __construct(
        ConfigProvider $organizationProvider,
        Registry $doctrine,
        Translator $translator
    ) {
```

After:

```
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper;

class OrganizationExtension extends \Twig_Extension
{
    public function __construct(
        ConfigProvider $organizationProvider,
        Registry $doctrine,
        Translator $translator,
        OrganizationProHelper $organizationHelper
    ) {
```
This changes affected on `oropro_organization.twig.extension.organization` service in DIC.


####OroProTestFrameworkBundle
- This bundle was moved to the platform
