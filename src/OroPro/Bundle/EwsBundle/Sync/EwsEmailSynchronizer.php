<?php

namespace OroPro\Bundle\EwsBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer;
use Oro\Bundle\UserBundle\Entity\User;

use OroPro\Bundle\EwsBundle\Connector\EwsConnector;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;

class EwsEmailSynchronizer extends AbstractEmailSynchronizer
{
    const CREATE_ORIGIN_BATCH_SIZE = 100;

    /**
     * @var EmailOwnerProviderStorage
     */
    protected $emailOwnerProviderStorage;

    /**
     * @var EwsConnector
     */
    protected $connector;

    /**
     * @var string
     */
    protected $userEntityClass;

    /**
     * Constructor
     *
     * @param EntityManager             $em
     * @param EmailEntityBuilder        $emailEntityBuilder
     * @param EmailAddressManager       $emailAddressManager
     * @param EmailOwnerProviderStorage $emailOwnerProviderStorage
     * @param EwsConnector              $connector
     * @param string                    $userEntityClass
     */
    public function __construct(
        EntityManager $em,
        EmailEntityBuilder $emailEntityBuilder,
        EmailAddressManager $emailAddressManager,
        EmailOwnerProviderStorage $emailOwnerProviderStorage,
        EwsConnector $connector,
        $userEntityClass
    ) {
        parent::__construct($em, $emailEntityBuilder, $emailAddressManager);

        $this->connector                 = $connector;
        $this->emailOwnerProviderStorage = $emailOwnerProviderStorage;
        $this->userEntityClass           = $userEntityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(EmailOrigin $origin)
    {
        return ($origin instanceof EwsEmailOrigin);
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
        return new EwsEmailSynchronizationProcessor(
            $this->log,
            $this->em,
            $this->emailEntityBuilder,
            $this->emailAddressManager,
            new EwsEmailManager($this->connector)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function resetHangedOrigins()
    {
        $this->initializeOrigins();

        parent::resetHangedOrigins();
    }

    /**
     * Creates missing email origins
     */
    protected function initializeOrigins()
    {
        $this->log->notice('Initializing email origins ...');

        $server = 'orolab.net:8091';

        $iterator = new BufferedQueryResultIterator(
            $this->findDataForNewEmailOriginsQuery($server)
        );

        $lastUserId   = -1;
        $batchCounter = self::CREATE_ORIGIN_BATCH_SIZE;
        $counter = 0;
        foreach ($iterator as $item) {
            /** @var User $user */
            $user  = $item[0];
            $email = $item['email'];
            if (!empty($email)) {
                $this->log->notice(sprintf('Create email origin. Server: %s. User Email: %s', $server, $email));
                $origin = new EwsEmailOrigin();
                $origin->setServer($server);
                $origin->setUserEmail($email);
                $this->em->persist($origin);
                $user->addEmailOrigin($origin);

                $batchCounter--;
                $counter++;
            }
            if ($batchCounter <= 0 && $lastUserId != $user->getId()) {
                $this->em->flush();
                $this->em->clear();
                $batchCounter = self::CREATE_ORIGIN_BATCH_SIZE;
            }
            $lastUserId = $user->getId();
        }
        $this->em->flush();
        $this->em->clear();

        $this->log->notice(sprintf('Created %d email origin(s).', $counter));
    }

    /**
     * @param string $server
     * @return Query
     */
    protected function findDataForNewEmailOriginsQuery($server)
    {
        $domains = [];
        if (empty($domains)) {
            $domains[] = $this->getHost($server);
        }

        $subQuery = $this->em->getRepository($this->userEntityClass)
            ->createQueryBuilder('u1')
            ->innerJoin('u1.emailOrigins', 'o')
            ->innerJoin(
                $this->emailAddressManager->getEmailAddressProxyClass(),
                'a1',
                'WITH',
                sprintf('a1.%s = u1.id', $this->getEmailAddressUserOwnerFieldName())
            )
            ->innerJoin($this->getEmailOriginClass(), 'ews', 'WITH', 'ews.id = o.id')
            ->where('u1.id = u.id AND ews.server = :server AND ews.userEmail = a1.email')
            ->getQuery();

        $qb = $this->em->getRepository($this->userEntityClass)
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
            ->setParameter('server', $server);

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
