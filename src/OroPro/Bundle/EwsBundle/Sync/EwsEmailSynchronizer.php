<?php

namespace OroPro\Bundle\EwsBundle\Sync;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerFactory;
use Oro\Bundle\UserBundle\Entity\User;

use OroPro\Bundle\EwsBundle\Connector\EwsConnector;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;
use OroPro\Bundle\EwsBundle\Provider\EwsServiceConfigurator;

class EwsEmailSynchronizer extends AbstractEmailSynchronizer
{
    const CREATE_ORIGIN_BATCH_SIZE = 100;

    /** @var EmailAddressManager */
    protected $emailAddressManager;

    /** @var EwsEmailSynchronizationProcessorFactory */
    protected $syncProcessorFactory;

    /** @var EmailOwnerProviderStorage */
    protected $emailOwnerProviderStorage;

    /** @var EwsConnector */
    protected $connector;

    /** @var EwsServiceConfigurator */
    protected $configurator;

    /** @var string */
    protected $userEntityClass;

    /**
     * Constructor
     *
     * @param ManagerRegistry                         $doctrine
     * @param KnownEmailAddressCheckerFactory         $knownEmailAddressCheckerFactory
     * @param EwsEmailSynchronizationProcessorFactory $syncProcessorFactory
     * @param EmailAddressManager                     $emailAddressManager
     * @param EmailOwnerProviderStorage               $emailOwnerProviderStorage
     * @param EwsConnector                            $connector
     * @param EwsServiceConfigurator                  $configurator
     * @param string                                  $userEntityClass
     */
    public function __construct(
        ManagerRegistry $doctrine,
        KnownEmailAddressCheckerFactory $knownEmailAddressCheckerFactory,
        EwsEmailSynchronizationProcessorFactory $syncProcessorFactory,
        EmailAddressManager $emailAddressManager,
        EmailOwnerProviderStorage $emailOwnerProviderStorage,
        EwsConnector $connector,
        EwsServiceConfigurator $configurator,
        $userEntityClass
    ) {
        parent::__construct($doctrine, $knownEmailAddressCheckerFactory);

        $this->syncProcessorFactory      = $syncProcessorFactory;
        $this->emailAddressManager       = $emailAddressManager;
        $this->emailOwnerProviderStorage = $emailOwnerProviderStorage;
        $this->connector                 = $connector;
        $this->configurator              = $configurator;
        $this->userEntityClass           = $userEntityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(EmailOrigin $origin)
    {
        return $origin instanceof EwsEmailOrigin;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkConfiguration()
    {
        $server    = $this->configurator->getServer();
        $isEnabled = $this->configurator->isEnabled();

        return !empty($server) && $isEnabled;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEmailOriginClass()
    {
        return 'OroProEwsBundle:EwsEmailOrigin';
    }

    /**
     * Creates a processor is used to synchronize emails
     *
     * @param EwsEmailOrigin $origin
     * @return EwsEmailSynchronizationProcessor
     */
    protected function createSynchronizationProcessor($origin)
    {
        return $this->syncProcessorFactory->create(
            new EwsEmailManager($this->connector),
            $this->getKnownEmailAddressChecker()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function resetHangedOrigins()
    {
        $this->deactivateOutdatedOrigins();
        $this->initializeOrigins();

        parent::resetHangedOrigins();
    }

    /**
     * Deactivates outdated email origins
     */
    protected function deactivateOutdatedOrigins()
    {
        $this->logger->notice('Deactivating outdated email origins ...');

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->update($this->getEmailOriginClass(), 'ews')
            ->set('ews.isActive', ':inactive')
            ->where('ews.isActive = :isActive AND ews.server <> :server')
            ->setParameter('inactive', false)
            ->setParameter('isActive', true)
            ->setParameter('server', $this->configurator->getServer());
        $counter = $qb->getQuery()->execute();

        $this->logger->notice(sprintf('Deactivated %d email origin(s).', $counter));
    }

    /**
     * Creates missing email origins
     */
    protected function initializeOrigins()
    {
        $this->logger->notice('Initializing email origins ...');

        $em = $this->getEntityManager();

        /**
         * TODO this config value is taken ONLY from GlobalScopeManager so as there is no authorized user
         * TODO to use OrganizationScopeManager and UserOrganizationScopeManager
         */
        $server = $this->configurator->getServer();

        $lastUserId   = -1;
        $batchCounter = self::CREATE_ORIGIN_BATCH_SIZE;
        $counter = 0;
        $items = $this->findDataForNewEmailOriginsQuery($server)->execute();
        foreach ($items as $item) {
            /** @var User $user */
            $user  = $item[0];
            $email = $item['email'];
            if (!empty($email)) {
                $this->logger->notice(sprintf('Create email origin. Server: %s. User Email: %s', $server, $email));

                $origin = new EwsEmailOrigin();
                $origin->setServer($server);
                $origin->setUserEmail($email);
                $origin->setOwner($user);
                $origin->setOrganization($user->getOrganization());
                $em->persist($origin);
                $user->addEmailOrigin($origin);

                $batchCounter--;
                $counter++;
            }
            if ($batchCounter <= 0 && $lastUserId != $user->getId()) {
                $em->flush();
                $em->clear();
                $batchCounter = self::CREATE_ORIGIN_BATCH_SIZE;
            }
            $lastUserId = $user->getId();
        }
        $em->flush();
        $em->clear();

        $this->logger->notice(sprintf('Created %d email origin(s).', $counter));
    }

    /**
     * @param string $server
     * @return Query
     */
    protected function findDataForNewEmailOriginsQuery($server)
    {
        $domains = $this->configurator->getDomains();
        if (empty($domains)) {
            $domains[] = $this->getHost($server);
        }

        $subQuery = $this->getEntityManager()->getRepository($this->userEntityClass)
            ->createQueryBuilder('u1')
            ->innerJoin('u1.emailOrigins', 'o')
            ->innerJoin(
                $this->emailAddressManager->getEmailAddressProxyClass(),
                'a1',
                'WITH',
                sprintf('a1.%s = u1.id', $this->getEmailAddressUserOwnerFieldName())
            )
            ->innerJoin($this->getEmailOriginClass(), 'ews', 'WITH', 'ews.id = o.id')
            ->where('u1.id = u.id AND o.isActive = :isActive AND ews.server = :server AND ews.userEmail = a1.email')
            ->getQuery();

        $qb = $this->getEntityManager()->getRepository($this->userEntityClass)
            ->createQueryBuilder('u')
            ->select('partial u.{id}, a.email')
            ->innerJoin(
                $this->emailAddressManager->getEmailAddressProxyClass(),
                'a',
                'WITH',
                sprintf('a.%s = u.id', $this->getEmailAddressUserOwnerFieldName())
            );
        $qb->where(
            $qb->expr()->not($qb->expr()->exists($subQuery->getDQL()))
        );
        $emailExpr = $qb->expr()->orX();
        foreach ($domains as $domain) {
            $emailExpr->add(
                $qb->expr()->like(
                    'a.email',
                    $qb->expr()->literal('%@' . $domain)
                )
            );
        }
        $qb
            ->andWhere($emailExpr)
            ->orderBy('u.id')
            ->setParameter('server', $server)
            ->setParameter('isActive', true);

        return $qb->getQuery();
    }

    /**
     * @param string $server
     * @return string|null
     */
    protected function getHost($server)
    {
        $portDelimPos = strrpos($server, ':');
        if ($portDelimPos === false) {
            return $server;
        }

        return substr($server, 0, $portDelimPos);
    }

    /**
     * @return string
     */
    protected function getEmailAddressUserOwnerFieldName()
    {
        $providers = $this->emailOwnerProviderStorage->getProviders();
        foreach ($providers as $provider) {
            $class = $provider->getEmailOwnerClass();
            if ($this->userEntityClass === $class || is_subclass_of($this->userEntityClass, $class)) {
                return $this->emailOwnerProviderStorage->getEmailOwnerFieldName($provider);
            }
        }
    }
}
