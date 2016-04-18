<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

class UserRoleNormalizer extends AbstractNormalizer
{
    /**
     * {@inheritdoc}
     */
    protected function getFieldRules()
    {
        return array(
            'name' => array(
                'primary' => true
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetClassName()
    {
        return 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\UserRole';
    }
}
