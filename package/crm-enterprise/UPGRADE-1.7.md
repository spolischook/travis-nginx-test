UPGRADE FROM 1.6 to 1.7
=======================
####OroProPlatformBundle
- Added bundle that allow to configure existing features of Oro Platform (see [Readme.md](./src/OroPro/Bundle/PlatformBundle/README.md))


####OroProEwsBundle
- Added `OroPro\Bundle\EwsBundle\Sync\EwsEmailSynchronizationProcessorFactory`
- The signature of `OroPro\Bundle\EwsBundle\Sync\EwsEmailSynchronizationProcessor::__construct` was changed

Before:

```
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressChecker;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;

class EwsEmailSynchronizationProcessor extends AbstractEmailSynchronizationProcessor
{
    public function __construct(
        LoggerInterface $log,
        EntityManager $em,
        EmailEntityBuilder $emailEntityBuilder,
        EmailAddressManager $emailAddressManager,
        KnownEmailAddressChecker $knownEmailAddressChecker,
        EwsEmailManager $manager
    ) { }
}
```

After:

```
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerInterface;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;

class EwsEmailSynchronizationProcessor extends AbstractEmailSynchronizationProcessor
{
    public function __construct(
        EntityManager $em,
        EmailEntityBuilder $emailEntityBuilder,
        KnownEmailAddressCheckerInterface $knownEmailAddressChecker,
        EwsEmailManager $manager
    ) {}
}
```

- The signature of `OroPro\Bundle\EwsBundle\Sync\EwsEmailSynchronizer::__construct` was changed

Before:

```
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

class EwsEmailSynchronizer extends AbstractEmailSynchronizer
{
    public function __construct(
        ManagerRegistry $doctrine,
        EmailEntityBuilder $emailEntityBuilder,
        EmailAddressManager $emailAddressManager,
        EmailAddressHelper $emailAddressHelper,
        EmailOwnerProviderStorage $emailOwnerProviderStorage,
        EwsConnector $connector,
        EwsServiceConfigurator $configurator,
        $userEntityClass
    ) { }
}
```

After:

```
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerFactory;

class EwsEmailSynchronizer extends AbstractEmailSynchronizer
{
    public function __construct(
        ManagerRegistry $doctrine,
        KnownEmailAddressCheckerFactory $knownEmailAddressCheckerFactory,
        EwsEmailSynchronizationProcessorFactory $syncProcessorFactory,
        EmailAddressManager $emailAddressManager,
        EmailOwnerProviderStorage $emailOwnerProviderStorage,
        EwsConnector $connector,
        EwsServiceConfigurator $configurator,
        $userEntityClass
    ) { }
}
```
This changes affected on `oro_pro_ews.email_synchronizer` service in DIC.


####OroCRMProOutlookBundle
- Removed configuration group `integrations_outlook_contacts`


####OroProOrganizationBundle
- Added support `form.yml` config file 
- Was changed bundle priority from -100 to 100
