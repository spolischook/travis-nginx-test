<?php

namespace OroPro\Bundle\EwsBundle\Connector;

use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;

class EwsAdditionalPropertiesBuilder
{
    /**
     * @var EwsType\NonEmptyArrayOfPathsToElementType
     */
    protected $additionalProperties;

    public function __construct()
    {
        $this->additionalProperties = new EwsType\NonEmptyArrayOfPathsToElementType();
    }

    /**
     * Get built additional properties
     *
     * @return EwsType\NonEmptyArrayOfPathsToElementType
     */
    public function get()
    {
        return $this->additionalProperties;
    }

    /**
     * @param string $fieldUri
     */
    public function addUnindexedFieldUri($fieldUri)
    {
        if (!$this->additionalProperties->FieldURI) {
            $this->additionalProperties->FieldURI = array();
        }

        $field = new EwsType\PathToUnindexedFieldType();
        $field->FieldURI = $fieldUri;
        $this->additionalProperties->FieldURI[] = $field;
    }

    /**
     * @param string[] $fieldUris
     */
    public function addUnindexedFieldUris(array $fieldUris)
    {
        if (!$this->additionalProperties->FieldURI) {
            $this->additionalProperties->FieldURI = array();
        }

        foreach ($fieldUris as $fieldUri) {
            $field = new EwsType\PathToUnindexedFieldType();
            $field->FieldURI = $fieldUri;
            $this->additionalProperties->FieldURI[] = $field;
        }
    }
}
