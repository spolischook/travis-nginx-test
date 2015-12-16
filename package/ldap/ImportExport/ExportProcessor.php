<?php

namespace OroCRMPro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor as BaseExportProcessor;

class ExportProcessor extends BaseExportProcessor implements StepExecutionAwareInterface
{
    /** @var ContextRegistry */
    private $contextRegistry;

    /**
     * @param ContextRegistry $contextRegistry
     */
    public function __construct(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->setImportExportContext($this->contextRegistry->getByStepExecution($stepExecution));
    }
}
