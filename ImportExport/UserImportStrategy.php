<?php

namespace OroCRMPro\Bundle\LDAPBundle\ImportExport;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;

class UserImportStrategy extends ConfigurableAddOrReplaceStrategy
{
    /** @var DefaultOwnerHelper */
    private $defaultOwnerHelper;

    /** @var ConnectorContextMediator */
    private $contextMediator;

    /** @var LdapHelper */
    private $ldapHelper;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param ImportStrategyHelper     $strategyHelper
     * @param FieldHelper              $fieldHelper
     * @param DatabaseHelper           $databaseHelper
     * @param DefaultOwnerHelper       $defaultOwnerHelper
     * @param ConnectorContextMediator $contextMediator
     * @param LdapHelper               $ldapHelper
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ImportStrategyHelper $strategyHelper,
        FieldHelper $fieldHelper,
        DatabaseHelper $databaseHelper,
        DefaultOwnerHelper $defaultOwnerHelper,
        ConnectorContextMediator $contextMediator,
        LdapHelper $ldapHelper
    ) {
        parent::__construct($eventDispatcher, $strategyHelper, $fieldHelper, $databaseHelper);
        $this->defaultOwnerHelper = $defaultOwnerHelper;
        $this->contextMediator = $contextMediator;
        $this->ldapHelper = $ldapHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        parent::setImportExportContext($context);
        $this->ldapHelper->setImportExportContext($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function updateContextCounters($entity)
    {
        $identifier = $this->databaseHelper->getIdentifier($entity);
        if ($identifier) {
            $this->context->incrementUpdateCount();
        } else {
            $this->context->incrementAddCount();
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $entity = parent::process($entity);
        if ($entity) {
            // populate required relations only if a validation passed
            $this->defaultOwnerHelper->populateChannelOwner(
                $entity,
                $this->contextMediator->getChannel($this->context)
            );
            $this->ldapHelper->populateUserRoles($entity);
            $this->ldapHelper->populateOrganization($entity);
            $this->ldapHelper->populateBusinessUnitOwner($entity);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    protected function importExistingEntity(
        $entity,
        $existingEntity,
        $itemData = null,
        array $excludedFields = []
    ) {
        $channel = $this->contextMediator->getChannel($this->context);
        // skip updates if priority is on local records.
        if ($channel->getSynchronizationSettings()
                ->offsetGet('syncPriority') == 'local'
        ) {
            $this->context->incrementUpdateCount(-1);

            return;
        }

        $dns = (array)$existingEntity->getLdapDistinguishedNames();
        foreach ($entity->getLdapDistinguishedNames() as $channelId => $dn) {
            $dns[$channelId] = $dn;
        }

        /*
         * Update existing entity so ldap dns will be updated despite being marked as excluded in importexport entity
         * configuration.
         */
        $existingEntity->setLdapDistinguishedNames($dns);

        parent::importExistingEntity($entity, $existingEntity, $itemData, $excludedFields);
    }
}
