<?php

namespace OroCRMPro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;

use OroCRMPro\Bundle\LDAPBundle\ImportExport\Utils\LdapUtils;
use OroCRMPro\Bundle\LDAPBundle\Provider\Transport\LdapTransportInterface;

class LdapUserWriter implements ItemWriterInterface, StepExecutionAwareInterface, ContextAwareInterface
{

    /** @var ContextInterface */
    protected $context;
    /** @var LdapTransportInterface */
    protected $transport;
    /** @var Channel */
    protected $channel;
    /** @var LdapHelper */
    private $ldapHelper;

    /**
     * @param ContextRegistry          $contextRegistry
     * @param ConnectorContextMediator $contextMediator
     * @param LdapHelper               $ldapHelper
     */
    public function __construct(
        ContextRegistry $contextRegistry,
        ConnectorContextMediator $contextMediator,
        LdapHelper $ldapHelper
    ) {
        $this->contextRegistry = $contextRegistry;
        $this->contextMediator = $contextMediator;
        $this->ldapHelper = $ldapHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $exported = [];
        $usernameAttribute = $this->getUsernameAttribute();

        foreach ($items as $item) {
            if (!isset($item['dn'][$this->channel->getId()])) {
                $dn = $this->createExportDn($item);
            } else {
                $dn = $item['dn'][$this->channel->getId()];
            }
            unset($item['dn']);

            $username = $item[$usernameAttribute];

            try {
                if (!$this->transport->exists($dn)) {
                    $item['objectClass'] = $this->channel->getMappingSettings()->offsetGet('exportUserObjectClass');
                    $this->transport->add($dn, $item);
                    $this->context->incrementAddCount();
                    $exported[$username] = $dn;
                } elseif ($this->channel->getSynchronizationSettings()->offsetGet('syncPriority') == 'local') {
                    $this->transport->update($dn, $item);
                    $this->context->incrementUpdateCount();
                    $exported[$username] = $dn;
                }
            } catch (\Exception $e) {
                $this->context->addError($e->getMessage());
                $this->context->incrementErrorEntriesCount();
            }
        }

        $this->ldapHelper->updateUserDistinguishedNames($this->channel->getId(), $exported);
    }

    /**
     * Creates Dn for export.
     *
     * @param array $item Record ready for LDAP export.
     *
     * @return string
     */
    protected function createExportDn(array $item)
    {
        return LdapUtils::createDn(
            $usernameAttr = $this->getUsernameAttribute(),
            $item[$usernameAttr],
            $this->channel->getMappingSettings()->offsetGet('exportUserBaseDn')
        );
    }

    /**
     * Returns username mapping attribute for current channel.
     *
     * @return string
     */
    protected function getUsernameAttribute()
    {
        $mappingSettings = $this->channel->getMappingSettings();
        return strtolower($mappingSettings->offsetGet('userMapping')[LdapUtils::USERNAME_MAPPING_ATTRIBUTE]);
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->setImportExportContext($this->contextRegistry->getByStepExecution($stepExecution));
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
        $this->channel = $this->contextMediator->getChannel($this->context);
        $this->transport = $this->contextMediator->getTransport($this->channel);
        $this->transport->init($this->channel->getTransport());
    }
}
