<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\LDAPBundle\Provider\Transport\LdapTransportInterface;

class LdapUserWriter implements ItemWriterInterface, StepExecutionAwareInterface, ContextAwareInterface
{

    /** @var ContextInterface */
    protected $context;
    /** @var LdapTransportInterface */
    protected $transport;
    /** @var Channel */
    protected $channel;

    /**
     * @param ContextRegistry          $contextRegistry
     * @param ConnectorContextMediator $contextMediator
     */
    public function __construct(ContextRegistry $contextRegistry, ConnectorContextMediator $contextMediator)
    {
        $this->contextRegistry = $contextRegistry;
        $this->contextMediator = $contextMediator;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        foreach ($items as $item) {
            if (is_null($item['dn']) || !isset($item['dn'][$this->channel->getId()])) {
                $dn = $this->createExportDn($item);
            } else {
                $dn = $item['dn'][$this->channel->getId()];
            }
            unset($item['dn']);

            if ($this->transport->exists($dn)) {
                if ($this->channel->getSynchronizationSettings()->offsetGet('syncPriority') == 'local') {
                    $this->transport->update($dn, $item);
                    $this->context->incrementUpdateCount();
                }
            } else {
                $item['objectClass'] = $this->channel->getMappingSettings()->offsetGet('exportUserObjectClass');
                $this->transport->add($dn, $item);
                $this->context->incrementAddCount();
            }
        }
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
        $usernameAttr = $this->channel->getMappingSettings()->offsetGet('userMapping')['username'];
        $exportUserBaseDn = $this->channel->getMappingSettings()->offsetGet('exportUserBaseDn');

        return sprintf('%s=%s,%s', $usernameAttr, $item[$usernameAttr], $exportUserBaseDn);
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
