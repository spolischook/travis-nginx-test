UPGRADE FROM 1.10 to 1.11
=======================
####OroProUserBundle
- Added bundle that allow to create and manage organization specific roles


####OroCRMProOutlookBundle
- Removed resource `translations/tooltips.en.yml`


####OroProEntityConfigBundle
- Added bundle to replace entity config info template in `OroEntityConfigBundle`


####OroCRMProOutlookBundle
- Bundle has been upgraded to Symfony 2.7
- Added 2 new api controller
 * `OroCRMPro\Bundle\OutlookBundle\Controller\Api\Rest\CountryRegionsController`
 * `OroCRMPro\Bundle\OutlookBundle\Controller\Api\Rest\CountryRegionsController`
 
 
####OroProEwsBundle
- Bundle has been upgraded to Symfony 2.7
- Removed resource `translations/tooltips.en.yml`
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
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use OroPro\Bundle\EwsBundle\Connector\EwsConnector;

class EwsEmailBodyLoader implements EmailBodyLoaderInterface
{
    public function __construct(EwsConnector $connector, ConfigManager $configManager)
```
This changes affected on `oro_pro_ews.email_body_loader` service in DIC.


####OroProOrganizationBundle
- The signature of `OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper::__construct` was changed

Before:

```
use Doctrine\Common\Persistence\ManagerRegistry;

class OrganizationProHelper
{
    public function __construct(ManagerRegistry $doctrine)
```

After:

```
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class OrganizationProHelper
{
    public function __construct(ManagerRegistry $doctrine, TokenStorageInterface $tokenStorage)
```
This changes affected on `oropro_organization.helper` service in DIC.

- The signature of `OroPro\Bundle\OrganizationBundle\Twig\WindowsExtension::renderFragment` was changed

Before:

```
use Oro\Bundle\WindowsBundle\Entity\WindowsState;

class WindowsExtension extends BaseWindowsExtension
{
    public function renderFragment(\Twig_Environment $environment, WindowsState $windowState)
```

After:

```
use Oro\Bundle\WindowsBundle\Entity\AbstractWindowsState;

class WindowsExtension extends BaseWindowsExtension
{
    public function renderFragment(\Twig_Environment $environment, AbstractWindowsState $windowState)
```
This changes affected on `oro_windows.twig.extension` service in DIC.

- Changes in class `OroPro\Bundle\OrganizationConfigBundle\Config\OrganizationScopeManager`
    * Removed method `getSettingValue`
    * Method `setScopeId` has no longer be called without parameter
    * Method `getSettingValue` changed signature from `getSettingValue($name, $full = false)` to `setSecurityContext(TokenStorageInterface $securityContext)`
This changes affected on `oropro_organization_config.scope.organization` service in DIC.
    
- Changes in class `OroPro\Bundle\OrganizationConfigBundle\Config\UserOrganizationScopeManager`
    * Removed methods `setSecurity`, `setScopeId`
    * Method `getUserOrganizationId` changed visibility from `public` to `protected`
This changes affected on `oropro_organization_config.scope.user` service in DIC.

- Class `Oro\Bundle\OrganizationBundle\Entity\Organization\SearchResultOrganizationExtension` moved to `Oro\Bundle\OrganizationBundle\Entity\Organization\GlobalOrganizationExtension`
- Removed resources `translations/tooltips.en.yml`, `views/organizationInfo.html.twig`, `views/viewOrganizationInfo.html.twig`


####OroProSecurityBundle
- Removed `OroPro\Bundle\SecurityBundle\Acl\Voter\AclProVoter`
