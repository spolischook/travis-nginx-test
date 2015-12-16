<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Exception\ActionException;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Performs workflow transition for given entity.
 */
class TransitWorkflow extends AbstractAction
{
    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var string
     */
    protected $transition;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param ContextAccessor $contextAccessor
     * @param WorkflowManager $workflowManager
     */
    public function __construct(ContextAccessor $contextAccessor, WorkflowManager $workflowManager)
    {
        parent::__construct($contextAccessor);
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $entity = $this->contextAccessor->getValue($context, $this->entity);
        $transition = $this->contextAccessor->getValue($context, $this->transition);
        $workflowItem = $this->workflowManager->getWorkflowItemByEntity($entity);
        if (!$workflowItem) {
            throw new ActionException(
                sprintf(
                    'Cannot transit workflow, instance of "%s" doesn\'t have workflow item.',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }

        $data = $this->getData($context);
        if ($data) {
            $workflowItem->getData()->add($data);
        }

        $this->workflowManager->transit($workflowItem, $transition);
    }
    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (isset($options['entity'])) {
            $this->entity = $options['entity'];
        } elseif (isset($options[0])) {
            $this->entity = $options[0];
        } else {
            throw new InvalidParameterException('Option "entity" is required.');
        }
        if (isset($options['transition'])) {
            $this->transition = $options['transition'];
        } elseif (isset($options[1])) {
            $this->transition = $options[1];
        } else {
            throw new InvalidParameterException('Option "transition" is required.');
        }

        if (isset($options['data'])) {
            $this->data = $options['data'];
        } elseif (isset($options[2])) {
            $this->data = $options[2];
        }
    }

    /**
     * @param mixed $context
     * @return array
     */
    protected function getData($context)
    {
        $data = $this->data;

        foreach ($data as $key => $value) {
            $data[$key] = $this->contextAccessor->getValue($context, $value);
        }

        return $data;
    }
}
