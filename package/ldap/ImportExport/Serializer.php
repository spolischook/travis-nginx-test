<?php

namespace OroCRMPro\Bundle\LDAPBundle\ImportExport;

use Symfony\Component\Serializer\SerializerInterface;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer as BaseSerializer;

class Serializer implements SerializerInterface
{
    /** @var BaseSerializer */
    private $serializer;

    /**
     * @param BaseSerializer $serializer
     */
    public function __construct(BaseSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($data, $format, array $context = [])
    {
        $out = $this->serializer->serialize($data, $format, $context);

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize($data, $type, $format, array $context = [])
    {
        $extractedData = [];
        foreach ($data as $key => $field) {
            if (is_array($field)) {
                if (!isset($field['count']) || ($field['count'] == 1)) {
                    $extractedData[$key] = $field[0];
                }
                if (isset($field['count'])) {
                    unset($field['count']);
                }
            } else {
                $extractedData[$key] = $field;
            }
        }
        $extractedData['ldap_distinguished_names'] = [
            $context['channel'] => $extractedData['ldap_distinguished_names']
        ];

        $entity = $this->serializer->deserialize($extractedData, $type, $format, $context);
        if ($entity->getPassword() === null) {
            $entity->setPassword('');
        }

        return $entity;
    }
}
