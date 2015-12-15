<?php

namespace Oro\Bundle\WorkflowBundle\Model;

class ProcessData extends AbstractStorage implements EntityAwareInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntity()
    {
        return $this->get('data');
    }
}
