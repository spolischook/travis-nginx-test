<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ImportStrategyHelper $strategyHelper,
        FieldHelper $fieldHelper,
        DatabaseHelper $databaseHelper,
        DefaultOwnerHelper $defaultOwnerHelper,
        ConnectorContextMediator $contextMediator,
        LdapHelper $ldapHelper
    )
    {
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

        if ($this->ldapHelper instanceof ContextAwareInterface) {
            $this->ldapHelper->setImportExportContext($context);
        }
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
    protected function afterProcessEntity($entity)
    {
        $this->defaultOwnerHelper->populateChannelOwner($entity, $this->contextMediator->getChannel($this->context));
        $this->ldapHelper->populateUserRoles($entity);

        return parent::afterProcessEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function importExistingEntity(
        $entity,
        $existingEntity,
        $itemData = null,
        array $excludedFields = []
    )
    {
        $dns = (array)$existingEntity->getLdapDistinguishedNames();
        foreach($entity->getLdapDistinguishedNames() as $channelId => $dn) {
            $dns[$channelId] = $dn;
        }

        $entity->setLdapDistinguishedNames($dns);

        parent::importExistingEntity($entity, $existingEntity, $itemData, $excludedFields);
    }

}
