<?php

namespace OroCRMPro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;

class UserDataConverter extends AbstractTableDataConverter implements StepExecutionAwareInterface, ContextAwareInterface
{
    /** @var ConnectorContextMediator */
    protected $contextMediator;

    /** @var array */
    protected $userMapping;

    /** @var ContextRegistry */
    private $contextRegistry;

    /**
     * @param ConnectorContextMediator $contextMediator
     * @param ContextRegistry          $contextRegistry
     */
    public function __construct(ConnectorContextMediator $contextMediator, ContextRegistry $contextRegistry)
    {
        $this->contextMediator = $contextMediator;
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return $this->userMapping;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        $header = array_filter(
            $this->getHeaderConversionRules(),
            function ($value) {
                return !is_array($value);
            }
        );

        return array_values($header);
    }

    /**
     * @param Channel $channel
     *
     * @return $this
     */
    protected function setChannel(Channel $channel)
    {
        $this->userMapping = array_map(
            'strtolower',
            $channel->getMappingSettings()->offsetGet('userMapping')
        );

        $this->userMapping = array_flip(array_filter($this->userMapping));
        $this->userMapping += ['dn' => 'ldap_distinguished_names'];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->setChannel($this->contextMediator->getChannel($context));
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->setImportExportContext($this->contextRegistry->getByStepExecution($stepExecution));
    }

    /**
     * {@inheritdoc}
     */
    protected function fillEmptyColumns(array $header, array $data)
    {
        $dataDiff = array_diff(array_keys($data), $header);
        $data = array_diff_key($data, array_flip($dataDiff));

        return parent::fillEmptyColumns($header, $data);
    }
}
