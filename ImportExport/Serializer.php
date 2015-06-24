<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Symfony\Component\Serializer\SerializerInterface;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer as BaseSerializer;
use Zend\Ldap\Attribute;

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
            } else {
                unset($field['count']);
                $extractedData[$key] = $field;
            }
        }
        $extractedData['ldap_distinguished_names'] = [
            $context['channel'] => $extractedData['dn']
        ];
        unset($extractedData['dn']);

        return $this->serializer->deserialize($extractedData, $type, $format, $context);
    }
}
