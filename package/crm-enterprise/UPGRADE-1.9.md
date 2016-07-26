UPGRADE FROM 1.8 to 1.9
=======================
####OroProOrganizationConfigBundle
- Added bundle that allow to manipulate config values on organizational scope.


####OroProOrganizationBundle
- The signature of `OroPro\Bundle\OrganizationBundle\Controller\OrganizationController::indexAction` was changed from `indexAction(Request $request)` to `indexAction()` 
- The signature of `OroPro\Bundle\OrganizationBundle\Form\Extension\DynamicFieldsExtension::__construct` was changed

Before:

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

After

```
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\Translator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class DynamicFieldsExtension extends BaseDynamicFieldsExtension
{
    public function __construct(
        ConfigManager $configManager,
        Router $router,
        Translator $translator,
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade,
        SystemAccessModeOrganizationProvider $systemAccessModeOrganizationProvider
    ) {
```
This changes affected on `oro_entity_extend.form.extension.dynamic_fields` service in DIC.
